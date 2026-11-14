# Repo standard: shared rules

Both `/repo-standard:new` and `/repo-standard:adopt` follow these rules.
**The user's own instructions win:** if their CLAUDE.md or messages say otherwise, follow them.

## Source of the standard

The standard lives in one place: **wpgenius/wordpress-plugin-boilerplate**, branch `main`. Every run downloads it fresh with `fetch-standard.sh`. Never copy standard files from memory or from this plugin.

If the user keeps their own fork of the boilerplate as their standard, set `REPO_STANDARD_SOURCE=owner/name` when running `fetch-standard.sh`.

## Scripts

They're in `${CLAUDE_PLUGIN_ROOT}/scripts/`. Run them with `bash`.

| Script | Does |
|---|---|
| `fetch-standard.sh [dest]` | Shallow-clones the boilerplate and prints the folder |
| `audit.sh <standard> [repo]` | Read-only report: type, layout, slug, versions, readme.txt, files, GitHub settings |
| `merge-ignore.sh <standard-file> <repo-file>` | The standard ignore file plus the repo's extra rules |
| `package-preview.sh [repo] [lib.sh]` | Lists exactly what the release zip would contain (committable files only, like CI) |

## Working rules

- **Credits:** commits and PRs carry only the user's own git identity. Never add a Co-Authored-By trailer, a "Generated with" footer or a session link, and never commit as a bot user.
- **Branch names:** short and descriptive (e.g. `repo-standard`). Never prefixed with the assistant's name.
- **Issues and PRs:** one GitHub issue per change. All changes from one request go in one PR that closes every issue, with one `Closes #N` line per issue. Squash-merge.
  - A risky change unrelated to the rest (e.g. code restructuring) gets its own PR, so it can be reverted alone.
- **Skip markers:** never write `[skip ci]`, `[ci skip]`, `[no ci]`, `[skip actions]` or `[actions skip]` in a PR title or body, not even quoted. The squash commit inherits it and GitHub then skips every workflow, including the release.
- **`main`:** never push to it directly. The only exception is the very first commit of a brand-new repo in `/repo-standard:new`.
- **Secrets:** never ask the user to paste a secret value into the chat. Give them the exact `gh secret set NAME --repo OWNER/REPO` command to run themselves.
- **Outward-facing steps:** creating a repo, applying GitHub settings, creating a deploy key, opening or merging a PR. Confirm each one first, unless the user already asked for it in this conversation.
- **CI:** after opening a PR, check CI once and report the result. Don't poll in a loop, and merge only when the user asks.

## What the standard gives a repo

- **Ignore files:** `.gitignore`, plus `.distignore`, which decides what ships. Anything `.distignore` doesn't exclude counts as deployable.
- **Automatic releases:** `release.yml` and `.github/scripts/`. On each merge to `main` that changes the plugin zip (compared with the last release), the workflow:
  1. bumps the version: patch by default, or minor/major with the `release:minor` or `release:major` label;
  2. writes the version and changelog into the plugin header (or `style.css` for a theme) and `readme.txt`;
  3. commits that to `main`;
  4. tags it and publishes a GitHub release with the zip.
- **Deploys:** `deploy.yml`, with an on/off switch. It deploys only while the repo variable `DEPLOY_ENABLED` is `true`, which is off by default. `DEPLOY_METHOD` picks the target: `ftps`, `ssh` or `wporg`.
- **Linked issue check:** `linked-issue.yml`. A PR must close an issue.
- **Dependabot** for GitHub Actions.
- **Setup script:** `bin/setup-github.sh` applies the GitHub settings that templates don't copy:
  - squash-only merges;
  - the release labels;
  - `DEPLOY_ENABLED=false`;
  - a `release-bot` deploy key, which the release workflow uses to push its version commit;
  - a "Protect main" ruleset: no deletion, no force-push, PRs required, the "Linked issue" check required. Repo admins and deploy keys may bypass it.
- **Tooling** (optional when adopting): CI, coding standards, a local Docker environment.
