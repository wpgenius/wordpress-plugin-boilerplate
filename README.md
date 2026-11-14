# WordPress plugin boilerplate

A starter kit for WordPress plugins: plugin structure, a local Docker environment,
coding-standards tooling, CI, automatic versioned releases and optional deploys,
all wired up and ready on day one.

[![Latest release](https://img.shields.io/github/v/release/wpgenius/wordpress-plugin-boilerplate)](https://github.com/wpgenius/wordpress-plugin-boilerplate/releases/latest)

## Start a new plugin

You need `git`, `perl` (preinstalled on macOS and Linux) and, for step 4, the
[GitHub CLI](https://cli.github.com) signed in with `gh auth login`.
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

Pick your names, then run the rename from the repo root:

| Variable | Used for | Boilerplate value | Example |
|---|---|---|---|
| `SLUG` | folder, main file, text domain, block names | `wordpress-plugin-boilerplate` | `acme-events` |
| `NAME` | plugin name shown in WordPress | `Plugin boilerplate for WordPress` | `Acme Events` |
| `VENDOR` | first part of the PHP namespace (you or your company) | `WPGenius` | `Acme` |
| `PREFIX` | second part of the PHP namespace (the plugin) | `MyPlugin` | `Events` |
| `CONST` | constant prefix | `MYPLUGIN` | `ACME_EVENTS` |
| `FUNC` | option, hook, function, CSS and JS prefix | `myplugin` | `acme_events` |

So `WPGenius\MyPlugin\Admin\Settings` becomes `Acme\Events\Admin\Settings`.

```bash
SLUG=acme-events NAME="Acme Events" VENDOR=Acme PREFIX=Events CONST=ACME_EVENTS FUNC=acme_events; export SLUG NAME VENDOR PREFIX CONST FUNC
```

```bash
grep -rlI --exclude-dir={.git,vendor,node_modules} --exclude={README.md,composer.lock,package-lock.json} -e wordpress-plugin-boilerplate -e 'Plugin boilerplate for WordPress' -e 'WPGenius\\' -ie myplugin . | xargs perl -pi -e 's/wordpress-plugin-boilerplate/$ENV{SLUG}/g; s/Plugin boilerplate for WordPress/$ENV{NAME}/g; s/WPGenius\\/$ENV{VENDOR}\\/g; s/MyPlugin/$ENV{PREFIX}/g; s/MYPLUGIN/$ENV{CONST}/g; s/myplugin/$ENV{FUNC}/g'
```

```bash
mv myplugin.php "$SLUG.php" && mv languages/wordpress-plugin-boilerplate.pot "languages/$SLUG.pot"
```

Then edit by hand: the author, URIs and description in the plugin header and
`readme.txt`, the `name` in `composer.json` (`vendor/package`), and the menu
title in `src/Admin/Settings.php`. Replace this README with your own. Keep post
type names (`FUNC` + `_item`) within 20 characters.

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
zip; the version comes from the plugin header. From then on, every change follows
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
- **CI** runs on every PR: PHP 7.4–8.4 syntax, unit tests, PHPCS, JS/CSS lint, block build.
- **Releases are automatic.** On each merge to `main`, the
  [Release workflow](.github/workflows/release.yml):
  1. releases only if a *deployable* file changed: anything not excluded by
     [`.distignore`](.distignore). Docs, CI and config changes don't release;
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
| `bin/setup-github.sh` | One-time GitHub settings for a new repo |
| `docker-compose.yml`, `.env.example` | Local WordPress + MariaDB + phpMyAdmin |
| `composer.json`, `phpcs.xml.dist`, `phpunit.xml.dist` | PHP dev tools: coding standards, tests |
| `package.json` | Block build and JS/CSS lint (`@wordpress/scripts`) |

## License

GPL-3.0-or-later. See [LICENSE](LICENSE).
