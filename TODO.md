# TODO

## Current status

- Docker development setup exists and has been verified.
- Docker httpd service serves `web/` static files and proxies dynamic requests to PHP.
- Symfony has been upgraded to 3.4 LTS.
- Docker PHP has been upgraded incrementally from 7.0 to 7.4.
- Composer dependencies have been updated to latest versions within existing constraints.
- SensioDistributionBundle, its Composer script handlers, the generated requirements/config checker flow, and transitive sensiolabs/security-checker have been removed.
- Basic integration workflow test exists for tournaments, users, predictions, scoring, standings, and helper/repository calls.
- Functional coverage exists for `/login` and `/register/`; both pages load on the Symfony 3.4/FOSUserBundle baseline, the login page renders a CSRF token, and local dev registration creates enabled users.
- GitHub Actions CI workflow is in place and green on main.
- Symfony deprecation notices have been reduced to the remaining vendor-level batch.
- Composer package constraints have been reviewed for the current Symfony 3.4/PHP 7.4 baseline; unused `sensio/generator-bundle` was removed and `doctrine/doctrine-cache-bundle` is no longer a direct dependency.
- Remaining abandoned packages are tied to the legacy Symfony 3.4 stack and should be handled as separate migrations.
- `doctrine/doctrine-cache-bundle` cannot be removed while staying on Symfony 3.4 because the latest compatible `doctrine/doctrine-bundle` 1.x requires it; `doctrine/doctrine-bundle` 2.x removes that dependency but requires Symfony 4.3+.
- SensioFrameworkExtraBundle has been removed; former admin-only security annotations are explicit controller checks.
- Web controller routes have been moved from annotations to YAML routing.
- App validation constraints have been moved from annotations to YAML, and Symfony validator annotation loading is disabled.
- Current abandoned packages in `composer.lock`: `doctrine/annotations`, `doctrine/cache`, `doctrine/doctrine-cache-bundle`, `doctrine/reflection`, `swiftmailer/swiftmailer`, and `symfony/swiftmailer-bundle`.

## Next steps

1. Keep remaining `doctrine/annotations` work separate because Doctrine ORM mappings and API docs still rely on annotations; plan that migration before a Symfony major upgrade.
2. Keep `symfony/swiftmailer-bundle`/`swiftmailer/swiftmailer` migration separate; replacing it with `symfony/mailer` likely belongs with a later Symfony upgrade.
3. Keep frontend upgrade work separate from PHP/Symfony upgrade work.
4. Plan the backend upgrade path toward Symfony 7.4 LTS as the long-term framework target.

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
# Leave Docker running after local verification so the app can be tested manually.
```
