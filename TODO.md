# TODO

## Current baseline

- Docker development setup is verified. `httpd` serves `web/` static files and proxies dynamic requests to PHP.
- Runtime baseline: Symfony 7.4 LTS, PHP 8.5, Composer 2.2.x, MySQL 9.7, Apache httpd 2.4, Node 26/npm 11, and Gulp 4.
- Composer dependencies are current within the existing constraints. Current abandoned packages in `composer.lock`: none known.
- GitHub Actions CI is in place and green on `main`.
- Test coverage includes the basic tournament/user/prediction/scoring workflow, helper/repository calls, functional auth flows, and minimal frontend smoke coverage through `npm test`.

## Completed modernization

- Removed legacy Symfony/Doctrine blockers: SensioDistributionBundle, SensioFrameworkExtraBundle, `doctrine/annotations`, Doctrine cache/reflection packages, FOSUserBundle, FOSOAuthServerBundle, FOSRestBundle, NelmioApiDocBundle, Swiftmailer, and the unsupported `symfony/symfony` meta-package.
- Upgraded core backend dependencies: Doctrine Bundle/ORM/DBAL/Persistence/Event Manager, JMS Serializer/Bundle, Hateoas Bundle, Monolog Bundle, and PHPUnit Bridge.
- Replaced annotation-based routing, validation, and Doctrine ORM mappings with YAML/XML configuration.
- Replaced FOSUser/FOSOAuth behavior with app-owned Symfony Security registration/login/password-reset flows and token issuance/authentication against the existing OAuth tables.
- Replaced Swiftmailer email delivery with Symfony Mailer for registration and password reset.
- Migrated `phpunit.xml.dist` to the PHPUnit 9.6 schema.
- Symfony 7.4 deprecation re-check is clean for self and direct notices. The remaining indirect notices are from Doctrine DBAL schema events and require a future DBAL major upgrade.
- Modernized frontend tooling from Node 6/npm 3/Bower/Gulp 3/Laravel Elixir/`node-sass` to Node 26/npm 11, npm-managed dependencies, Gulp 4, and Dart Sass.
- Upgraded Docker MySQL from 5.7 to 9.7 with explicit utf8mb4 defaults. Schema validation, reserved-word checks, SQL mode, and charset/collation checks are clean.

## Deployment status

- Production Compose is separate from the dev stack via `docker-compose.prod.yml` and `docker/Dockerfile.prod`.
- Production runtime uses php-fpm + httpd. Node/npm are used only in the asset build stage and are not included in final runtime images.
- Production app configuration remains host-provided through `app/config/parameters.yml`; infrastructure settings are documented in `.env.example`.
- Production includes an idempotent `init` service that waits for the database, creates/updates schema, installs bundle assets, and clears/warms prod cache before app startup.
- First admin creation uses `sportify:user:create-admin`; regular user creation for deployments without SMTP uses `sportify:user:create`.
- Deployment documentation covers required local config files, first deployment, upgrades, scheduled commands, and smoke checks.

## App feature status

- Admin panel Data Updates shows the no-updates flash message without the removed `session` service.
- Submitted predictions can be sent to the configured Telegram chat shortly after kickoff with `sportify:telegram:send-predictions`.
- Data update Telegram notifications use the app-owned Telegram service/config instead of legacy hardcoded send/pin URLs. Sent messages are pinned by default and can be disabled with `telegram.pin_messages: false`.

## Next steps

No required backend, frontend, or deployment infrastructure modernization is currently pending.

Optional product work:

- Add an opt-in scoring mode for predictions that awards separate points for correct goal difference when the predicted score differs from the real score by exactly one goal for each team. Example personal rule: outcome = 4 points, exact score = 10 points, matching goal difference with exactly one-goal offset = 6 points. Keep legacy scoring as the default unless explicitly enabled.
- Evaluate replacing exact-point prediction scoring with an upstream betting/probability API. Each game prediction would score based on the probability percentage of the chosen outcome, so correctly betting on less likely outcomes can be rewarded differently than high-probability outcomes.

Deferred infrastructure work:

- Defer structural backend modernization until a framework step requires it.
- Prefer compatibility shims and focused route/config changes over broad rewrites unless a milestone explicitly calls for a replacement.

## PR sizing strategy

Use bigger PRs, but keep them coherent:

- Good larger PR: "remove a Doctrine blocker with mapping coverage" or "stabilize Symfony 7.4 by fixing one coherent group of deprecations/config breaks".
- Bad larger PR: mixing Symfony upgrades, frontend build modernization, broad directory layout changes, and unrelated cleanup.
- Every large PR should include or expand tests for the flows it changes.
- Prefer one PR per milestone, not one PR per deprecation notice or one PR per small config line.
- If a milestone uncovers unrelated work, note it in TODO instead of expanding scope indefinitely.

## Verification

See the "Verification rule" section of `AGENTS.md`. CI also replaces `football_api.token` with the `FOOTBALL_DATA_API_TOKEN` secret before running.
