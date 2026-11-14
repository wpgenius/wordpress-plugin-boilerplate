#!/usr/bin/env bash
# Shared helpers for the release and deploy workflows. Source, don't execute.

# File that holds the version header, in the repo root:
#   plugin: the PHP file with a "Plugin Name:" header
#   theme:  style.css with a "Theme Name:" header
main_plugin_file() {
	local f
	for f in *.php; do
		[ -f "$f" ] || continue
		if head -n 30 "$f" | grep -qiE '^[[:space:]/*#@]*Plugin Name:'; then
			echo "$f"
			return 0
		fi
	done
	if [ -f style.css ] && head -n 30 style.css | grep -qiE '^[[:space:]/*#@]*Theme Name:'; then
		echo "style.css"
		return 0
	fi
	echo "::error::No plugin file ('Plugin Name:' header in a root .php file) or theme stylesheet ('Theme Name:' header in style.css) found in the repo root" >&2
	return 1
}

# Version from the main plugin file header.
header_version() {
	head -n 30 "$1" | sed -nE 's/^[[:space:]/*#@]*Version:[[:space:]]*([^[:space:]]+).*/\1/Ip' | head -n 1
}

# Reads paths on stdin, prints the ones that ship (not excluded by .distignore).
# .distignore uses .gitignore syntax, so git itself does the matching in a
# scratch repo whose only ignore file is a copy of .distignore.
deployable_paths() {
	local scratch
	scratch=$(mktemp -d)
	git init -q "$scratch"
	cp .distignore "$scratch/.gitignore"
	printf '.git\n' >> "$scratch/.gitignore"
	local paths ignored
	paths=$(cat)
	[ -n "$paths" ] || { rm -rf "$scratch"; return 0; }
	ignored=$(cd "$scratch" && printf '%s\n' "$paths" | git check-ignore --no-index --stdin || true)
	rm -rf "$scratch"
	# .distignore itself is excluded from the zip, but changing it changes the zip.
	printf '%s\n' "$paths" | while IFS= read -r p; do
		[ -n "$p" ] || continue
		if [ "$p" = ".distignore" ] || ! printf '%s\n' "$ignored" | grep -qxF -- "$p"; then
			echo "$p"
		fi
	done
}

# Copies the deployable files into $1 (created if missing).
build_package() {
	local dest="$1"
	mkdir -p "$dest"
	rsync --archive --delete --exclude='.git' --exclude-from='.distignore' ./ "$dest/"
	if find "$dest" -type l | grep -q .; then
		echo "::error::Symlinks are not allowed in the package:" >&2
		find "$dest" -type l >&2
		return 1
	fi
}
