#!/usr/bin/env bash
# Compares a repo with the repo standard and prints a report. Read-only:
# changes nothing locally or on GitHub.
#
#   audit.sh <standard-dir> [repo-dir]
#
# Sections: repo type and layout, slug, versions, readme.txt, standard files,
# tooling files, other workflows, GitHub settings.
set -uo pipefail

std=${1:?usage: audit.sh <standard-dir> [repo-dir]}
std=$(cd "$std" && pwd)
repo=${2:-.}
cd "$repo" || exit 1
lib="$std/.github/scripts/lib.sh"

say() { printf '%s\n' "$*"; }
hdr() { printf '\n## %s\n' "$*"; }
main_file() { bash -c '. "$1"; main_plugin_file' _ "$lib" 2>/dev/null; }
hver() { bash -c '. "$1"; header_version "$2"' _ "$lib" "$1" 2>/dev/null; }
header_field() { head -n 30 "$1" | sed -nE "s/^[[:space:]/*#@]*$2:[[:space:]]*(.*[^[:space:]])[[:space:]]*$/\1/Ip" | head -n 1; }

say "# Repo standard audit: $(pwd)"
say "Standard: $(git -C "$std" log -1 --format='%h "%s" (%cs)')"

# ---------------------------------------------------------------- type/layout
hdr "Type and layout"
main=$(main_file || true)
kind=unknown
if [ -n "$main" ]; then
	if [ "$main" = style.css ]; then kind=theme; else kind=plugin; fi
	say "- $kind, main file in the repo root: $main  (OK: root layout)"
else
	found=$(find . -maxdepth 4 -not -path './.git/*' -not -path '*/node_modules/*' -not -path '*/vendor/*' \
		\( -name '*.php' -o -name style.css \) -print0 2>/dev/null |
		xargs -0 grep -liE '^[[:space:]/*#@]*(Plugin|Theme) Name:' 2>/dev/null | head -n 5)
	if [ -n "$found" ]; then
		say "- NOT root layout. Plugin/theme header found in a subfolder:"
		printf '%s\n' "$found" | sed 's/^/    /'
		say "  The standard's scripts need the plugin or theme at the repo root -> restructuring proposal (needs the user's yes)."
	else
		say "- No WordPress plugin or theme header found. The standard covers WordPress plugins and themes only."
	fi
fi
say "KIND=$kind"

