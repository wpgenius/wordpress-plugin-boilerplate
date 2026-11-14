#!/usr/bin/env bash
# Turns this boilerplate into your own plugin: renames every file, class,
# constant, function, option, hook, block and text domain, and resets the
# version to 1.0.0. Shows what it will do and asks before changing anything.
#
#   bin/rename.sh                    # asks a few questions
#   bin/rename.sh "Acme Events"      # works out the rest from the name
#
# Options (all optional; you are asked for the important ones if left out):
#   --vendor NAME        First part of the PHP namespace: you or your company (e.g. Acme)
#   --prefix PREFIX      Short prefix for options, hooks and functions (e.g. acme_events)
#   --description TEXT   One-line description for the plugin header
#   --author NAME        Author name
#   --author-uri URL     Author website
#   --plugin-uri URL     Plugin website
#   --github OWNER/REPO  GitHub repository (for links in the README, templates and composer.json)
#   --yes                Don't ask for confirmation (for scripts)
#
# Run it once, from the plugin folder, right after cloning.
set -euo pipefail

cd "$(dirname "${BASH_SOURCE[0]}")/.."

OLD_SLUG=wordpress-plugin-boilerplate
OLD_NAME='Plugin boilerplate for WordPress'
OLD_REPO=wpgenius/wordpress-plugin-boilerplate

die() { printf 'Error: %s\n' "$*" >&2; exit 1; }

[ -f myplugin.php ] || die "myplugin.php not found. This looks already renamed, or this is not the boilerplate folder."
command -v perl >/dev/null || die "perl is required (it comes with macOS and Linux)."

# ---------------------------------------------------------------- arguments
NAME="" VENDOR="" FUNC="" DESCRIPTION="" AUTHOR="" AUTHOR_URI="" PLUGIN_URI="" GITHUB="" YES=0
while [ $# -gt 0 ]; do
	case "$1" in
		--vendor) VENDOR=${2:-}; shift 2 ;;
		--prefix) FUNC=${2:-}; shift 2 ;;
		--description) DESCRIPTION=${2:-}; shift 2 ;;
		--author) AUTHOR=${2:-}; shift 2 ;;
		--author-uri) AUTHOR_URI=${2:-}; shift 2 ;;
		--plugin-uri) PLUGIN_URI=${2:-}; shift 2 ;;
		--github) GITHUB=${2:-}; shift 2 ;;
		--yes|-y) YES=1; shift ;;
		-h|--help) sed -n '2,19p' "$0" | sed 's/^# \{0,1\}//'; exit 0 ;;
		-*) die "unknown option $1 (see bin/rename.sh --help)" ;;
		*) [ -z "$NAME" ] || die "give the plugin name once, in quotes: bin/rename.sh \"Acme Events\""; NAME=$1; shift ;;
	esac
done

interactive=0
if [ -t 0 ] && [ "$YES" -eq 0 ]; then interactive=1; fi

ask() { # ask VAR "Question" "default"
	local answer
	if [ "$interactive" -eq 1 ]; then
		if [ -n "$3" ]; then read -r -p "$2 [$3]: " answer; else read -r -p "$2: " answer; fi
		printf -v "$1" '%s' "${answer:-$3}"
	else
		printf -v "$1" '%s' "$3"
	fi
}

# Name helpers (perl, so they behave the same on macOS and Linux).
slugify() { printf '%s' "$1" | perl -pe '$_=lc; s/[^a-z0-9]+/-/g; s/^-+|-+$//g'; }
studly()  { printf '%s' "$1" | perl -pe 's/[^A-Za-z0-9]+/ /g; s/(\w+)/\u$1/g; s/ //g; s/^(\d)/P$1/'; }
snake()   { printf '%s' "$1" | perl -pe '$_=lc; s/[^a-z0-9]+/_/g; s/^_+|_+$//g; s/^(\d)/p$1/'; }

# ---------------------------------------------------------------- questions
if [ -z "$NAME" ]; then
	[ "$interactive" -eq 1 ] || die "give the plugin name: bin/rename.sh \"Acme Events\""
	echo "Let's name your plugin. Press Enter to accept the suggestion in [brackets]."
	echo
	while [ -z "$NAME" ]; do ask NAME "Plugin name (e.g. Acme Events)" ""; done
