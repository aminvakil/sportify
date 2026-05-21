# AGENTS.md

## Project context

This is a legacy Symfony application being upgraded incrementally.

Current baseline:
- Symfony 6.4 LTS
- PHP 8.1 in Docker
- Apache httpd 2.4 in Docker serving `web/` and proxying dynamic requests to PHP
- MySQL 5.7 in Docker
- Composer 2.2.x in Docker
- Node 6 / npm 3 / Bower / Gulp 3 in Docker

The project is intentionally old. Do not modernize broad areas unless the current step requires it.

## Working rules

- Make minimal, focused changes per step.
- Always make changes on a branch other than `main`; never make changes directly on `main`.
- Commit and push changes to that branch, create a pull request, and check whether the PR tests pass.
- If PR tests fail, investigate, fix the failure on the same branch, push the fix, and re-check the PR tests.
- Do not merge PRs; the user will handle merging.
- Do not combine unrelated upgrades.
- Do not refactor opportunistically.
- Keep Docker as the source of truth for local verification.
- Do not install host packages.
- Do not commit secrets, API tokens, local `parameters.yml`, `vendor/`, `node_modules/`, `lib/`, generated assets, or cache/log files.
- Do not push on `main` unless explicitly asked.
- Keep PR descriptions concise; do not add a detailed summary unless asked.
- For user-facing web changes, verify the affected flow in a real local browser in addition to command-line smoke checks.
- For changes limited to `AGENTS.md` and/or `TODO.md`, commit and push when requested, but do not run local Docker verification or wait for CI unless explicitly asked.

## Output and token discipline

- Avoid pasting huge command outputs into chat. Redirect noisy test/build output to a temp file, then inspect only the relevant summary or error lines.
- For failing PHPUnit/Symfony runs, prefer extracting concise failure sections (for example, grep around `^[0-9]+)`), not full HTML error pages or full logs.
- Keep searches targeted. Use narrower `rg` patterns, `--count`, or `head` before printing large match sets.
- Prefer `git diff --stat` plus targeted diffs for risky files instead of printing broad diffs.
- For doc-only changes, commit and push when requested; do not run local Docker verification or wait for CI unless explicitly asked.
- Keep progress updates short and avoid repeating branch/verification details unless they changed or are needed for the final summary.

## Verification rule

Before validating each upgrade step, reset Docker state:

```sh
docker compose down -v
```

Then verify from a clean setup as much as practical:

```sh
cp docker/symfony/parameters.yml app/config/parameters.yml
docker compose build
docker compose run --rm php composer install --no-interaction --no-progress
docker compose run --rm node npm install
docker compose run --rm node bower install
docker compose run --rm node gulp
docker compose run --rm php php bin/console cache:clear --env=test
docker compose run --rm php php bin/console doctrine:database:create --if-not-exists
docker compose run --rm php php bin/console doctrine:schema:validate --skip-sync
docker compose run --rm php php bin/console doctrine:schema:update --force
docker compose run --rm php vendor/bin/simple-phpunit --testsuite 'Project Test Suite'
docker compose up --wait httpd
curl -I --max-time 10 http://localhost:8000/
# Leave Docker running after local verification so the user can test manually.
```

## Upgrade strategy

Preferred path:
1. Keep Symfony 6.4 test suite passing.
2. Resolve Symfony 6.4 deprecations and Symfony 7.4 blockers.
3. Upgrade Docker PHP to the minimum supported version before Symfony 7.4.
4. Keep frontend modernization separate from PHP/Symfony upgrades.
