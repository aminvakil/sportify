# TODO

## Current status

- Docker development setup exists and has been verified.
- Docker httpd service serves `web/` static files and proxies dynamic requests to PHP.
- Symfony has been upgraded to 5.4 LTS.
- Docker PHP has been upgraded incrementally from 7.0 to 7.4.
- Composer dependencies have been updated to latest versions within existing constraints.
- SensioDistributionBundle, its Composer script handlers, the generated requirements/config checker flow, and transitive sensiolabs/security-checker have been removed.
- Basic integration workflow test exists for tournaments, users, predictions, scoring, standings, and helper/repository calls.
- Functional auth coverage exists for login page CSRF, successful registration/login, failed login, logout, duplicate registration, registration validation, password reset, and profile password changes.
- GitHub Actions CI workflow is in place and green on main.
- Symfony deprecation notices have been reduced to the remaining vendor-level batch.
- `symfony/monolog-bundle` has been upgraded to 3.6, removing it from the Symfony 4.4 blocker list.
- `symfony/swiftmailer-bundle` has been upgraded to 3.3 and Swiftmailer to 6.3, removing it from the Symfony 4.4 blocker list while keeping full Mailer replacement deferred.
- `jms/serializer-bundle` has been upgraded to 3.10 and `willdurand/hateoas-bundle` to 2.6, removing them from the Symfony 5.4 blocker list and removing the old `doctrine/common ~2` constraint from Hateoas.
- `symfony/phpunit-bridge` has been upgraded to 5.4, with `SYMFONY_PHPUNIT_VERSION=7.5` pinned for the legacy PHPUnit test suite.
- Composer package constraints have been reviewed for the current Symfony 4.4/PHP 7.4 baseline; unused `sensio/generator-bundle` was removed and `doctrine/doctrine-cache-bundle` is no longer a direct dependency.
- Remaining abandoned packages are tied to legacy dependencies and should be handled as separate migrations.
- `doctrine/doctrine-bundle` has been upgraded to 2.7, Doctrine ORM to 2.20, Doctrine DBAL is pinned to 2.13, Doctrine Persistence is pinned to 2.5, and `doctrine/doctrine-cache-bundle`/`doctrine/reflection` have been removed.
- Short `DevlabsSportifyBundle:Entity` aliases in app code have been replaced with FQCN/`::class`, and remaining short aliases live only in vendor bridges.
- SensioFrameworkExtraBundle has been removed; former admin-only security annotations are explicit controller checks.
- Web controller routes have been moved from annotations to YAML routing.
- App validation constraints have been moved from annotations to YAML, and Symfony validator annotation loading is disabled.
- The leftover `Team` unique-entity validation annotation has been moved to YAML.
- The app bootstrap no longer manually registers Doctrine's annotation autoloader.
- `doctrine/annotations` is no longer a direct dependency; it remains installed transitively through Doctrine ORM, JMS serializer, and Hateoas.
- Current email usage is registration/password reset through Swiftmailer; full Mailer replacement is still deferred.
- Current abandoned packages in `composer.lock`: `doctrine/annotations`, `doctrine/cache`, `swiftmailer/swiftmailer`, and `symfony/swiftmailer-bundle`.
- FOSUserBundle has been removed; login, logout, registration, and password reset now use Symfony Security with the app `User` entity/provider/checker.
- FOSOAuthServerBundle has been removed; password-grant token issuance and API access-token authentication now use small app-owned services/controllers against the existing OAuth tables.
- FOSRestBundle and NelmioApiDocBundle have been removed; API routes are explicit YAML routes and API JSON responses are serialized directly with JMS Serializer.
- `composer why-not symfony/symfony 5.4.*` now reports no installed package blockers.
- Symfony 5.4 test output currently reports 227 direct, 7 indirect, and 1021 other deprecation notices with the expanded functional/API test suite.
- Backend upgrade path toward Symfony 7.4 LTS has been outlined below.

## Next steps

1. Stop splitting upgrade work into tiny deprecation-only PRs. Prefer larger, coherent milestone PRs that remove a full blocker or complete a framework step end-to-end.
2. Add tests before each larger change so CI gives enough confidence to review and merge bigger PRs.
3. Keep frontend modernization separate from backend/Symfony modernization unless a backend step explicitly requires a frontend change.
4. Follow the backend upgrade path toward Symfony 7.4 LTS as the long-term framework target.

## PR sizing strategy

Use bigger PRs, but keep them coherent:

- Good larger PR: "replace legacy OAuth/API stack and cover API auth flows" or "upgrade Symfony 4.4 to 5.4 and fix resulting config/code breaks".
- Bad larger PR: mixing Symfony upgrades, frontend build modernization, broad directory layout changes, and unrelated cleanup.
- Every large PR should include or expand tests for the flows it changes.
- Prefer one PR per milestone below, not one PR per deprecation notice or one PR per small config line.
- If a milestone uncovers unrelated work, note it in TODO instead of expanding scope indefinitely.

## Backend upgrade path

Keep each milestone as a PR and verify from a clean Docker state before moving on.

1. Symfony 5.4 blocker-removal PR(s), grouped by subsystem rather than tiny package changes:
   - Keep Doctrine annotation removal grouped by ORM mapping work; ORM mappings still rely on annotations.
   - Defer Swiftmailer replacement until the app can install `symfony/mailer`, unless it becomes a hard Symfony 5.4 blocker.
2. Post-5.4 modernization PR(s):
   - Replace Swiftmailer with Symfony Mailer.
   - Re-check abandoned packages and `composer why-not` output.
   - Add any missing tests discovered during the 5.4 upgrade.
3. Continue one LTS at a time:
   - Upgrade Docker PHP to the minimum supported version before Symfony 6.4.
   - Symfony 5.4 -> 6.4.
   - Upgrade Docker PHP to the minimum supported version before Symfony 7.4.
   - Symfony 6.4 -> 7.4.
4. Defer structural modernization until a framework step requires it:
   - Do not migrate the directory layout or frontend toolchain opportunistically.
   - Prefer compatibility shims and focused route/config changes over broad rewrites unless a milestone explicitly calls for a replacement.

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
