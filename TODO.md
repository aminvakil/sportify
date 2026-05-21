# TODO

## Current status

- Docker development setup exists and has been verified.
- Docker httpd service serves `web/` static files and proxies dynamic requests to PHP.
- Symfony has been upgraded to 7.4 LTS.
- Docker PHP has been upgraded incrementally from 7.0 to 8.5.
- Composer dependencies have been updated to latest versions within existing constraints.
- SensioDistributionBundle, its Composer script handlers, the generated requirements/config checker flow, and transitive sensiolabs/security-checker have been removed.
- Basic integration workflow test exists for tournaments, users, predictions, scoring, standings, and helper/repository calls.
- Functional auth coverage exists for login page CSRF, successful registration/login, failed login, logout, duplicate registration, registration validation, password reset, and profile password changes.
- GitHub Actions CI workflow is in place and green on main.
- `symfony/monolog-bundle` has been upgraded to 3.10.
- Swiftmailer and `symfony/swiftmailer-bundle` have been replaced with Symfony Mailer for registration and password reset email delivery.
- `jms/serializer-bundle` has been upgraded to 5.5, `jms/serializer` to 3.32, and `willdurand/hateoas-bundle` to 2.6, removing the old `doctrine/common ~2` constraint from Hateoas.
- `symfony/phpunit-bridge` has been upgraded to 7.4, with PHPUnit 9.6 pinned for the legacy test suite.
- Composer package constraints have been reviewed for the current Symfony 7.4/PHP 8.5 baseline; unused `sensio/generator-bundle` was removed and `doctrine/doctrine-cache-bundle` is no longer a direct dependency.
- `doctrine/doctrine-bundle` has been upgraded to 2.18, Doctrine ORM to 3.6, Doctrine DBAL to 3.10, Doctrine Persistence to 3.4, Doctrine Event Manager to 2.1, and `doctrine/doctrine-cache-bundle`/`doctrine/cache`/`doctrine/reflection` have been removed.
- Short `DevlabsSportifyBundle:Entity` aliases in app code have been replaced with FQCN/`::class`, and remaining short aliases live only in vendor bridges.
- SensioFrameworkExtraBundle has been removed; former admin-only security annotations are explicit controller checks.
- Web controller routes have been moved from annotations to YAML routing.
- App validation constraints have been moved from annotations to YAML, and Symfony validator annotation loading is disabled.
- The leftover `Team` unique-entity validation annotation has been moved to YAML.
- The app bootstrap no longer manually registers Doctrine's annotation autoloader.
- `doctrine/annotations` has been removed.
- Current email usage is registration/password reset through Symfony Mailer.
- Doctrine ORM mappings have been moved from annotations to XML files in `app/config/doctrine`.
- Current abandoned packages in `composer.lock`: none known.
- FOSUserBundle has been removed; login, logout, registration, and password reset now use Symfony Security with the app `User` entity/provider/checker.
- FOSOAuthServerBundle has been removed; password-grant token issuance and API access-token authentication now use small app-owned services/controllers against the existing OAuth tables.
- FOSRestBundle and NelmioApiDocBundle have been removed; API routes are explicit YAML routes and API JSON responses are serialized directly with JMS Serializer.
- The unsupported `symfony/symfony` meta-package has been replaced with explicit Symfony component packages pinned to 7.4.*.
- `phpunit.xml.dist` has been migrated to the PHPUnit 9.6 schema.
- Symfony 7.4 is installed and locked; deprecation re-check is clean for self and direct notices. The remaining 403 indirect notices are a single vendor deprecation (`Subscribing to onSchemaCreateTable events is deprecated`, doctrine/dbal) that needs a future DBAL major upgrade and is not actionable from app code.

## Next steps

Backend infrastructure (PHP runtime, Symfony, Doctrine) is good enough for now. The active track is frontend modernization (see "Frontend modernization path" below): Node 6 / Bower / Gulp 3 are far past EOL and are the most painful remaining baseline. Remaining backend infrastructure upgrades (MySQL 5.7 → 8 → 9) are deferred until the frontend track is complete.

## PR sizing strategy

Use bigger PRs, but keep them coherent:

- Good larger PR: "remove a Doctrine blocker with mapping coverage" or "stabilize Symfony 7.4 by fixing one coherent group of deprecations/config breaks".
- Bad larger PR: mixing Symfony upgrades, frontend build modernization, broad directory layout changes, and unrelated cleanup.
- Every large PR should include or expand tests for the flows it changes.
- Prefer one PR per milestone below, not one PR per deprecation notice or one PR per small config line.
- If a milestone uncovers unrelated work, note it in TODO instead of expanding scope indefinitely.

## Frontend modernization path

This is the active track. Keep each step as its own PR.

1. Add a frontend test suite before changing the frontend toolchain:
   - The repo currently has no automated frontend tests. Pick a minimal runner that fits the existing Gulp/Bower setup (or the chosen replacement) and add smoke-level coverage for the assets the app actually ships (`web/css/style.css`, `web/js/all-scripts.js`, and the templates that load them).
   - Until this exists, frontend changes can only be verified manually in a browser; CI cannot gate them.
2. Upgrade the Node/npm runtime in Docker in a focused PR.
3. Replace Bower with an npm-based dependency flow in a focused PR.
4. Replace Gulp 3 with a current build setup in a focused PR.

## Deferred backend infrastructure path

Pick this back up only after the frontend modernization path is complete. Keep each milestone as its own PR.

1. Infrastructure runtime upgrades:
   - Upgrade Docker MySQL from 5.7 to 8 in a focused PR.
   - For MySQL 8, explicitly check schema compatibility, reserved words, SQL modes, charset/collation behavior, and Doctrine schema validation output.
   - Upgrade Docker MySQL from 8 to 9 in a separate focused PR after the MySQL 8 runtime is stable. Re-check schema compatibility, reserved words, SQL modes, charset/collation behavior, and Doctrine schema validation output against MySQL 9.
2. Defer structural modernization until a framework step requires it:
   - Prefer compatibility shims and focused route/config changes over broad rewrites unless a milestone explicitly calls for a replacement.

## Always verify each step

See the "Verification rule" section of `AGENTS.md`. Note: CI also replaces `football_api.token` with the `FOOTBALL_DATA_API_TOKEN` secret before running.
