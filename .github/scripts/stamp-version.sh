#!/usr/bin/env bash
# Writes a release into the plugin files:
#   - "Version:" in the main plugin file header (and a *_VERSION constant, if defined)
#   - "Stable tag:" in readme.txt
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

# Optional version constant, e.g. define( 'MYPLUGIN_VERSION', '1.2.3' );
VERSION="$version" perl -pi -e "s/(define\(\s*'[A-Z0-9_]+_VERSION'\s*,\s*')[^']*(')/\${1}\$ENV{VERSION}\${2}/" "$main_file"

if [ -f readme.txt ]; then
	VERSION="$version" perl -pi -e 's/^(Stable tag:[ \t]*)\S+/${1}$ENV{VERSION}/i' readme.txt
	grep -qiE "^Stable tag:[[:space:]]*$version\$" readme.txt || { echo "::error::could not set Stable tag in readme.txt" >&2; exit 1; }

	if grep -q '^== Changelog ==' readme.txt; then
		entry=$(printf '= %s =\n\n%s' "$version" "$(cat "$notes")")
		ENTRY="$entry" perl -0pi -e 's/^(== Changelog ==[ \t]*\n)\n*/$1\n$ENV{ENTRY}\n\n/m' readme.txt
	else
		echo "::warning::readme.txt has no '== Changelog ==' section; changelog not written"
	fi
fi

echo "Stamped $version into $main_file and readme.txt"
