# TODO

## Current status

- Docker development setup exists and has been verified.
- Docker httpd service serves `web/` static files and proxies dynamic requests to PHP.
- Symfony has been upgraded to 3.4 LTS.
- Docker PHP has been upgraded incrementally from 7.0 to 7.4.
- Composer dependencies have been updated to latest versions within existing constraints.
- Generated Symfony requirements/config checker files are synced with the current installed SensioDistributionBundle version.
- Basic integration workflow test exists for tournaments, users, predictions, scoring, standings, and helper/repository calls.
- GitHub Actions CI workflow is in place and green on main.
- Symfony deprecation notices have been reduced to the remaining vendor-level batch.
- Composer package constraints have been reviewed for the current Symfony 3.4/PHP 7.4 baseline; unused `sensio/generator-bundle` was removed and `doctrine/doctrine-cache-bundle` is no longer a direct dependency.
- Remaining abandoned packages are tied to the legacy Symfony 3.4 stack and should be handled as separate migrations.

## Next steps

1. Add tests for `/login` and `/register/`, then investigate and fix the page failures.
2. Decide the next backend upgrade step after the package review.
3. Keep frontend upgrade work separate from PHP/Symfony upgrade work.

## Always verify each step

Local verification should mirror `.github/workflows/ci.yml`, with an explicit clean reset before and after:

```sh
docker compose down -v
cp docker/symfony/parameters.yml app/config/parameters.yml
# CI also replaces football_api.token with the FOOTBALL_DATA_API_TOKEN secret before running.
docker compose build
docker compose run --rm php composer install --no-interaction --no-progress
docker compose run --rm node npm install
docker compose run --rm node bower install
docker compose run --rm node gulp
docker compose run --rm php php bin/console cache:clear --env=test
docker compose run --rm php php bin/console cache:clear --env=dev
docker compose run --rm php php bin/console doctrine:database:create --if-not-exists
docker compose run --rm php php bin/console doctrine:schema:validate --skip-sync
docker compose run --rm php php bin/console doctrine:schema:update --force
docker compose run --rm php php bin/console doctrine:schema:validate
docker compose run --rm php vendor/bin/simple-phpunit --testsuite 'Project Test Suite'
docker compose up --wait httpd
curl -fsSI --max-time 10 http://localhost:8000/
curl -fsSI --max-time 10 http://localhost:8000/css/style.css
curl -fsSI --max-time 10 http://localhost:8000/js/all-scripts.js
docker compose down -v
```
