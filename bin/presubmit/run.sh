#!/usr/bin/env bash
# Pre-submission checks for WordPress.org, run on the plugin as it ships (the
# files .distignore keeps) in WordPress Playground. Nothing to install: npx
# fetches the Playground CLI. Needs Node 22+ and a network connection.
#
#   bin/presubmit/run.sh              # all checks
#   bin/presubmit/run.sh pcp          # Plugin Check only
#   bin/presubmit/run.sh runtime      # WP_DEBUG runs only
#   bin/presubmit/run.sh --strict     # exit 1 when any check fails (CI)
#
# Checks:
#   pcp      Plugin Check with every static check, experimental ones included
#            (more than CI's Plugin Check job runs).
#   runtime  A clean site with WP_DEBUG on the lowest PHP ("Requires PHP") and
#            the newest: activation output, block and shortcode rendering,
#            settings page, the plugin's assets on its own screen, no plugin
#            assets or notices on the Dashboard, no <prefix> cron events after
#            deactivation, no <prefix> options, transients or tables after
#            uninstall, and every PHP notice.
#
# Results go to release/presubmit/ (ignored by git and left out of the zip).
#
# Your plugin: edit only bin/presubmit/plugin.php (what to render, the settings
# page and own admin screen, starting admin parts that load only under
# is_admin(), what to turn on before deactivating or uninstalling, the prefix).
# activate.php, plugin-check.php, runtime.php and this script are the same in
# every plugin. These files are test scripts, not plugin code: they are not in
# the zip and PHPCS skips them.
set -euo pipefail

cd "$(dirname "${BASH_SOURCE[0]}")/../.."
# shellcheck source=../../.github/scripts/lib.sh
. .github/scripts/lib.sh

# Newer Playground CLI versions need Node 24; this one runs on Node 22.
PLAYGROUND=${PLAYGROUND:-@wp-playground/cli@3.1.61}
PHP_HIGH=${PHP_HIGH:-8.5}
what=all
strict=0
for arg in "$@"; do
	case "$arg" in
		--strict) strict=1 ;;
		all|pcp|runtime) what=$arg ;;
		*) echo "Unknown argument: $arg (use all, pcp, runtime, --strict)" >&2; exit 2 ;;
	esac
done

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

# --strict: fail when a result is not clean. Each runtime line must contain
# these exact pairs; a debug log with anything in it fails too.
if [ "$strict" -eq 1 ]; then
	failed=0
	fail() { echo "::error::$1"; failed=1; }
	if [ "$what" = all ] || [ "$what" = pcp ]; then
		grep -qx 'errors=0 warnings=0' "$out/plugin-check.txt" \
			|| fail "Plugin Check: $(grep -E '^errors=' "$out/plugin-check.txt" || echo 'no result') (see $out/plugin-check.txt)"
	fi
	if [ "$what" = all ] || [ "$what" = runtime ]; then
		for php in "$php_low" "$PHP_HIGH"; do
			result="$out/runtime-$php.txt"
			for want in 'activate=ok output_bytes=0' 'inline_style_or_script=no' \
				'dashboard_plugin_assets=none dashboard_notice_bytes=0' 'cron_after_deactivate=none' \
				'uninstall_output_bytes=0 options_left=none tables_left=none' 'plugin_notices=0 '; do
				grep -qF -- "$want" "$result" 2>/dev/null || fail "PHP $php: expected '$want' (see $result)"
			done
			log="$out/debug-$php.log"
			if [ -s "$log" ]; then
				fail "PHP $php: $log is not empty:"
				head -n 20 "$log"
			fi
		done
	fi
	if [ "$failed" -ne 0 ]; then
		exit 1
	fi
	echo "Strict: every check passed."
fi