fi

[ -n "$DESCRIPTION" ] || ask DESCRIPTION "One-line description" ""
[ -n "$AUTHOR" ]      || ask AUTHOR "Author (you or your company)" ""
[ -n "$AUTHOR_URI" ]  || ask AUTHOR_URI "Author website (optional)" ""
[ -n "$VENDOR" ]      || ask VENDOR "PHP namespace vendor, one word" "$(studly "${AUTHOR:-WPGenius}")"
[ -n "$GITHUB" ]      || ask GITHUB "GitHub repository, owner/name (optional)" ""

SLUG=$(slugify "$NAME")
PREFIX=$(studly "$NAME")
VENDOR=$(studly "$VENDOR")
[ -n "$FUNC" ] || FUNC=$(snake "$NAME")
FUNC=$(snake "$FUNC")

# Post type names are limited to 20 characters: <prefix>_item.
while [ ${#FUNC} -gt 15 ]; do
	echo "The prefix '$FUNC' is too long (max 15 characters, for post type names)."
	[ "$interactive" -eq 1 ] || die "pass a shorter one with --prefix, e.g. --prefix $(printf '%s' "$FUNC" | cut -c1-15 | sed 's/_$//')"
	ask FUNC "Short prefix for options and hooks" "$(printf '%s' "$FUNC" | cut -c1-15 | sed 's/_$//')"
	FUNC=$(snake "$FUNC")
done
CONST=$(printf '%s' "$FUNC" | tr '[:lower:]' '[:upper:]')
# Hyphenated form, for script handles, CSS classes and URL slugs.
KEBAB=$(printf '%s' "$FUNC" | tr '_' '-')

[ -n "$SLUG" ] && [ -n "$PREFIX" ] && [ -n "$VENDOR" ] && [ -n "$FUNC" ] || die "the name must contain letters or numbers."
if [ -n "$GITHUB" ]; then
	[[ "$GITHUB" =~ ^[A-Za-z0-9_.-]+/[A-Za-z0-9_.-]+$ ]] || die "--github must look like owner/name"
fi
[ -n "$PLUGIN_URI" ] || { [ -n "$GITHUB" ] && PLUGIN_URI="https://github.com/$GITHUB"; } || true

# ---------------------------------------------------------------- confirm
cat <<EOF

Your plugin:
  Name            $NAME
  Folder / slug   $SLUG   (main file: $SLUG.php, text domain: $SLUG)
  PHP namespace   $VENDOR\\$PREFIX   (e.g. $VENDOR\\$PREFIX\\Admin\\Settings)
  Prefix          $FUNC   (options, hooks, functions)   constants: ${CONST}_*
                  $KEBAB-*   (script handles, CSS classes, URL slugs)
  Author          ${AUTHOR:-(unchanged)}${AUTHOR_URI:+  <$AUTHOR_URI>}
  GitHub          ${GITHUB:-(not set)}
  Version         reset to 1.0.0, changelog emptied

EOF
if [ "$YES" -eq 0 ]; then
	[ -t 0 ] || die "run it in a terminal to confirm, or add --yes"
	read -r -p "Rename everything now? [y/N] " ok
	case "$ok" in y|Y|yes|YES) ;; *) echo "Nothing changed."; exit 0 ;; esac
fi

# ---------------------------------------------------------------- rename
export NAME SLUG VENDOR PREFIX CONST FUNC KEBAB GITHUB OLD_SLUG OLD_NAME OLD_REPO
export DESCRIPTION AUTHOR AUTHOR_URI PLUGIN_URI

# The boilerplate's own Claude Code plugin is not part of your plugin.
rm -rf claude .claude-plugin

files=$(grep -rlI --exclude-dir={.git,vendor,node_modules} \
	--exclude={README.md,composer.lock,package-lock.json,rename.sh,'.*cache'} \
	-e "$OLD_SLUG" -e "$OLD_NAME" -e 'WPGenius\\' -ie myplugin . || true)

