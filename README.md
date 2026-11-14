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

| Variable | Used for | Example |
|---|---|---|
| `SLUG` | folder, main file, text domain | `acme-events` |
| `NAME` | plugin name shown in WordPress | `Acme Events` |
| `PREFIX` | class prefix (`MyPlugin_Class_*`) | `Acme_Events` |
| `CONST` | constant prefix (`MYPLUGIN_*`) | `ACME_EVENTS` |
| `FUNC` | function, option and hook prefix (`myplugin_*`) | `acme_events` |

```bash
SLUG=acme-events NAME="Acme Events" PREFIX=Acme_Events CONST=ACME_EVENTS FUNC=acme_events; export SLUG NAME PREFIX CONST FUNC
```

```bash
grep -rlI --exclude-dir={.git,vendor,node_modules} --exclude={README.md,composer.lock,package-lock.json} -e wordpress-plugin-boilerplate -e 'Plugin boilerplate for WordPress' -ie myplugin . | xargs perl -pi -e 's/wordpress-plugin-boilerplate/$ENV{SLUG}/g; s/Plugin boilerplate for WordPress/$ENV{NAME}/g; s/MyPlugin/$ENV{PREFIX}/g; s/MYPLUGIN/$ENV{CONST}/g; s/myplugin/$ENV{FUNC}/g'
```

```bash
mv myplugin.php "$SLUG.php" && mv languages/wordpress-plugin-boilerplate.pot "languages/$SLUG.pot"
```

Then edit by hand: the author, URIs and description in the plugin header and
`readme.txt`, and the `name` in `composer.json` (`vendor/package`). Replace this
README with your own.

### 3. Run it locally

The plugin runs straight from the checkout; no build or `composer install` is
needed to activate it. To get a WordPress site for it, see
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
| `composer make-pot` | Regenerate `languages/*.pot` |
| `npm ci && npm run lint` | JS and CSS lint (`@wordpress/scripts`) |

## How changes ship

```
issue  →  branch  →  pull request (Closes #N)  →  squash merge to main  →  automatic release  →  deploy (if enabled)
```

- **`main` is protected.** Changes go through pull requests, merged with squash.
  Repository admins can still bypass the rules (e.g. a quick edit on GitHub).
- **Every PR closes an issue.** The *Linked issue* check fails a PR whose
  description has no `Closes #N`. One request with several changes = one issue per
  change, delivered in one PR.
- **CI** runs on every PR: PHP 7.4–8.4 syntax, PHPCS, JS/CSS lint.
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

## What's in the box

| Path | Purpose |
|---|---|
| `myplugin.php` | Main plugin file: header, constants, includes, bootstrapping |
| `includes/` | Plugin classes (admin, settings, AJAX, database, shortcodes, widgets) |
| `templates/`, `assets/`, `languages/` | Front-end templates, CSS/JS/images, translations |
| `uninstall.php` | Cleanup when the plugin is deleted |
| `readme.txt` | WordPress.org readme; version and changelog are maintained by the release workflow |
| `.distignore` | What stays out of the zip and deploys (and so never triggers a release) |
| `.github/` | CI, release, deploy, linked-issue check, Dependabot |
| `bin/setup-github.sh` | One-time GitHub settings for a new repo |
| `docker-compose.yml`, `.env.example` | Local WordPress + MariaDB + phpMyAdmin |

## License

GPL-2.0-or-later. See [LICENSE](LICENSE).
