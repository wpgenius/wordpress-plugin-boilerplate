---
name: new
description: Start a new WordPress plugin from wordpress-plugin-boilerplate. Renames everything, resets the version to 1.0.0, creates the GitHub repo, applies the protected-main settings, and pushes so the first release is v1.0.0. Use when the user runs /repo-standard:new or asks to start or scaffold a new plugin from the boilerplate.
argument-hint: "[plugin name]"
---

# /repo-standard:new

Read `${CLAUDE_PLUGIN_ROOT}/rules.md` first and follow it throughout.

The boilerplate is a plugin, so this command makes plugins. For a theme, build the theme first, then run `/repo-standard:adopt` in its repo.

## 1. Fetch and read
```bash
STD=$(bash "${CLAUDE_PLUGIN_ROOT}/scripts/fetch-standard.sh")
```
Read `$STD/README.md`, section "Start a new plugin". It is the source of truth for the rename variables and commands, and it changes over time. Use what it says now, not what you remember.

## 2. One round of questions
`$ARGUMENTS` may already give the plugin name. Ask everything else at once:
- **Plugin name and description:** the name, plus a one-line description.
- **Rename variables:** every variable the README's rename step lists (slug, prefixes, namespace parts and so on). Offer values derived from the name.
- **Author details:** author name and URI, and plugin URI. Never keep the boilerplate's author unless the user says so.
- **GitHub repo:** the owner (user or organisation) and the visibility (private recommended).
- **Folder:** where to create it. Default: next to the current folder.
- **Deploy target:** none (recommended), ftps, ssh or wporg.

Before creating anything, confirm the plan in one message:
- the repo `OWNER/SLUG` and its visibility;
- the settings that `setup-github.sh` will apply;
- the deploy key it will create.

## 3. Build the plugin locally
1. **Get a fresh copy.** Clone with a fresh history, as the README describes ("clone this one and start a fresh history").
2. **Rename.** Run the README's rename commands exactly, with the chosen values. That includes removing the boilerplate's own `.claude-plugin/` and `claude/` folders.
3. **Hand edits.** Make the README's manual edits: description, author and URIs, and the composer `name`.
4. **Reset the version and changelog.** The template carries the boilerplate's own release history.
   - Set the header `Version:` and the `readme.txt` `Stable tag:` to `1.0.0`.
   - Empty `== Changelog ==`, keeping the heading.
   - Regenerate the `.pot` file (`composer make-pot`) if Composer is set up.
5. **Write a README.** Replace `README.md` with a short one for this plugin: name, description, a "Development" pointer and "How changes ship". Keep the boilerplate's developer notes where they still apply.
6. **Check:**
   - No leftover boilerplate names (anything unintended is a miss):
     ```bash
     grep -rIn -e wordpress-plugin-boilerplate -e 'Plugin boilerplate for WordPress' -ie myplugin --exclude-dir={.git,vendor,node_modules} .
     ```
   - `php -l` passes on all PHP files.
   - The package looks right:
     ```bash
     bash "${CLAUDE_PLUGIN_ROOT}/scripts/package-preview.sh" .
     ```
     (Run `git add -A` first, so the new files count.)

## 4. Create the repo, protect it, then push
Do it in this order, so the first release runs with the deploy key already in place:
1. **Create the empty repo:**
   ```bash
   gh repo create OWNER/SLUG --private
   ```
   Use `--public` if chosen, and no `--source`.
2. **Apply the settings:**
   ```bash
   bin/setup-github.sh OWNER/SLUG
   ```
3. **Push the first commit.** This is the one allowed direct push to `main`: the repo is new and the user is an admin.
   ```bash
   git add -A
   git commit -m "Start <Name> from wordpress-plugin-boilerplate"
   git remote add origin git@github.com:OWNER/SLUG.git
   git push -u origin main
   ```
4. **Deploy target.** If one was chosen, set `DEPLOY_METHOD` and list the `gh secret set` commands the user needs.

## 5. Report
The first push triggers the Release workflow, which publishes **v1.0.0** ("Initial release"). Check it once with:
```bash
gh run list --repo OWNER/SLUG --workflow release.yml --limit 1
```
Give the release URL, or the error.

Then remind the user that every further change goes issue → branch → PR.
