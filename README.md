# WordPress plugin boilerplate

A starter kit for WordPress plugins that is ready on day one: a clean,
namespaced plugin with working examples, plus the whole workflow around it
(coding standards, tests, CI, automatic versioned releases and optional deploys).
Rename it with one command and start building.

[![Latest release](https://img.shields.io/github/v/release/wpgenius/wordpress-plugin-boilerplate)](https://github.com/wpgenius/wordpress-plugin-boilerplate/releases/latest)
[![CI](https://github.com/wpgenius/wordpress-plugin-boilerplate/actions/workflows/ci.yml/badge.svg)](https://github.com/wpgenius/wordpress-plugin-boilerplate/actions/workflows/ci.yml)
[![License: GPL v3+](https://img.shields.io/badge/license-GPL--3.0--or--later-blue)](LICENSE)

## What you get

**A plugin you can keep, change or trim**
- Namespaced classes in `src/`, one per concern (admin, front end, AJAX, cron,
  post types, blocks), all sharing one `Api` helper class. No Composer needed
  at runtime: it runs straight from a clone or a zip.
- A **settings page built from one array**: add a field in one line and get it
  registered, sanitised, rendered and readable with `get_setting()`.
- Working examples: a custom post type with an admin column, a shortcode with
  an AJAX "Load more" button, a dynamic block, a scheduled email with
  `{placeholders}`, theme-overridable templates, and a "View details" popup for
  plugins that aren't on WordPress.org.
- Activation, upgrades and an uninstall that only deletes data when asked.
  Multisite aware.

**The workflow around it**
- WordPress Coding Standards, PHP compatibility (7.4–8.4), unit tests, JS/CSS
  lint and WordPress Plugin Check, all run on every pull request.
- **Automatic releases:** merge a pull request and, if the plugin changed, a new
  version is tagged, the changelog is written from the closed issues, and a
  zip is published. Docs-only changes don't make a release.
- **Optional deploys** to FTPS, SSH or WordPress.org, behind an on/off switch.
- A protected `main` branch, issue-linked pull requests, Dependabot, and a
  one-command GitHub setup.
- A local WordPress in Docker, and a rename script so the first five minutes
  are easy.

## Why this boilerplate

Most boilerplates give you folders. This one gives you a plugin that already
works, following WordPress best practices (escaping, nonces, capabilities,
i18n, dbDelta, block.json), and the release process most teams build by hand
later. Everything is plain PHP and shell, readable in an afternoon, with no
framework to learn.

## Start a new plugin

You need `git`, `perl` (both preinstalled on macOS and Linux) and, for step 4,
the [GitHub CLI](https://cli.github.com) signed in with `gh auth login`.
The examples create a plugin called **Acme Events**; use your own names.

### 1. Get the code

Either click **Use this template** on GitHub and clone your new repo, or clone this
one and start a fresh history:

```bash
git clone https://github.com/wpgenius/wordpress-plugin-boilerplate.git acme-events
```

```bash
cd acme-events && rm -rf .git && git init -b main
```

### 2. Rename the plugin

From the plugin folder, run:

```bash
bin/rename.sh
```

It asks for the plugin name, a one-line description and the author, suggests
everything else, shows you the result and asks before changing anything:

```
Your plugin:
  Name            Acme Events
  Folder / slug   acme-events   (main file: acme-events.php, text domain: acme-events)
  PHP namespace   Acme\AcmeEvents   (e.g. Acme\AcmeEvents\Admin\Settings)
  Prefix          acme_events   (options, hooks, functions)   constants: ACME_EVENTS_*
  Version         reset to 1.0.0, changelog emptied
```

It renames every file, class, constant, function, option, hook, block and text
domain, fills in the plugin header, resets the version to 1.0.0, and removes
this repo's own Claude Code plugin (`claude/`, `.claude-plugin/`).

You can also pass everything at once, e.g. for scripts:

```bash
bin/rename.sh "Acme Events" --vendor Acme --author "Acme Inc" --github acme/acme-events --yes
```

See `bin/rename.sh --help` for every option. Afterwards, read through the plugin
header and `readme.txt`, and replace this README with your own.

### 3. Run it locally

The plugin runs straight from the checkout; no `composer install` or
`npm install` is needed to activate it (the compiled block in `build/` is
committed). To get a WordPress site for it, see
[Local development](#local-development). You can also clone straight into an
existing site's `wp-content/plugins/` folder.

### 4. Publish to GitHub and apply the repo settings

```bash
git add -A && git commit -m "Start Acme Events from wordpress-plugin-boilerplate"
```

```bash
gh repo create acme-events --private --source=. --push
```

```bash
bin/setup-github.sh
```

GitHub templates and clones copy files only, so `bin/setup-github.sh` applies the
settings once: squash-only merges, the `release:minor` / `release:major` labels,
`DEPLOY_ENABLED=false`, and a "Protect main" ruleset. The ruleset requires PRs
with a linked issue, and lets repo admins and the release workflow (through a `release-bot` deploy key it creates) bypass it (see
[How changes ship](#how-changes-ship)). It needs admin rights on the repo and is
safe to re-run.

The first push to `main` publishes **v1.0.0** ("Initial release") with the plugin
zip; the version comes from the plugin header you reset in step 2. From then on, every change follows
the flow below: open an issue, branch, PR, merge.

## Local development

**WordPress in Docker** (mounts this repo as the plugin):

```bash
cp .env.example .env
```

```bash
docker compose up -d
```

WordPress runs at http://localhost:8000 and phpMyAdmin at http://localhost:8080.
Change `WP_HTTP_PORT` / `PMA_HTTP_PORT` in `.env` if those ports are taken.

**Code checks** (Composer and npm are for development tools only):

```bash
composer install
```

| Command | What it does |
|---|---|
| `composer lint` | PHP syntax check |
| `composer phpcs` / `composer phpcbf` | WordPress Coding Standards (check / auto-fix) |
| `composer test` | Unit tests (PHPUnit + Brain Monkey) |
| `composer make-pot` | Regenerate `languages/*.pot` |
| `npm ci` | Install the JS tools |
| `npm run build` / `npm start` | Build the blocks into `build/` (once / watch) |
| `npm run lint` | JS and CSS lint |

Commit `build/` after changing a block; CI fails if it is out of date.

## How changes ship

```
issue  →  branch  →  pull request (Closes #N)  →  squash merge to main  →  automatic release  →  deploy (if enabled)
```

- **`main` is protected.** Changes go through pull requests, merged with squash.
  Repository admins can still bypass the rules (e.g. a quick edit on GitHub).
- **Every PR closes an issue.** The *Linked issue* check fails a PR whose
  description has no `Closes #N`. One request with several changes = one issue per
  change, delivered in one PR.
- **CI** runs on every PR: PHP 7.4–8.4 syntax, unit tests, PHPCS, JS/CSS lint, block build and WordPress Plugin Check.
  Plugin Check fails when `Tested up to` in `readme.txt` is behind the latest WordPress,
  so bump it after testing each new WordPress version. In the boilerplate itself it
  also warns about the placeholder names ("plugin" in the name, the `myplugin_`
  prefix); those warnings go away once you run `bin/rename.sh`.
- **Releases are automatic.** On each merge to `main`, the
  [Release workflow](.github/workflows/release.yml):
  1. releases only if the plugin zip would change: it compares the package
     (everything [`.distignore`](.distignore) doesn't exclude) with the last
     release. Docs, CI and config changes don't release;
  2. bumps the version: patch by default, or minor/major when the PR has the
     `release:minor` / `release:major` label;
  3. writes the changelog (one line per closed issue) and the new version into
     `readme.txt` and the plugin header, and commits that to `main`;
  4. tags the commit and publishes a GitHub release with the plugin zip
     (`releases/latest/download/<slug>.zip`).
- **Deploys are off by default.** See below.

## Deploys

The [Deploy workflow](.github/workflows/deploy.yml) runs after each release while
the repo variable `DEPLOY_ENABLED` is `true`. Choose the target with `DEPLOY_METHOD`:

| `DEPLOY_METHOD` | Target | Settings (variables / secrets) |
|---|---|---|
| `ftps` | FTP with TLS (shared hosting) | `FTP_PORT`, `FTP_REMOTE_PATH`, `FTP_SSL_VERIFY` / `FTP_HOST`, `FTP_USERNAME`, `FTP_PASSWORD` |
| `ssh` | rsync over SSH | `SSH_PORT`, `SSH_REMOTE_PATH`, `SSH_DELETE` / `SSH_HOST`, `SSH_USER`, `SSH_PRIVATE_KEY`, `SSH_KNOWN_HOSTS` |
| `wporg` | WordPress.org plugin SVN | `WPORG_SLUG` / `SVN_USERNAME`, `SVN_PASSWORD` (banners and icons go in `.wordpress-org/`) |

To test the connection without uploading, run **Deploy** manually from the
Actions tab with *deploy* unticked.

## Bring an existing plugin or theme up to the standard

Everything under [How changes ship](#how-changes-ship) and [Deploys](#deploys) works
in any WordPress plugin or theme repo, not only in plugins started from this one.

### With Claude Code (optional)

This repo is also a Claude Code plugin marketplace with one plugin, **repo-standard**.
Install it once on your computer:

```bash
claude plugin marketplace add wpgenius/wordpress-plugin-boilerplate
```

```bash
claude plugin install repo-standard@wpgenius
```

Inside Claude Code the same is `/plugin marketplace add wpgenius/wordpress-plugin-boilerplate`
and then `/plugin install repo-standard@wpgenius`. It adds two commands, available in every repo:

| Command | What it does |
|---|---|
| `/repo-standard:adopt` | Audits the current plugin or theme repo against this standard, then adds the files and GitHub settings in one pull request (one issue per change). Code restructuring is only proposed. |
| `/repo-standard:new` | Starts a new plugin from this boilerplate: the rename steps above, version 1.0.0, the GitHub repo and its settings. |

The commands always use the latest `main` of this repo, so they pick up changes to
the standard automatically. They work in Claude Code on your own computer (terminal,
desktop app, IDE). Cloud sessions on claude.ai don't load plugins installed on your
computer.

### By hand

1. **Layout.** The plugin (a root `.php` file with a `Plugin Name:` header) or the
   theme (`style.css` with `Theme Name:`) must be at the repo root, not in a subfolder.
2. **Copy** from this repo, unchanged: `.github/workflows/release.yml`,
   `deploy.yml`, `linked-issue.yml`, the `.github/scripts/` folder,
   `.github/dependabot.yml` and `bin/setup-github.sh`. Delete any older release or
   deploy workflow, or every change would be released twice. CI and tooling
   (`ci.yml`, `composer.json`, `phpcs.xml.dist`, `package.json`, Docker) are optional.
3. **Ignore files.** Start from this repo's `.gitignore` and `.distignore`, then add
   your project's own rules. Anything `.distignore` doesn't exclude ships in the zip,
   so check the package contains your runtime files (`build/`, `languages/`, runtime
   `vendor/`) and nothing dev-only.
4. **readme.txt** needs a `== Changelog ==` section. For a plugin it also needs a
   `Stable tag:` equal to the header `Version:` (in `X.Y.Z` form). Themes keep their
   version in `style.css`.
5. **Versions.** Existing `vX.Y.Z` tags carry on: the next release is the next patch
   (or minor/major with a label). With no such tags, the first release takes the
   header version. If the header is ahead of your latest tag, line them up first.
6. **Merge and protect.** Open a pull request that closes an issue, merge it, and run
   `bin/setup-github.sh` (admin rights needed). Changing `.distignore` counts as
   deployable, so that merge usually makes the first automatic release.

## Plugin structure

Each area of the plugin has its own file; the folder is the area, the file is
the class. Every class extends `Api`, the shared helpers.

```
myplugin.php              header, constants, autoloader, activation hooks
uninstall.php             cleanup when the plugin is deleted
src/
├── autoload.php          WPGenius\MyPlugin\Admin\Settings → src/Admin/Settings.php
├── Plugin.php            starts every part below (admin parts only in wp-admin)
├── Api.php               shared helpers: get_setting(), verify_request(),
│                         render_template(), send_email() with {placeholders}, asset_url()
├── Installer.php         activation / deactivation, database table, upgrades
├── Ajax.php              wp_ajax_* handlers ("Load more")
├── Cron.php              scheduled jobs (daily report email)
├── Admin/
│   ├── Admin.php         admin CSS/JS, Settings link on the Plugins screen
│   ├── Settings.php      settings page built from one array of fields
│   └── PluginInfo.php    "View details" popup from readme.txt (private plugins)
├── Frontend/
│   ├── Frontend.php      front-end CSS/JS
│   └── Shortcodes.php    [myplugin_items]
├── PostTypes/
│   ├── PostTypes.php     registers each post type
│   └── Example.php       "Items" post type, taxonomy, admin column
└── Blocks/
    └── Blocks.php        registers every block in build/
blocks/example/           block source (block.json, edit.js, render.php) → npm run build → build/
templates/                HTML templates; themes override them in <theme>/wordpress-plugin-boilerplate/
assets/                   admin and front-end CSS/JS
languages/                translation template (.pot)
tests/                    PHPUnit unit tests (Brain Monkey, no WordPress needed)
```

**Add a setting:** one entry in `Settings::fields()`, then read it anywhere
with `self::get_setting( 'key' )` (or `Api::get_setting()`). Field types: text,
textarea, editor, number, checkbox, select, radio, email, url, color, time, password.

**Add a part:** create the class in `src/`, register its hooks in the
constructor, and add it to the list in `Plugin.php`. Delete the examples you
don't need the same way.

## Repo files

| Path | Purpose |
|---|---|
| `readme.txt` | WordPress.org readme; version and changelog are maintained by the release workflow |
| `.distignore` | What stays out of the zip and deploys (and so never triggers a release) |
| `.github/` | CI, release, deploy, linked-issue check, Dependabot |
| `bin/rename.sh` | Turns the boilerplate into your plugin (names, namespace, version) |
| `bin/setup-github.sh` | One-time GitHub settings for a new repo |
| `CONTRIBUTING.md`, `SECURITY.md`, `.github/ISSUE_TEMPLATE/` | How to contribute and report security issues |
| `.claude-plugin/`, `claude/repo-standard/` | Claude Code plugin with `/repo-standard:adopt` and `/repo-standard:new` (not shipped) |
| `docker-compose.yml`, `.env.example` | Local WordPress + MariaDB + phpMyAdmin |
| `composer.json`, `phpcs.xml.dist`, `phpunit.xml.dist` | PHP dev tools: coding standards, tests |
| `package.json` | Block build and JS/CSS lint (`@wordpress/scripts`) |

## License

GPL-3.0-or-later. See [LICENSE](LICENSE).
