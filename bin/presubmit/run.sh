#!/usr/bin/env bash
# Pre-submission checks for WordPress.org, run on the plugin as it ships (the
# files .distignore keeps) in WordPress Playground. Nothing to install: npx
# fetches the Playground CLI. Needs Node 22+ and a network connection.
#
#   bin/presubmit/run.sh              # all checks
#   bin/presubmit/run.sh pcp          # Plugin Check only
#   bin/presubmit/run.sh runtime      # WP_DEBUG runs only
#
# Checks:
#   pcp      Plugin Check with every static check, experimental ones included
#            (more than CI's Plugin Check job runs).
#   runtime  A clean site with WP_DEBUG on the lowest PHP ("Requires PHP") and
#            the newest: activation output, block and shortcode rendering,
#            settings page, admin assets and notices outside the plugin's
#            screens, cron removed on deactivation, uninstall cleanup, and
#            every PHP notice.
#
# Results go to release/presubmit/ (ignored by git and left out of the zip).
set -euo pipefail

cd "$(dirname "${BASH_SOURCE[0]}")/../.."
# shellcheck source=../../.github/scripts/lib.sh
. .github/scripts/lib.sh

# Newer Playground CLI versions need Node 24; this one runs on Node 22.
PLAYGROUND=${PLAYGROUND:-@wp-playground/cli@3.1.61}
PHP_HIGH=${PHP_HIGH:-8.5}
what=${1:-all}

main_file=$(main_plugin_file)
# The WordPress.org slug: PLUGIN_SLUG, else the Text Domain header, else the file name.
text_domain=$(head -n 30 "$main_file" | sed -nE "s/^[[:space:]/*#@]*Text Domain:[[:space:]]*([^[:space:]]+).*/\1/Ip" | head -n 1)
slug=${PLUGIN_SLUG:-${text_domain:-$(basename "$main_file" .php)}}
php_low=$(head -n 30 "$main_file" | sed -nE 's/^[[:space:]/*#@]*Requires PHP:[[:space:]]*([0-9.]+).*/\1/Ip' | head -n 1)
php_low=${php_low:-7.4}

out="$PWD/release/presubmit"
pkg="$out/package/$slug"
# A single check keeps the other check's results.
if [ "$what" = all ]; then rm -rf "$out"; else rm -rf "$out/package"; fi
mkdir -p "$out"
build_package "$pkg"
cp bin/presubmit/*.php "$out/"
plugin="$slug/$main_file"

run() { # blueprint-file
	npx -y "$PLAYGROUND" run-blueprint --quiet --blueprint="$1" \
		--mount="$pkg:/wordpress/wp-content/plugins/$slug" --mount="$out:/sp"
}

# run-blueprint doesn't print runPHP output, so the scripts write to /sp.
if [ "$what" = all ] || [ "$what" = pcp ]; then
	cat > "$out/bp-pcp.json" <<JSON
{"preferredVersions":{"php":"8.3","wp":"latest"},"constants":{"PRESUBMIT_PLUGIN":"$plugin"},"steps":[
{"step":"installPlugin","pluginData":{"resource":"wordpress.org/plugins","slug":"plugin-check"}},
{"step":"activatePlugin","pluginPath":"$plugin"},
{"step":"runPHP","code":"<?php require '/sp/plugin-check.php';"}]}
JSON
	echo "Plugin Check (static + experimental)..."
	run "$out/bp-pcp.json"
	cat "$out/plugin-check.txt"
fi

if [ "$what" = all ] || [ "$what" = runtime ]; then
	for php in "$php_low" "$PHP_HIGH"; do
		cat > "$out/bp-runtime-$php.json" <<JSON
{"preferredVersions":{"php":"$php","wp":"latest"},
"constants":{"WP_DEBUG":true,"WP_DEBUG_DISPLAY":true,"WP_DEBUG_LOG":"/sp/debug-$php.log","PRESUBMIT_PLUGIN":"$plugin"},
"steps":[{"step":"runPHP","code":"<?php require '/sp/activate.php';"},{"step":"runPHP","code":"<?php require '/sp/runtime.php';"}]}
JSON
		echo
		echo "WP_DEBUG run on PHP $php..."
		run "$out/bp-runtime-$php.json"
		cat "$out/runtime-$php.txt"
	done
fi

echo
echo "Results: $out"
