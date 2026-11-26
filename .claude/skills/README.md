# Claude Code skills

Claude Code loads these skills automatically when you work in this repo. They
are not part of the plugin: `.distignore` leaves `/.claude` out of the zip.

| Skill | What it helps with |
|---|---|
| `wp-plugin-development/` | Plugin architecture, hooks, activation/deactivation/uninstall, Settings API, data, cron, security (nonces, capabilities, sanitizing, escaping), packaging. |
| `wp-plugin-directory-guidelines/` | The 18 WordPress.org Plugin Directory guidelines: GPL, naming and trademarks, trialware, Plugin Check and why plugins get rejected. |

## Where they come from

Copied unchanged from [WordPress/agent-skills](https://github.com/WordPress/agent-skills)
(branch `trunk`, folder `skills/`), © WordPress Contributors, licensed
GPL-2.0-or-later (see [LICENSE](LICENSE)).

Synced from upstream commit: `32ea909f7d01be2e08fee6aff6ae449a42b9b34f`

Don't edit them here. The "Agent skills sync" workflow checks upstream every
month and opens a pull request when either skill changed; improvements belong
upstream.
