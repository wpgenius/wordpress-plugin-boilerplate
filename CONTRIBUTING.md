# Contributing

Thanks for helping improve the WordPress plugin boilerplate.

## How a change gets in

Every change starts with an issue, and the changelog of each release is built
from the issues it closes.

1. **Open an issue** (or comment on an existing one) describing the bug or
   the change. For anything large, wait for a reply before you start.
2. **Fork and branch** from `main`. Use a short descriptive branch name, such
   as `fix-uninstall` or `settings-radio-field`.
3. **Make the change** and run the checks below.
4. **Open a pull request** whose description says what changed and includes
   `Closes #<issue number>` (one line per issue). The *Linked issue* check
   fails without it.
5. A maintainer squash-merges it. If the plugin itself changed, a new version
   is released automatically.

## Local setup

```bash
composer install
```

```bash
npm ci
```

A WordPress site to try it in: `cp .env.example .env && docker compose up -d`,
then open http://localhost:8000.

## Checks to run before a pull request

| Command | What it checks |
|---|---|
| `composer lint` | PHP syntax |
| `composer phpcs` | WordPress Coding Standards (`composer phpcbf` fixes most issues) |
| `composer test` | Unit tests |
| `npm run lint` | JS and CSS |
| `npm run build` | Rebuilds `build/` after a block change (commit the result) |

CI runs the same checks, plus WordPress Plugin Check, on every pull request.

## Code style

- Follow the [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/);
  PHPCS enforces them.
- One class per file in `src/`, named after the class; hooks are registered in
  the constructor. See "Plugin structure" in the README.
- Escape output, sanitise input, check nonces and capabilities.
- Keep PHP 7.4 compatible (the minimum in the plugin header).
- Don't edit the version or the changelog in `readme.txt`: the release
  workflow writes them.

## Reporting a security issue

Please don't open a public issue. See [SECURITY.md](SECURITY.md).
