#!/usr/bin/env bash
# Decides whether main needs a release and, if so, which version and changelog.
#
# Output (key=value lines, appended to $GITHUB_OUTPUT when set):
#   release=true|false   version=X.Y.Z   tag=vX.Y.Z
# The changelog (one "* line" per change) is written to $CHANGELOG_FILE.
#
# Rules:
# - Only strict vMAJOR.MINOR.PATCH tags count as previous releases.
# - A release happens only when the plugin zip would change: the package built
#   at the last release tag is compared with the one built at HEAD (each with
#   its own .distignore). Docs, CI, config and .distignore-only edits that
#   leave the zip identical make no release.
# - The changelog lists the commits that touched a deployable file (anything
#   .distignore does not exclude).
# - Bump: patch, or minor/major when a released PR has the label
#   release:minor / release:major (the highest wins).
# - Changelog: the titles of the issues closed by the released PRs, so issue
#   titles are user-facing (and never name trademarks such as "Gutenberg").
#   Each line gets a prefix from the issue's type label (security -> Security:,
#   enhancement -> Enhancement:, bug/fix -> Fix:, maintenance -> Maintenance:;
#   none without one), and lines are grouped in that order. Issues labelled
#   changelog:skip (dev-only work) are left out: that hides the line, not the
#   release. A commit without a linked issue contributes its subject line (the
#   PR title), typed by the PR's labels.
# - release:skip on a PR (or on every issue it closes) holds its change back:
#   when every change since the last release is held, there is no release. The
#   held changes ship, with their changelog lines, in the next release.
# - No previous release: version = the plugin header Version (or 1.0.0),
#   changelog "Initial release."
#
# Needs: git with full history and tags, gh (GH_TOKEN), GITHUB_REPOSITORY.
set -euo pipefail

here=$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)
# shellcheck source=lib.sh
. "$here/lib.sh"

: "${GITHUB_REPOSITORY:?GITHUB_REPOSITORY must be set (owner/repo)}"
CHANGELOG_FILE=${CHANGELOG_FILE:-changelog.txt}
out() { echo "$1"; if [ -n "${GITHUB_OUTPUT:-}" ]; then echo "$1" >> "$GITHUB_OUTPUT"; fi; }

semver='^v[0-9]+\.[0-9]+\.[0-9]+$'
last=$(git tag --list 'v*' --merged HEAD --sort=-v:refname | grep -E "$semver" | head -n 1 || true)
main_file=$(main_plugin_file)

if [ -z "$last" ]; then
	version=$(header_version "$main_file")
	[[ "v$version" =~ $semver ]] || version=1.0.0
	echo "No previous release; first release will be $version."
	printf '* Initial release.\n' > "$CHANGELOG_FILE"
	out "release=true"
	out "version=$version"
	out "tag=v$version"
	exit 0
fi

echo "Last release: $last"

changes=$(package_diff "$last" HEAD)
if [ -z "$changes" ]; then
	echo "The plugin package is identical to $last: no release."
	out "release=false"
	exit 0
fi
echo "Package changes since $last:"
printf '%s\n' "$changes" | sed 's/^/  /'

# A failed GitHub call must stop the release: guessing would turn a major
# release into a patch, or drop changelog lines. Nothing is pushed before this
# point, so the run can simply be started again.
api_failed() {
	echo "::error::Could not read $1 from GitHub. Nothing was released; re-run this workflow." >&2
	exit 1
}

bump=patch
seen_issues=" "   # issue numbers already in the changelog, space separated
: > "$CHANGELOG_FILE"
deployable_commits=0
releasable=0             # changed commits not held back with release:skip
distignore_lines=""      # lines of commits that only changed .distignore (used if nothing else explains the change)
# Changelog lines by type, printed in this order; unlabelled ones last.
b_security="" b_enhancement="" b_fix="" b_maintenance="" b_none=""

# Type of a change from label names (one per line): the first of security,
# enhancement, bug/fix, maintenance; "none" without a type label.
type_of() {
	if grep -qx 'security' <<<"$1"; then echo Security
	elif grep -qx 'enhancement' <<<"$1"; then echo Enhancement
	elif grep -qxE 'bug|fix' <<<"$1"; then echo Fix
	elif grep -qx 'maintenance' <<<"$1"; then echo Maintenance
	else echo none
	fi
}

# "* Type: text": no prefix for "none", and never doubled when the text
# already starts with "Type:".
line_for() {
	local type=$1 text=$2
	if [ "$type" != none ]; then
		shopt -s nocasematch
		case "$text" in "$type:"*) ;; *) text="$type: $text" ;; esac
		shopt -u nocasematch
	fi
	printf '* %s\n' "$text"
}

# Adds a line to its type's group.
add_line() {
	local line
	line=$(line_for "$1" "$2")$'\n'
	case "$1" in
		Security) b_security+=$line ;;
		Enhancement) b_enhancement+=$line ;;
		Fix) b_fix+=$line ;;
		Maintenance) b_maintenance+=$line ;;
		*) b_none+=$line ;;
	esac
}

