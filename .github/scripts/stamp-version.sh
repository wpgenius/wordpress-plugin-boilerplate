#!/usr/bin/env bash
# Writes a release into the plugin or theme files:
#   - "Version:" in the main plugin file header (style.css for a theme)
#   - "Stable tag:" in readme.txt, when it has one (themes do not)
#   - a new "= X.Y.Z =" entry at the top of readme.txt's "== Changelog =="
# Usage: stamp-version.sh <version> <changelog-file>
set -euo pipefail

here=$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)
# shellcheck source=lib.sh
. "$here/lib.sh"

version=${1:?version required}
notes=${2:?changelog file required}
[[ "$version" =~ ^[0-9]+\.[0-9]+\.[0-9]+$ ]] || { echo "::error::bad version $version" >&2; exit 1; }
[ -s "$notes" ] || { echo "::error::changelog file $notes is empty" >&2; exit 1; }

main_file=$(main_plugin_file)

# Header Version: only the first match, only within the header block.
VERSION="$version" perl -0pi -e 's/^([ \t\/*#@]*Version:[ \t]*)\S+/${1}$ENV{VERSION}/mi' "$main_file"
[ "$(header_version "$main_file")" = "$version" ] || { echo "::error::could not set Version in $main_file" >&2; exit 1; }

if [ -f readme.txt ]; then
	if grep -qiE '^Stable tag:' readme.txt; then
		VERSION="$version" perl -pi -e 's/^(Stable tag:[ \t]*)\S+/${1}$ENV{VERSION}/i' readme.txt
		grep -qiE "^Stable tag:[[:space:]]*$version\$" readme.txt || { echo "::error::could not set Stable tag in readme.txt" >&2; exit 1; }
	else
		# Theme readmes have no Stable tag; their version lives in style.css.
		echo "::notice::readme.txt has no 'Stable tag:' line; skipped"
	fi

	if grep -q '^== Changelog ==' readme.txt; then
		entry=$(printf '= %s =\n\n%s' "$version" "$(cat "$notes")")
		ENTRY="$entry" perl -0pi -e 's/^(== Changelog ==[ \t]*\n)\n*/$1\n$ENV{ENTRY}\n\n/m' readme.txt
	else
		echo "::warning::readme.txt has no '== Changelog ==' section; changelog not written"
	fi
fi

echo "Stamped $version into $main_file and readme.txt"
