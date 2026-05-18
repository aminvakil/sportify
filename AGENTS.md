# AGENTS.md

## Project context

This is a legacy Symfony application being upgraded incrementally.

Current baseline:
- Symfony 3.4 LTS
- PHP 7.0 in Docker
- MySQL 5.7 in Docker
- Composer 2.2.x in Docker
- Node 6 / npm 3 / Bower / Gulp 3 in Docker

The project is intentionally old. Do not modernize broad areas unless the current step requires it.

## Working rules

- Make minimal, focused changes per step.
- Do not combine unrelated upgrades.
- Do not refactor opportunistically.
- Keep Docker as the source of truth for local verification.
- Do not install host packages.
- Do not commit secrets, API tokens, local `parameters.yml`, `vendor/`, `node_modules/`, `lib/`, generated assets, or cache/log files.
- Do not push unless explicitly asked.

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
docker compose up -d php
curl -I --max-time 10 http://localhost:8000/app_dev.php/
docker compose down -v
```

## Upgrade strategy

Preferred path:
1. Keep Symfony 3.4 test suite passing.
2. Resolve low-risk Symfony 3.4 deprecations.
3. Move PHP one minor version at a time: 7.0 -> 7.1 -> 7.2 -> 7.3 -> 7.4.
4. Only after PHP 7.4 is stable, consider larger dependency/framework upgrades.
5. Keep frontend modernization separate from PHP/Symfony upgrades.
