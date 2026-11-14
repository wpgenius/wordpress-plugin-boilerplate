#!/usr/bin/env bash
# Decides whether main needs a release and, if so, which version and changelog.
#
# Output (key=value lines, appended to $GITHUB_OUTPUT when set):
#   release=true|false   version=X.Y.Z   tag=vX.Y.Z
# The changelog (one "* line" per change) is written to $CHANGELOG_FILE.
#
# Rules:
# - Only strict vMAJOR.MINOR.PATCH tags count as previous releases.
# - A release happens when a commit since the last release touched a
#   deployable file (anything .distignore does not exclude).
# - Bump: patch, or minor/major when a released PR has the label
#   release:minor / release:major (the highest wins).
# - Changelog: the titles of the issues closed by the released PRs; a commit
#   without a PR or linked issue contributes its subject line instead.
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
last=$(git tag --list 'v*' --sort=-v:refname | grep -E "$semver" | head -n 1 || true)
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
bump=patch
seen_issues=" "   # issue numbers already in the changelog, space separated
: > "$CHANGELOG_FILE"
deployable_commits=0

# Oldest first, so the changelog reads in merge order.
for sha in $(git rev-list --reverse --no-merges "$last"..HEAD); do
	changed=$(git diff-tree --no-commit-id --name-only -r "$sha")
	shipped=$(printf '%s\n' "$changed" | deployable_paths)
	subject=$(git log -1 --format=%s "$sha")
	if [ -z "$shipped" ]; then
		echo "  skip   ${sha:0:7} $subject (no deployable files)"
		continue
	fi
	echo "  ship   ${sha:0:7} $subject"
	deployable_commits=$((deployable_commits + 1))

	pr=$(gh api "repos/$GITHUB_REPOSITORY/commits/$sha/pulls" --jq '.[0].number // empty' 2>/dev/null || true)
	lines=""
	if [ -n "$pr" ]; then
		labels=$(gh api "repos/$GITHUB_REPOSITORY/issues/$pr/labels" --jq '.[].name' 2>/dev/null || true)
		if grep -qx 'release:major' <<<"$labels"; then bump=major
		elif grep -qx 'release:minor' <<<"$labels" && [ "$bump" != major ]; then bump=minor
		fi
		# Issues this PR closed: "number<TAB>title" per line.
		issues=$(gh api graphql -F owner="${GITHUB_REPOSITORY%/*}" -F name="${GITHUB_REPOSITORY#*/}" -F pr="$pr" -f query='
			query($owner: String!, $name: String!, $pr: Int!) {
				repository(owner: $owner, name: $name) {
					pullRequest(number: $pr) {
						closingIssuesReferences(first: 50) { nodes { number title } }
					}
				}
			}' --jq '.data.repository.pullRequest.closingIssuesReferences.nodes[] | "\(.number)\t\(.title)"' 2>/dev/null || true)
		while IFS=$'\t' read -r num title; do
			[ -n "$num" ] || continue
			case "$seen_issues" in *" $num "*) continue ;; esac
			seen_issues+="$num "
			lines+="* $title (#$num)"$'\n'
		done <<<"$issues"
	fi
	if [ -z "$lines" ]; then
		# Squash commits end with " (#PR)"; keep the subject as written.
		lines="* $subject"$'\n'
	fi
	printf '%s' "$lines" >> "$CHANGELOG_FILE"
done

if [ "$deployable_commits" -eq 0 ]; then
	echo "Nothing deployable changed since $last: no release."
	out "release=false"
	exit 0
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
