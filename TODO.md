# TODO

## Current status

- Docker development setup exists and has been verified.
- Docker httpd service serves `web/` static files and proxies dynamic requests to PHP.
- Symfony has been upgraded to 4.4 LTS.
- Docker PHP has been upgraded incrementally from 7.0 to 7.4.
- Composer dependencies have been updated to latest versions within existing constraints.
- SensioDistributionBundle, its Composer script handlers, the generated requirements/config checker flow, and transitive sensiolabs/security-checker have been removed.
- Basic integration workflow test exists for tournaments, users, predictions, scoring, standings, and helper/repository calls.
- Functional coverage exists for `/login` and `/register/`; both pages load on the Symfony 3.4/FOSUserBundle baseline, the login page renders a CSRF token, and local dev registration creates enabled users.
- GitHub Actions CI workflow is in place and green on main.
- Symfony deprecation notices have been reduced to the remaining vendor-level batch.
- `symfony/monolog-bundle` has been upgraded to 3.6, removing it from the Symfony 4.4 blocker list.
- `symfony/swiftmailer-bundle` has been upgraded to 3.3 and Swiftmailer to 6.3, removing it from the Symfony 4.4 blocker list while keeping full Mailer replacement deferred.
- `jms/serializer-bundle` has been upgraded to 3.10 and `willdurand/hateoas-bundle` to 2.6, removing them from the Symfony 5.4 blocker list and removing the old `doctrine/common ~2` constraint from Hateoas.
- `symfony/phpunit-bridge` has been upgraded to 4.4, with `SYMFONY_PHPUNIT_VERSION=6.5` pinned for the legacy PHPUnit test suite.
- Composer package constraints have been reviewed for the current Symfony 4.4/PHP 7.4 baseline; unused `sensio/generator-bundle` was removed and `doctrine/doctrine-cache-bundle` is no longer a direct dependency.
- Remaining abandoned packages are tied to legacy dependencies and should be handled as separate migrations.
- `doctrine/doctrine-bundle` has been upgraded to 2.7, Doctrine ORM to 2.20, Doctrine DBAL is pinned to 2.13, Doctrine Persistence is pinned to 2.5, and `doctrine/doctrine-cache-bundle`/`doctrine/reflection` have been removed.
- Minimal app bridge managers/listeners keep FOSUserBundle/FOSOAuthServerBundle working with `Doctrine\Persistence`; short `DevlabsSportifyBundle:Entity` aliases in app code have been replaced with FQCN/`::class`, and remaining short aliases live only in vendor bridges.
- SensioFrameworkExtraBundle has been removed; former admin-only security annotations are explicit controller checks.
- Web controller routes have been moved from annotations to YAML routing.
- App validation constraints have been moved from annotations to YAML, and Symfony validator annotation loading is disabled.
- The leftover `Team` unique-entity validation annotation has been moved to YAML.
- The app bootstrap no longer manually registers Doctrine's annotation autoloader.
- `doctrine/annotations` is no longer a direct dependency; it remains installed transitively through Doctrine ORM, JMS serializer, and Hateoas.
- Current email usage is FOSUserBundle registration/resetting through `fos_user.mailer.twig_swift`; full Mailer replacement is still deferred.
- Current abandoned packages in `composer.lock`: `doctrine/annotations`, `doctrine/cache`, `swiftmailer/swiftmailer`, and `symfony/swiftmailer-bundle`.
- `composer why-not symfony/symfony 5.4.*` now lists the root Symfony constraint plus FOSOAuthServerBundle, FOSRestBundle, FOSUserBundle, NelmioApiDocBundle, and `symfony/contracts` blockers.
- Symfony 4.4 test output currently reports 0 direct, 20 indirect, and 40 other deprecation notices after adding the local security user checker/equatable user comparison.
- Backend upgrade path toward Symfony 7.4 LTS has been outlined below.

## Next steps

1. Keep remaining `doctrine/annotations` work separate because Doctrine ORM mappings and API docs still rely on annotations; plan that migration before a Symfony major upgrade.
2. Defer `symfony/swiftmailer-bundle`/`swiftmailer/swiftmailer` replacement until the app is on a Symfony version that can install `symfony/mailer`, unless a custom non-Symfony FOSUserBundle mailer is explicitly chosen first.
3. Keep frontend upgrade work separate from PHP/Symfony upgrade work.
4. Follow the backend upgrade path toward Symfony 7.4 LTS as the long-term framework target.

## Backend upgrade path

Keep each item as its own PR and verify from a clean Docker state before moving on.

1. Prepare the Symfony 4.4 baseline for Symfony 5.4:
   - Keep reducing deprecations until the test suite is clean except for unavoidable vendor notices.
   - Remove remaining app dependencies on Doctrine annotations where practical, while keeping ORM/API-doc migrations separate.
   - Audit legacy bundles with `composer why-not` before changing framework constraints.
2. Upgrade dependency blockers before the next Symfony major bump:
   - Replace or remove packages that do not have a viable Symfony 5.4+ path, especially FOSOAuthServerBundle, FOSUserBundle, FOSRestBundle/JMS/Hateoas/Nelmio API docs, DoctrineBundle/cache, and Swiftmailer integration.
   - Keep each replacement scoped to one subsystem and covered by functional tests.
3. Move Symfony one LTS at a time:
   - Symfony 4.4 -> 5.4 on PHP 7.4.
   - Upgrade Docker PHP to the minimum supported version before Symfony 6.4.
   - Symfony 5.4 -> 6.4.
   - Upgrade Docker PHP to the minimum supported version before Symfony 7.4.
   - Symfony 6.4 -> 7.4.
4. Defer structural modernization until a framework step requires it:
   - Do not migrate the directory layout, frontend toolchain, or auth/API architecture opportunistically.
   - Prefer compatibility shims and small route/config changes over broad rewrites.
5. After each LTS step:
   - Run the full Docker verification flow below.
   - Review abandoned packages and deprecations again.
   - Update this TODO with the new baseline and the next blocker.

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
