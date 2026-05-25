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
- Minimal frontend smoke coverage exists through `npm test`.
- Docker Node runtime has been upgraded from Node 6 / npm 3 to Node 26 / npm 11.
- Bower has been removed; frontend dependencies now install through npm.
- Gulp 3 and Laravel Elixir have been replaced with a plain Gulp 4 build.
- `node-sass` has been replaced with Dart Sass via `gulp-sass` 5.
- A production Docker Compose stack (`docker-compose.prod.yml`) is separate from the dev stack, with a php-fpm + httpd runtime built from `docker/Dockerfile.prod`.
- Production app configuration remains host-provided through `app/config/parameters.yml`; production Compose infrastructure settings are documented in `.env.example`.
- Docker MySQL has been upgraded from 5.7 to 9.7, with explicit utf8mb4 server and Doctrine table defaults. Schema validation, reserved-word checks, SQL mode, and charset/collation checks are clean against MySQL 9.7.
- Production Compose includes an idempotent `init` service that waits for the database, creates/updates schema, installs bundle assets, and clears/warms prod cache before app startup.
- First admin creation is handled by the app-owned `sportify:user:create-admin` command.

## Next steps

Backend infrastructure (PHP runtime, Symfony, Doctrine, MySQL) is good enough for now. The active frontend runtime modernization step is complete.

## PR sizing strategy

Use bigger PRs, but keep them coherent:

- Good larger PR: "remove a Doctrine blocker with mapping coverage" or "stabilize Symfony 7.4 by fixing one coherent group of deprecations/config breaks".
- Bad larger PR: mixing Symfony upgrades, frontend build modernization, broad directory layout changes, and unrelated cleanup.
- Every large PR should include or expand tests for the flows it changes.
- Prefer one PR per milestone below, not one PR per deprecation notice or one PR per small config line.
- If a milestone uncovers unrelated work, note it in TODO instead of expanding scope indefinitely.

## Frontend modernization path

Complete for now. Keep the completed steps in "Current status" above so the upgrade path remains visible.

## Optional scoring rules

- Add an opt-in scoring mode for predictions that awards separate points for correct goal difference when the predicted score differs from the real score by exactly one goal for each team. Example personal rule: outcome = 4 points, exact score = 10 points, matching goal difference with exactly one-goal offset = 6 points. Keep the legacy scoring behavior as the default unless explicitly enabled.
- Evaluate replacing exact-point prediction scoring with an upstream betting/probability API. Each game prediction would score based on the probability percentage of the chosen outcome, so correctly betting on less likely outcomes can be rewarded differently than high-probability outcomes.

## Deployment path

Separate the deployment stack from the local development stack. Keep `docker-compose.yml` focused on local testing, and add deployment-specific Docker files/Compose configuration so production is easier to operate.

### Backend deployment tasks

1. Add an app-owned scheduled command that sends users' predictions to the configured Telegram chat shortly after each match starts, without hardcoded secrets. This is separate from the existing Telegram result notification sent after matches end and scores are updated.
2. Document required env vars, first deployment, upgrades, scheduled commands, and smoke checks.

### Frontend deployment tasks

1. Build frontend assets during image build or deployment, not manually on the server.
2. Ensure built assets are available in `web/` for the runtime httpd container.
3. Keep Node/npm out of the final runtime image unless needed.

## Deferred backend infrastructure path

Defer structural modernization until a framework step requires it:

- Prefer compatibility shims and focused route/config changes over broad rewrites unless a milestone explicitly calls for a replacement.

## Always verify each step

See the "Verification rule" section of `AGENTS.md`. Note: CI also replaces `football_api.token` with the `FOOTBALL_DATA_API_TOKEN` secret before running.
