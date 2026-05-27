# AGENTS.md

## Project context

This is a legacy Symfony application being upgraded incrementally.

Current baseline:
- Symfony 7.4 LTS
- PHP 8.5 in Docker
- Apache httpd 2.4 in Docker serving `web/` and proxying dynamic requests to PHP
- MySQL 9.7 in Docker
- Composer 2.2.x in Docker
- Node 26 / npm 11 / Gulp 4 in Docker; frontend dependencies install through npm

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
- If Chromium fails to start during browser verification, try launching it with `--no-sandbox`.
- For internet research, open important provider/documentation pages in a real browser when command-line access hits a CAPTCHA, JavaScript challenge, or Cloudflare block; ask the user to solve the challenge instead of treating the site as unreachable.
- For documentation-only changes, including `AGENTS.md`, `TODO.md`, and files under `docs/`, still commit and push on a non-`main` branch, but do not run local Docker verification or wait for CI unless explicitly asked.

## Output and token discipline

- Avoid pasting huge command outputs into chat. Redirect noisy test/build output to a temp file, then inspect only the relevant summary or error lines.
- For failing PHPUnit/Symfony runs, prefer extracting concise failure sections (for example, grep around `^[0-9]+)`), not full HTML error pages or full logs.
- Keep searches targeted. Use narrower `rg` patterns, `--count`, or `head` before printing large match sets.
- Prefer `git diff --stat` plus targeted diffs for risky files instead of printing broad diffs.
- Keep progress updates short and avoid repeating branch/verification details unless they changed or are needed for the final summary.

## Verification rule

Do not reset Docker state (`docker compose down -v`) before verification. `app/config/parameters.yml` contains a valid local `FOOTBALL_DATA_API_TOKEN`; local testing should use it when exercising football-data API paths, and the token must never be shown in chat or command output. If the stack is already up, reuse it. If it isn't, bring it up and make sure the database schema is in place before running checks:

```sh
[ -f app/config/parameters.yml ] || cp docker/symfony/parameters.yml app/config/parameters.yml
docker compose up --wait httpd
docker compose run --rm php php bin/console doctrine:database:create --if-not-exists
docker compose run --rm php php bin/console doctrine:schema:update --force
```

Then run only the suite that matches the type of change.

### PHP / Symfony changes

```sh
docker compose run --rm php composer install --no-interaction --no-progress
docker compose run --rm php php bin/console cache:clear --env=test
docker compose run --rm php php bin/console doctrine:schema:validate
docker compose run --rm php vendor/bin/simple-phpunit --testsuite 'Project Test Suite'
curl -fsSI --max-time 10 http://localhost:8000/
```

Do not run the frontend asset pipeline for PHP-only changes.

### Frontend changes

```sh
docker compose run --rm node npm install
docker compose run --rm node npm test
docker compose run --rm node gulp
curl -fsSI --max-time 10 http://localhost:8000/css/style.css
curl -fsSI --max-time 10 http://localhost:8000/js/all-scripts.js
```

Do not run the PHP test suite for frontend-only changes. Frontend modernization additionally requires browser-based verification by the agent: load the affected pages in a real browser and confirm the flow visibly works, not just that command-line smoke checks return 200.

Leave Docker running after local verification so the user can test manually.
