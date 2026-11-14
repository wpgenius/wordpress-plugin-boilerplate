---
name: adopt
description: Bring an existing WordPress plugin or theme repo up to the wordpress-plugin-boilerplate standard. It adds ignore files, automatic versioned releases, a deploy workflow with an on/off switch, a linked-issue check, Dependabot and a protected main. Code restructuring is only proposed. Use when the user runs /repo-standard:adopt or asks to standardize or adopt the standard in an existing repo.
argument-hint: "[path to repo, default: current folder]"
---

# /repo-standard:adopt

First read `${CLAUDE_PLUGIN_ROOT}/rules.md`, and follow it throughout.

The goal is to add the standard files and settings automatically. Code changes are only **proposed**. Work in the repo given as `$ARGUMENTS`, or in the current folder if none is given.

## 1. Audit
```bash
STD=$(bash "${CLAUDE_PLUGIN_ROOT}/scripts/fetch-standard.sh")
bash "${CLAUDE_PLUGIN_ROOT}/scripts/audit.sh" "$STD" .
```
Then read the boilerplate's `README.md` in `$STD` ("How changes ship", "Deploys"), so you can explain the flow.

Stop and explain if any of these is true:
- **Not WordPress:** the repo isn't a WordPress plugin or theme (`KIND=unknown`). The standard doesn't cover it; offer to adapt it by hand.
- **Not root layout:** the plugin or theme sits in a subfolder. The release scripts need it at the repo root. Moving it is a restructuring, so propose it (step 6), and continue only after the user says yes and that move is merged.
- **No admin rights:** the user isn't an admin of the GitHub repo, so the settings can't be applied.

## 2. One round of questions
Ask everything at once, with the recommended answer first:
- **Deploy target:**
  - *none for now* (recommended);
  - *ftps*;
  - *ssh*;
  - *wporg*. Plugins only: themes aren't deployed to WordPress.org by SVN.

  `DEPLOY_ENABLED` stays `false` whatever they choose.
- **Tooling group:** add CI, coding standards and local Docker from the boilerplate? Warn that existing code may fail the lint jobs.
- **Version mismatch:** only if the audit warned that the header version is ahead of the latest tag. How should they be lined up? For example, a `release:minor` or `release:major` label on this PR, or correcting the header.
- **Old workflows:** only if the audit found workflows that aren't in the standard. Replace or keep each one. An old release or deploy workflow must go, or every change would be released twice.
- **Classic branch protection:** only if present. Replace it with the ruleset?

## 3. Issues
Create one issue per change you'll actually make. Each body says what's wrong now and the expected outcome. A typical set:
- **Standard ignore files:** `.gitignore`, and `.distignore` deciding what ships.
- **Automatic releases:** `release.yml` and `.github/scripts/`, including the `readme.txt` Stable tag and changelog.
- **Deploy workflow:** off by default, naming the chosen target.
- **Require a linked issue on PRs.**
- **Dependabot for GitHub Actions.**
- **GitHub setup script:** `bin/setup-github.sh`.
- **Tooling group:** if chosen.
- **Remove old workflows:** if chosen.

## 4. Branch and files
Create a branch from an up-to-date default branch, e.g. `git switch -c repo-standard`. Then:
- **Copy as-is** from `$STD`:
  - `.github/workflows/release.yml`, `deploy.yml` and `linked-issue.yml`;
  - `.github/scripts/*`;
  - `.github/dependabot.yml`;
  - `bin/setup-github.sh` (keep it executable).
- **Merge** each of `.gitignore` and `.distignore`:
  ```bash
  bash "${CLAUDE_PLUGIN_ROOT}/scripts/merge-ignore.sh" "$STD/.distignore" .distignore > .distignore.new && mv .distignore.new .distignore
  ```
  Then read the "Project-specific" block. Remove rules that duplicate or fight the standard, and keep the genuinely project-specific ones.
- **`readme.txt`:**
  - A plugin needs a `Stable tag:` equal to the header version, plus an `== Changelog ==` section.
  - A theme needs `== Changelog ==` only.
  - If the file is missing, create one in WordPress readme format from the header fields.
  - Keep every existing changelog entry.
- **Header `Version:`** must be `X.Y.Z`. Fix it if it isn't, and say so.
- **Tooling group** (if chosen):
  - Copy the files.
  - Adapt the names to this repo: composer `name`, PHPCS `text_domain` and `prefixes`, the `.env.example` slug.
  - Regenerate the lock files with `composer install` and `npm install`. Never copy the boilerplate's lock files.
- **Slug:** if the audit says the repo name isn't a valid slug, `PLUGIN_SLUG` must be set (step 7).
- **README:** add a short "How changes ship" section, adapted from the boilerplate's.

## 5. Verify before pushing
- **Script syntax:** run `bash -n` on every `.github/scripts/*.sh` and on `bin/setup-github.sh`, and `actionlint` if it's installed.
- **Package contents:** run `bash "${CLAUDE_PLUGIN_ROOT}/scripts/package-preview.sh" . "$STD/.github/scripts/lib.sh"` and show the user the file list.
  - The main file must be in it.
  - Nothing dev-only may ship.
  - Everything needed at runtime must ship, e.g. `build/`, `vendor/` for runtime dependencies, `languages/`.

  Fix `.distignore` until the list is right.
- **PHP syntax:** run `find . -name '*.php' -not -path './vendor/*' -not -path './node_modules/*' -exec php -l {} \;`.

## 6. Code restructuring: propose only
Compare the code with the boilerplate's current structure (its README's "What's in the box" and its `src/` folders). Write a short proposal: what you'd move or rename, why, and the risk.

For anything that changes behaviour, say what existing sites would notice: renamed hooks, options, classes or namespaces.

Do nothing until the user says yes. Then do it in its own issues and its own PR, after the standard PR.

## 7. GitHub settings
Show what `bin/setup-github.sh` will do (from its header comment), and get a clear yes. Creating the deploy key needs explicit approval. Then run:
```bash
bin/setup-github.sh OWNER/REPO
```
Then, where needed:
- set `gh variable set PLUGIN_SLUG --body <slug>`;
- set `gh variable set DEPLOY_METHOD --body <method>` for the chosen target;
- give the user the `gh secret set …` commands for that target. The list is in the header of `deploy.yml`.

## 8. Pull request
Commit and push the branch. Open one PR that closes every issue. In the body:
- **Changes:** one line per change, under its issue.
- **Verification:** what you checked.
- **What happens on merge:**
  - A `.distignore` change counts as deployable, so the first release usually runs on this merge.
  - With no tags, that release is `v<header version>` ("Initial release").
  - Deploys stay off until `DEPLOY_ENABLED=true`.

Check CI once and report. Merge only when asked. After the merge, check the Release run once, and report the release URL or the error.

## Already on the standard?
Run this again to update. The audit lists the files that `differ` from the latest standard. Update them the same way, through issues and one PR.
