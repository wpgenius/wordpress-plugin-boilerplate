#!/usr/bin/env bash
# Downloads the current repo standard: a shallow copy of the boilerplate's main.
# The boilerplate is the single source of truth, so nothing is copied into this skill.
#
#   fetch-standard.sh [dest]        prints the folder it wrote
#
# REPO_STANDARD_SOURCE overrides the source repo (owner/name).
set -euo pipefail

src=${REPO_STANDARD_SOURCE:-wpgenius/wordpress-plugin-boilerplate}
dest=${1:-"$(mktemp -d)/standard"}

command -v gh >/dev/null || { echo "Install the GitHub CLI first: https://cli.github.com" >&2; exit 1; }
[ ! -e "$dest" ] || { echo "$dest already exists; pass a new folder" >&2; exit 1; }

gh repo clone "$src" "$dest" -- --depth 1 --quiet
git -C "$dest" log -1 --format="Standard: $src @ %h \"%s\" (%cs)" >&2
echo "$dest"