if [ -n "$files" ]; then
	printf '%s\n' "$files" | while IFS= read -r f; do
		perl -pi -e '
			s/\Q$ENV{OLD_REPO}\E/$ENV{GITHUB}/g if length $ENV{GITHUB};
			s/\Q$ENV{OLD_SLUG}\E/$ENV{SLUG}/g;
			s/\Q$ENV{OLD_NAME}\E/$ENV{NAME}/g;
			s/WPGenius\\/$ENV{VENDOR}\\/g;
			s/MyPlugin/$ENV{PREFIX}/g;
			s/MYPLUGIN/$ENV{CONST}/g;
			s/myplugin\.php/$ENV{SLUG}.php/g;   # the main file is named after the slug
			s/myplugin-/$ENV{KEBAB}-/g;          # handles, CSS classes, URL slugs
			s/myplugin/$ENV{FUNC}/g;
		' "$f"
	done
fi

git mv -f myplugin.php "$SLUG.php" 2>/dev/null || mv myplugin.php "$SLUG.php"
if [ -f "languages/$OLD_SLUG.pot" ]; then
	git mv -f "languages/$OLD_SLUG.pot" "languages/$SLUG.pot" 2>/dev/null || mv "languages/$OLD_SLUG.pot" "languages/$SLUG.pot"
fi

# Plugin header details.
perl -pi -e '
	s/^( \* Description:\s+).*/$1$ENV{DESCRIPTION}/ if length $ENV{DESCRIPTION};
	s/^( \* Author:\s+).*/$1$ENV{AUTHOR}/ if length $ENV{AUTHOR};
	s/^( \* Author URI:\s+).*/$1$ENV{AUTHOR_URI}/ if length $ENV{AUTHOR_URI};
	s/^( \* Plugin URI:\s+).*/$1$ENV{PLUGIN_URI}/ if length $ENV{PLUGIN_URI};
' "$SLUG.php"

# Version 1.0.0 and an empty changelog: the first release of your plugin is v1.0.0.
perl -pi -e 's/^( \* Version:\s+)\S+/${1}1.0.0/; s/(define\(\s*'"'"'[A-Z0-9_]+_VERSION'"'"'\s*,\s*'"'"')[^'"'"']*/${1}1.0.0/' "$SLUG.php"
if [ -f readme.txt ]; then
	perl -0pi -e '
		s/^(Stable tag:[ \t]*)\S+/${1}1.0.0/mi;
		s/^(== Changelog ==[ \t]*\n).*?(?=^== |\z)/$1\n/ms;
	' readme.txt
	if [ -n "$DESCRIPTION" ]; then
		perl -0pi -e 's/^(=== .* ===\n(?:[^\n]+\n)*\n)[^\n]*\n/$1$ENV{DESCRIPTION}\n/' readme.txt
	fi
fi

# composer.json package name: owner/slug.
if [ -n "$GITHUB" ] && [ -f composer.json ]; then
	owner=$(printf '%s' "${GITHUB%/*}" | tr '[:upper:]' '[:lower:]')
	OWNER=$owner perl -pi -e 's#"name": "[^"]*/\Q$ENV{SLUG}\E"#"name": "$ENV{OWNER}/$ENV{SLUG}"#' composer.json
fi

left=$(grep -rIl --exclude-dir={.git,vendor,node_modules} --exclude={composer.lock,package-lock.json,rename.sh,README.md,'.*cache'} \
	-e "$OLD_SLUG" -e 'WPGenius\\' -ie myplugin . || true)

cat <<EOF
Done. "$NAME" is ready in this folder.

Next:
  1. Read through $SLUG.php and readme.txt (description, FAQ, Contributors = your
     WordPress.org username) and adjust the wording.
  2. Replace README.md with your own (it still describes the boilerplate).
  3. Try it: cp .env.example .env && docker compose up -d, then open http://localhost:8000
     (or copy this folder into wp-content/plugins/ of any site and activate it).
  4. Publish it: see "Publish to GitHub" in the boilerplate README.
EOF
if [ -n "$left" ]; then
	echo
	echo "Note: these files still mention the boilerplate; check them by hand:"
	printf '  %s\n' $left
fi
if [ -d vendor ] || [ -d node_modules ]; then
	echo
	echo "vendor/ or node_modules/ exist: run 'composer install' and 'npm run build' again."
fi
