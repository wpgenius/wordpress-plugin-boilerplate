#!/usr/bin/env bash
# Shows exactly what a release zip would contain, using the repo's own
# .distignore and the standard's packaging code (.github/scripts/lib.sh).
#
#   package-preview.sh [repo-dir] [lib.sh]
#
# lib.sh defaults to the repo's .github/scripts/lib.sh. Flags files that look
# like they should not ship. Exit 1 when the main file is missing from the package.
set -euo pipefail

repo=${1:-.}
cd "$repo"
git rev-parse --git-dir >/dev/null 2>&1 || { echo "$(pwd) is not a git repo" >&2; exit 1; }
lib=${2:-.github/scripts/lib.sh}
[ -f "$lib" ] || { echo "No $lib; pass the standard's lib.sh as the second argument" >&2; exit 1; }
[ -f .distignore ] || { echo "No .distignore in $(pwd)" >&2; exit 1; }
lib=$(cd "$(dirname "$lib")" && pwd)/$(basename "$lib")

stage=$(mktemp -d)
trap 'rm -rf "$stage"' EXIT
# CI builds from a clean checkout, so start from what git would commit:
# tracked files plus new files that aren't git-ignored (no local worktrees,
# caches or other untracked clutter).
mkdir -p "$stage/src"
git ls-files -z --cached --others --exclude-standard | while IFS= read -r -d '' f; do
	[ -e "$f" ] || [ -L "$f" ] || continue   # deleted in the working tree
	printf '%s\0' "$f"
done | rsync -a --from0 --files-from=- ./ "$stage/src/"
(cd "$stage/src" && bash -c '. "$1"; build_package "$2"' _ "$lib" "$stage/pkg")
main=$(cd "$stage/src" && bash -c '. "$1"; main_plugin_file' _ "$lib")

echo "Main file: $main"
echo "Files in the package:"
(cd "$stage/pkg" && find . -type f | sed 's#^\./#  #' | sort)
echo "Total: $(find "$stage/pkg" -type f | wc -l | tr -d ' ') files, $(du -sh "$stage/pkg" | cut -f1)"

suspicious=$(cd "$stage/pkg" && find . -type f \( \
	-path './.*' -o -path '*/node_modules/*' -o -path './vendor/bin/*' -o -path './tests/*' \
	-o -path './src/*.js' -o -name '*.zip' -o -name '*.log' -o -name '*.sql' -o -name '.env*' \
	-o -name 'composer.json' -o -name 'composer.lock' -o -name 'package*.json' \
	-o -name 'phpcs.xml*' -o -name 'phpunit.xml*' -o -name 'docker-compose*.yml' \) | sed 's#^\./#  #' | sort)
if [ -n "$suspicious" ]; then
	echo
	echo "Check these (usually dev-only):"
	echo "$suspicious"
fi

[ -f "$stage/pkg/$main" ] || { echo "ERROR: $main is not in the package" >&2; exit 1; }
