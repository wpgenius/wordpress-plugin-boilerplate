#!/usr/bin/env bash
# Merges an ignore file (.gitignore or .distignore): the standard's file first,
# then every rule from the repo's own file that the standard doesn't already have,
# under a "Project-specific" heading. Nothing from either side is dropped.
#
#   merge-ignore.sh <standard-file> <repo-file>     prints the merged file
#
# Review the project-specific block afterwards: a rule there may un-ignore or
# re-ignore something the standard handles (e.g. "!vendor/"), and for .distignore
# it decides what ships, so always check the package preview.
set -euo pipefail

std=${1:?standard file required}
repo=${2:?repo file required}
[ -f "$std" ] || { echo "missing $std" >&2; exit 1; }

cat "$std"
[ -f "$repo" ] || exit 0

# Rules (non-blank, non-comment lines) the standard doesn't contain, in repo order.
extra=$(grep -vE '^[[:space:]]*(#|$)' "$repo" | sed 's/[[:space:]]*$//' | awk '!seen[$0]++' |
	while IFS= read -r rule; do
		grep -qxF -- "$rule" <(sed 's/[[:space:]]*$//' "$std") || printf '%s\n' "$rule"
	done)

if [ -n "$extra" ]; then
	# Keep exactly one blank line before the block.
	[ -z "$(tail -c 1 "$std")" ] || echo
	printf '\n# --------------------------------------------------\n'
	printf '# Project-specific (kept from this repo)\n'
	printf '# --------------------------------------------------\n'
	printf '%s\n' "$extra"
fi
