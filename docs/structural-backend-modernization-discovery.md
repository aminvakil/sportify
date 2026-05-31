# Structural backend modernization discovery

## Scope

This document inventories legacy Symfony structure that remains after the Symfony 7.4 upgrade. It is planning-only: do not change code from it without confirming a focused milestone first.

## Current structure inventory

| Area | Current state | Why it matters |
| --- | --- | --- |
| Kernel/bootstrap | `app/AppKernel.php` is the main kernel, loaded through Composer classmap. `bin/console`, `web/app.php`, `web/app_dev.php`, `tests/bootstrap.php`, and `phpunit.xml.dist` all reference the legacy app layout. | Future Symfony documentation, recipes, and bundle examples assume a namespaced `src/Kernel.php` and modern bootstrapping. Any kernel move must update all entrypoints together. |
| Autoloading | `composer.json` maps the empty PSR-4 prefix `"": "src/"` and classmaps `app/AppKernel.php`/`app/AppCache.php`. | The empty prefix hides class ownership and allows compatibility shims under `src/Symfony/...`. Replacing it with explicit prefixes is useful, but must preserve current `Devlabs\SportifyBundle\...` classes and shims. |
| App code organization | Application code lives under `src/Devlabs/SportifyBundle`, registered as `DevlabsSportifyBundle`. Current counts: 6 commands, 23 controllers, 22 entities, 19 forms, 19 services, 4 security classes, plus bundle dependency-injection/compiler passes. | Bundle-style app code works, but keeps the project tied to old Symfony conventions. Removing it is a later cleanup because routing, services, validation, and compiler passes currently depend on it. |
| Symfony compatibility shims | `src/Symfony/Component/DependencyInjection/ContainerAwareInterface.php` and `ContainerAwareTrait.php` provide classes removed from modern Symfony. `NotifyCommand` uses them. | These files depend on the empty PSR-4 prefix. Explicit autoloading must either preserve these shims deliberately or remove the last usages first. |
| Configuration layout | Runtime config is in `app/config/*.yml`; routing is in `app/config/routing*.yml`; Doctrine XML mappings are in `app/config/doctrine`; JMS serializer metadata is in `app/config/serializer`; host config remains `app/config/parameters.yml`. | Modern Symfony expects `config/packages`, `config/routes`, and environment-specific config. This is mostly layout debt, but moving it before kernel/autoload changes would touch many boot paths at once. |
| Service configuration | Project services are imported from `@DevlabsSportifyBundle/Resources/config/services.yml`. Controllers are public and container-aware; many services are manually wired and several use `@service_container`. | This is compatible with the current app, but bundle cleanup and modern service loading would be risky until bootstrapping/config layout are stable. |
| Templates/resources | Twig templates and image resources live under `app/Resources/views`; Twig points directly at that path. Bundle resources contain `Resources/config/services.yml` and `validation.yml`. | Template paths are straightforward to move later, but doing so with bundle cleanup would create broad route/controller/template churn. |
| Public document root | Apache, Docker, production build, asset pipeline, README, and init scripts all use `web/`. Front controllers are `web/app.php` and `web/app_dev.php`; assets are built into `web/css` and `web/js`; `assets:install` targets `web`. | Moving to `public/` is high blast-radius because it affects local Docker, production Docker, gulp, generated assets, docs, and web server configuration. |
| Tests/CI | `phpunit.xml.dist` sets `KERNEL_DIR=app/` and `KERNEL_CLASS=AppKernel`. CI copies `app/config/parameters.yml`, runs `bin/console`, and builds frontend assets into `web/`. | Test and CI updates should be part of the same milestone as any kernel, config, or document-root move. |
| Production deployment | `docker/Dockerfile.prod`, `docker/httpd/httpd.prod.conf`, `docker/prod/init.sh`, and `docker-compose.prod.yml` assume `app/`, `web/`, and `bin/console`. | Production behavior must be preserved by compatibility shims or updated in the exact milestone that changes these paths. |

## Upgrade/maintenance impact

High-impact items:

- `web/` document root: affects runtime serving, asset builds, production images, mounted volumes, and documentation.
- Empty PSR-4 autoload prefix: makes class ownership ambiguous and is tied to Symfony compatibility shims.
- Legacy kernel location/class: central bootstrapping point for console, web, tests, cache, and deployment.

Medium-impact items:

- `app/config` layout: mostly path and import debt, but coupled to the kernel and `parameters.yml` handling.
- Bundle-style service/config/resources: works today, but slows future moves toward standard Symfony service discovery.
- Container-aware/public-service style: not a structural blocker by itself, but it increases risk if bundle/service cleanup is attempted too early.

Low-impact/defer items:

- Route names that preserve old FOS-style names are compatibility details and should not be renamed during structural work.
- App-owned business classes can stay under `Devlabs\SportifyBundle` until there is a separate, tested namespace migration plan.

## Recommended first coding milestone

Start with **kernel/autoload modernization**, not config or document-root moves.

Proposed goal:

- Add a conventional namespaced kernel while keeping current paths working.
- Keep `app/AppKernel.php` as a compatibility wrapper if needed by entrypoints, tests, or deployment during the transition.
- Add explicit Composer autoloading for new application classes without breaking existing `Devlabs\SportifyBundle\...` classes or the current Symfony compatibility shims.
- Leave config files, templates, public assets, and the document root in place for this milestone.

Suggested acceptance checks for that PR, using Docker as the source of truth:

- `docker compose run --rm php composer install --no-interaction --no-progress`
- `docker compose run --rm php php bin/console cache:clear --env=test`
- `docker compose run --rm php php bin/console doctrine:schema:validate`
- `docker compose run --rm php vendor/bin/simple-phpunit --testsuite 'Project Test Suite'`
- `curl -fsSI --max-time 10 http://localhost:8000/`

## Later milestones

1. Config layout modernization after kernel/autoloading is stable.
2. `web/` to `public/` migration as its own PR, including Docker, gulp, generated asset paths, production deployment, README, and CI updates.
3. Bundle-structure cleanup only after bootstrapping/config/document-root moves are complete and verified.

## Guardrails

- Keep each PR focused on one coherent structural milestone. Broader internal changes are acceptable when the touched pieces are tightly coupled, but preserve observable runtime behavior unless the milestone explicitly says otherwise.
- Do not combine structural cleanup with dependency upgrades or product changes.
- Prefer temporary compatibility wrappers over broad rewrites.
- Expand tests only for boot/config behavior touched by the specific milestone.
