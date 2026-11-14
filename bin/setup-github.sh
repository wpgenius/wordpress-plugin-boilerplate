#!/usr/bin/env bash
# Applies the repo settings this boilerplate expects. "Use this template"
# copies files only, so run this once in every new repo (it is safe to re-run).
#
#   bin/setup-github.sh                 # repo of the current git checkout
#   bin/setup-github.sh owner/name      # a specific repo
#
# Needs the GitHub CLI (`gh auth login`) with admin rights on the repo.
#
# What it sets:
#   - merges: squash only; commit message = PR title + PR body; delete branch on merge
#   - labels: release:minor, release:major (bump the automatic version)
#   - variable DEPLOY_ENABLED=false (only if not set yet), environment "production"
#   - ruleset "Protect main" on the default branch: no deletion, no force-push,
#     PRs required (squash), "Linked issue" check required.
#     Bypass: repository admins (so you can still edit on GitHub directly) and
#     GitHub Actions (so the release workflow can commit the version bump).
set -euo pipefail

command -v gh >/dev/null || { echo "Install the GitHub CLI first: https://cli.github.com" >&2; exit 1; }
repo=${1:-$(gh repo view --json nameWithOwner --jq .nameWithOwner)}
[[ "$repo" =~ ^[A-Za-z0-9_.-]+/[A-Za-z0-9_.-]+$ ]] || { echo "Usage: $0 [owner/name]" >&2; exit 1; }
echo "Configuring $repo"

echo "- merge settings"
gh api --silent -X PATCH "repos/$repo" \
	-F allow_squash_merge=true \
	-F allow_merge_commit=false \
	-F allow_rebase_merge=false \
	-f squash_merge_commit_title=PR_TITLE \
	-f squash_merge_commit_message=PR_BODY \
	-F delete_branch_on_merge=true

echo "- labels"
gh label create release:minor --repo "$repo" --force --color 1d76db \
	--description "Release as a minor version (new feature)" >/dev/null
gh label create release:major --repo "$repo" --force --color b60205 \
	--description "Release as a major version (breaking change)" >/dev/null

echo "- deploy switch"
if gh variable get DEPLOY_ENABLED --repo "$repo" >/dev/null 2>&1; then
	echo "  DEPLOY_ENABLED already set to '$(gh variable get DEPLOY_ENABLED --repo "$repo")'; left as is"
else
	gh variable set DEPLOY_ENABLED --repo "$repo" --body false
	echo "  DEPLOY_ENABLED=false"
fi
gh api --silent -X PUT "repos/$repo/environments/production"

echo "- ruleset 'Protect main'"
# actor_id 5 = the "Admin" repository role; 15368 = the GitHub Actions app.
ruleset=$(cat <<'JSON'
{
  "name": "Protect main",
  "target": "branch",
  "enforcement": "active",
  "conditions": { "ref_name": { "include": ["~DEFAULT_BRANCH"], "exclude": [] } },
  "bypass_actors": [
    { "actor_type": "RepositoryRole", "actor_id": 5, "bypass_mode": "always" },
    { "actor_type": "Integration", "actor_id": 15368, "bypass_mode": "always" }
  ],
  "rules": [
    { "type": "deletion" },
    { "type": "non_fast_forward" },
    { "type": "pull_request", "parameters": {
        "allowed_merge_methods": ["squash"],
        "required_approving_review_count": 0,
        "dismiss_stale_reviews_on_push": false,
        "require_code_owner_review": false,
        "require_last_push_approval": false,
        "required_review_thread_resolution": false
    } },
    { "type": "required_status_checks", "parameters": {
        "strict_required_status_checks_policy": false,
        "required_status_checks": [ { "context": "Linked issue" } ]
    } }
  ]
}
JSON
)
existing=$(gh api "repos/$repo/rulesets" --jq '.[] | select(.name == "Protect main") | .id' | head -n 1)
if [ -n "$existing" ]; then
	printf '%s' "$ruleset" | gh api --silent -X PUT "repos/$repo/rulesets/$existing" --input -
	echo "  updated (id $existing)"
else
	printf '%s' "$ruleset" | gh api --silent -X POST "repos/$repo/rulesets" --input -
	echo "  created"
fi

echo "Done. To turn on deploys later: set DEPLOY_METHOD (ftps, ssh or wporg), add its"
echo "secrets (see .github/workflows/deploy.yml), then set DEPLOY_ENABLED=true."