# Oldest first, so the changelog reads in merge order within each type.
for sha in $(git rev-list --reverse --no-merges "$last"..HEAD); do
	changed=$(git diff-tree --no-commit-id --name-only -r "$sha")
	shipped=$(printf '%s\n' "$changed" | deployable_paths)
	subject=$(git log -1 --format=%s "$sha")
	distignore_only=0
	if [ -z "$shipped" ]; then
		if ! grep -qx '.distignore' <<<"$changed"; then
			echo "  skip   ${sha:0:7} $subject (no deployable files)"
			continue
		fi
		distignore_only=1
	fi

	pr=$(gh api "repos/$GITHUB_REPOSITORY/commits/$sha/pulls" --jq '.[0].number // empty') \
		|| api_failed "the pull request of commit ${sha:0:7}"
	labels=""
	issues=""
	if [ -n "$pr" ]; then
		labels=$(gh api "repos/$GITHUB_REPOSITORY/issues/$pr/labels" --jq '.[].name') \
			|| api_failed "the labels of PR #$pr"
		# Issues this PR closed: "number<TAB>changelog<TAB>type<TAB>release<TAB>title" per line.
		issues=$(gh api graphql -F owner="${GITHUB_REPOSITORY%/*}" -F name="${GITHUB_REPOSITORY#*/}" -F pr="$pr" -f query='
			query($owner: String!, $name: String!, $pr: Int!) {
				repository(owner: $owner, name: $name) {
					pullRequest(number: $pr) {
						closingIssuesReferences(first: 50) { nodes { number title labels(first: 30) { nodes { name } } } }
					}
				}
			}' --jq '.data.repository.pullRequest.closingIssuesReferences.nodes[]
				| (.labels.nodes | map(.name)) as $l
				| "\(.number)\t\(if ($l | index("changelog:skip")) then "skip" else "keep" end)\t\(if ($l | index("security")) then "Security" elif ($l | index("enhancement")) then "Enhancement" elif (($l | index("bug")) or ($l | index("fix"))) then "Fix" elif ($l | index("maintenance")) then "Maintenance" else "none" end)\t\(if ($l | index("release:skip")) then "hold" else "ship" end)\t\(.title)"') \
			|| api_failed "the issues closed by PR #$pr"
	fi

	# release:skip on the PR, or on every issue it closes, holds the change
	# back: it makes no release by itself, but its line goes into the next one.
	if grep -qx 'release:skip' <<<"$labels" \
		|| { [ -n "$issues" ] && ! cut -f4 <<<"$issues" | grep -qx ship; }; then
		status="hold  "
	else
		status="ship  "
		releasable=$((releasable + 1))
	fi
	pr_type=$(type_of "$labels")

	if [ "$distignore_only" -eq 1 ]; then
		echo "  $status ${sha:0:7} $subject (.distignore only)"
		distignore_lines+=$(line_for "$pr_type" "$subject")$'\n'
		continue
	fi
	echo "  $status ${sha:0:7} $subject"
	deployable_commits=$((deployable_commits + 1))

	if grep -qx 'release:major' <<<"$labels"; then bump=major
	elif grep -qx 'release:minor' <<<"$labels" && [ "$bump" != major ]; then bump=minor
	fi

	added=0
	while IFS=$'\t' read -r num keep type _hold title; do
		[ -n "$num" ] || continue
		added=1
		case "$seen_issues" in *" $num "*) continue ;; esac
		seen_issues+="$num "
		if [ "$keep" = skip ]; then
			echo "         #$num left out of the changelog (changelog:skip): $title"
			continue
		fi
		add_line "$type" "$title (#$num)"
	done <<<"$issues"
	if [ "$added" -eq 0 ]; then
		# No linked issue (or the linked-issue rule is off): the squash commit
		# subject, which is the PR title ending in " (#PR)", typed by the PR's labels.
		add_line "$pr_type" "$subject"
	fi
done

if [ "$releasable" -eq 0 ]; then
	echo "Every change since $last is labelled release:skip: no release. It ships with the next release."
	out "release=false"
	exit 0
fi

printf '%s' "$b_security$b_enhancement$b_fix$b_maintenance$b_none" > "$CHANGELOG_FILE"

if [ "$deployable_commits" -gt 0 ] && [ ! -s "$CHANGELOG_FILE" ]; then
	# Every issue behind the change was labelled changelog:skip.
	printf '* Maintenance release.\n' > "$CHANGELOG_FILE"
fi

if [ "$deployable_commits" -eq 0 ]; then
	# The zip changed only through .distignore (a file now ships or no longer does).
	if [ -n "$distignore_lines" ]; then
		printf '%s' "$distignore_lines" > "$CHANGELOG_FILE"
	else
		printf '* Maintenance release.\n' > "$CHANGELOG_FILE"
	fi
fi

IFS=. read -r major minor patch <<<"${last#v}"
case "$bump" in
	major) version="$((major + 1)).0.0" ;;
	minor) version="$major.$((minor + 1)).0" ;;
	*)     version="$major.$minor.$((patch + 1))" ;;
esac
echo "Bump: $bump -> $version"
out "release=true"
out "version=$version"
out "tag=v$version"