# ---------------------------------------------------------------- slug
hdr "Slug (zip folder name)"
nwo=$(gh repo view --json nameWithOwner --jq .nameWithOwner 2>/dev/null || true)
name=${nwo#*/}
say "- GitHub repo: ${nwo:-not found (no GitHub remote or gh not signed in)}"
domain=""
[ -n "$main" ] && domain=$(header_field "$main" 'Text Domain')
say "- Text Domain: ${domain:-none}"
if [ -n "$name" ]; then
	if [[ "$name" =~ ^[a-z0-9][a-z0-9-]*$ ]] && { [ -z "$domain" ] || [ "$name" = "$domain" ]; }; then
		say "- Repo name works as the slug."
	else
		say "- Repo name '$name' is not a valid/matching slug -> set repo variable PLUGIN_SLUG=${domain:-<slug>}"
	fi
fi

# ---------------------------------------------------------------- versions
hdr "Versions"
ver=""
[ -n "$main" ] && ver=$(hver "$main")
say "- Header Version: ${ver:-missing}"
[ -z "$ver" ] || [[ "$ver" =~ ^[0-9]+\.[0-9]+\.[0-9]+$ ]] || say "  Not X.Y.Z: the first automatic release will fall back to 1.0.0 unless it is fixed."
strict=$(git tag --list 'v*' --sort=-v:refname | grep -E '^v[0-9]+\.[0-9]+\.[0-9]+$' | head -n 1)
other=$(git tag --list | grep -vE '^v[0-9]+\.[0-9]+\.[0-9]+$' | head -n 5 | tr '\n' ' ')
say "- Latest vX.Y.Z tag: ${strict:-none}"
[ -z "$other" ] || say "- Other tags (ignored by the release workflow): $other"
if [ -z "$strict" ]; then
	say "- First release after setup: v${ver:-1.0.0} (\"Initial release\"), on the first merge to main."
else
	IFS=. read -r a b c <<<"${strict#v}"
	next="$a.$b.$((c + 1))"
	say "- Next automatic release: v$next (patch), or higher with a release:minor/major label. It happens only on a merge that changes the zip compared with $strict."
	if [ -n "$ver" ] && [ "$(printf '%s\n%s\n' "$ver" "$next" | sort -V | tail -n 1)" != "$next" ]; then
		say "  WARNING: header Version $ver is ahead of v$next. Ask the user how to line them up before merging."
	fi
fi
if command -v gh >/dev/null && [ -n "$nwo" ]; then
	drafts=$(gh release list --repo "$nwo" --limit 50 --json tagName,isDraft --jq '[.[]|select(.isDraft)|.tagName]|join(" ")' 2>/dev/null)
	[ -z "$drafts" ] || say "- Draft releases (may clash with future versions): $drafts"
fi

# ---------------------------------------------------------------- readme.txt
hdr "readme.txt"
if [ -f readme.txt ]; then
	grep -qiE '^Stable tag:' readme.txt && say "- Stable tag: $(grep -iE '^Stable tag:' readme.txt | head -n 1 | sed 's/^[^:]*:[[:space:]]*//')" ||
		{ [ "$kind" = theme ] && say "- No Stable tag (fine for a theme)" || say "- MISSING 'Stable tag:' line (plugins need it)"; }
	grep -q '^== Changelog ==' readme.txt && say "- == Changelog == section: present" || say "- MISSING '== Changelog ==' section: releases can't write the changelog"
	tested=$(grep -iE '^Tested up to:' readme.txt | head -n 1 | sed 's/^[^:]*:[[:space:]]*//; s/[[:space:]]*$//')
	latest=$(curl -fsS --max-time 10 https://api.wordpress.org/core/version-check/1.7/ 2>/dev/null | grep -oE '"current":"[0-9]+\.[0-9]+' | head -n 1 | sed 's/.*"//')
	if [ -z "$tested" ]; then
		[ "$kind" = theme ] || say "- MISSING 'Tested up to:' (Plugin Check fails without it)"
	elif [ -n "$latest" ] && [ "$(printf '%s\n%s\n' "$tested" "$latest" | sort -V | head -n 1)" != "$latest" ]; then
		say "- Tested up to: $tested, but the latest WordPress is $latest. Plugin Check (tooling group) fails until it is bumped after testing."
	else
		say "- Tested up to: $tested${latest:+ (latest WordPress: $latest)}"
	fi
else
	say "- MISSING readme.txt (the release writes the changelog there)"
fi

# ---------------------------------------------------------------- files
cmpfile() {
	if [ ! -f "$std/$1" ]; then say "  (not in standard) $1"
	elif [ ! -f "$1" ]; then say "  missing   $1"
	elif cmp -s "$std/$1" "$1"; then say "  same      $1"
	else say "  differs   $1"; fi
}
hdr "Standard files (copied as-is)"
for f in .github/workflows/release.yml .github/workflows/deploy.yml .github/workflows/linked-issue.yml \
	.github/scripts/lib.sh .github/scripts/release-plan.sh .github/scripts/stamp-version.sh \
	.github/dependabot.yml bin/setup-github.sh; do cmpfile "$f"; done
hdr "Ignore files (merged)"
for f in .gitignore .distignore; do cmpfile "$f"; done
hdr "Tooling (optional group: CI, coding standards, local dev)"
for f in .github/workflows/ci.yml composer.json phpcs.xml.dist phpstan.neon.dist tests/phpstan/bootstrap.php \
	phpunit.xml.dist package.json .nvmrc .editorconfig .env.example docker-compose.yml; do
	[ -f "$std/$f" ] || continue
	if [ -f "$f" ]; then say "  present   $f"; else say "  missing   $f"; fi
done

hdr "Community files (optional)"
for f in CONTRIBUTING.md SECURITY.md .github/pull_request_template.md .github/ISSUE_TEMPLATE/bug_report.yml \
	.github/ISSUE_TEMPLATE/feature_request.yml .github/ISSUE_TEMPLATE/config.yml; do
	[ -f "$std/$f" ] || continue
	if [ -f "$f" ]; then say "  present   $f"; else say "  missing   $f"; fi
done

hdr "Playground demo (optional, public plugins with releases)"
if [ -f .wordpress-org/blueprints/blueprint.json ]; then say "  present   .wordpress-org/blueprints/blueprint.json"
else say "  missing   .wordpress-org/blueprints/blueprint.json"; fi

hdr "Other workflows in this repo (not in the standard)"
others=0
for f in .github/workflows/*.yml .github/workflows/*.yaml; do
	[ -f "$f" ] || continue
	[ -f "$std/$f" ] && continue
	say "  $f  ($(sed -nE 's/^name:[[:space:]]*(.*)/\1/p' "$f" | head -n 1))"
	others=1
done
[ "$others" = 1 ] || say "  none"

# ---------------------------------------------------------------- GitHub
hdr "GitHub settings"
if [ -z "$nwo" ]; then
	say "- skipped (no GitHub repo)"
else
	gh api "repos/$nwo" --jq '"- merges: squash=\(.allow_squash_merge) merge-commit=\(.allow_merge_commit) rebase=\(.allow_rebase_merge) delete-branch=\(.delete_branch_on_merge) squash-message=\(.squash_merge_commit_title)/\(.squash_merge_commit_message) visibility=\(.visibility) default-branch=\(.default_branch) admin=\(.permissions.admin)"' 2>/dev/null || say "- repo settings: not readable"
	rs=$(gh api "repos/$nwo/rulesets" --jq '[.[].name]|join(", ")' 2>/dev/null)
	say "- rulesets: ${rs:-none}"
	gh api "repos/$nwo/branches/main/protection" >/dev/null 2>&1 && say "- classic branch protection on main: yes (the ruleset replaces it; ask before removing)"
	labels=$(gh label list --repo "$nwo" --limit 200 --json name --jq '[.[].name|select(startswith("release:"))]|join(", ")' 2>/dev/null)
	say "- release labels: ${labels:-none}"
	vars=$(gh variable list --repo "$nwo" --json name,value --jq '[.[]|"\(.name)=\(.value)"]|join(" ")' 2>/dev/null)
	say "- variables: ${vars:-none}"
	secrets=$(gh secret list --repo "$nwo" --json name --jq '[.[].name]|join(" ")' 2>/dev/null)
	say "- secret names: ${secrets:-none}"
fi
