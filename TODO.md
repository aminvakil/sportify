# TODO

## Current status

Pending tasks: 1.

## Pending

- Fix misleading Telegram result-update notifications: `sportify:data:update matches-results` currently sends “Match results and standings updated” whenever fixture import reports `total_updated > 0`, even if `ScoreUpdater` finds no scored predictions or standings changes (for example finished matches with zero predictions). Only send a result/standings Telegram message when scoring/standings actually changed, or make the no-scoring case explicit.

## Baseline

- Docker development setup is verified. `httpd` serves `public/` static files and proxies dynamic requests to PHP.
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
- Upgraded Docker MySQL from 5.7 to 9.7 with explicit `utf8mb4` / `utf8mb4_unicode_ci` defaults. Schema validation, reserved-word checks, SQL mode, and charset/collation checks are clean.
- Moved the public document root from `web/` to `public/` across Docker, httpd, frontend asset output, tests, and deployment docs.
- Completed structural backend modernization milestones for discovery, kernel/autoload, config layout, and public document-root modernization.
- Made production timezone configuration deployment-variable driven through `APP_TIMEZONE`.

## Deployment status

- Production persistent data uses external Docker volumes for MySQL data and uploaded team/tournament logos, with logo volumes writable in PHP and read-only in httpd.
- Production Compose is separate from the dev stack via `docker-compose.prod.yml` and `docker/Dockerfile.prod`.
- Production runtime uses php-fpm + httpd. Node/npm are used only in the asset build stage and are not included in final runtime images.
- Production app configuration remains host-provided through `app/config/parameters.yml`; infrastructure settings are documented in `.env.example`.
- Production includes an idempotent `init` service that waits for the database, creates/updates schema, installs bundle assets, and clears/warms prod cache before app startup.
- Production timezone is configured with `APP_TIMEZONE`, passed to containers as `TZ`, and reused by PHP for `date.timezone`.
- First admin creation uses `sportify:user:create-admin`; regular user creation for deployments without SMTP uses `sportify:user:create`; password resets without SMTP use `sportify:user:reset-password`.
- Deployment documentation covers required local config files, first deployment, upgrades, scheduled commands, and smoke checks.

## App feature status

- Admin panel Data Updates shows the no-updates flash message without the removed `session` service.
- Admin panel Matches loads when no tournaments exist and when a selected tournament has no teams.
- Admin panel Scoring defaults has a cleaner centered form layout.
- Submitted predictions can be sent to the configured Telegram chat shortly after kickoff with `sportify:telegram:send-predictions`.
- Data update Telegram notifications use the app-owned Telegram service/config instead of legacy hardcoded send/pin URLs. Sent messages are pinned by default and can be disabled with `telegram.pin_messages: false`.
- Probability-weighted scoring v1 is implemented with match scoring snapshots, stored prediction scoring breakdowns, exact-prediction percentages based on scored results, and floor-based underdog bonus rounding.
- Rules page documents World Cup 2026 knockout-stage base scoring recommendations and champion prediction scoring is 15 points.
- Football-Data integration uses v4, including the v4 teams/fixtures parser fields.
- Upcoming fixture import uses `football-data.org` plus The Odds API snapshots and skips fixtures when complete odds are unavailable.
- The prediction page shows probability snapshots, probability bonus chips, and points available for each outcome.
- Telegram fixture-added, prediction, and result/scoring messages include probability and scoring details.
- Telegram prediction messages show prediction bonus points instead of redundant outcome labels, and multi-match prediction notifications are covered by tests.
- Admin Data Updates handles non-200 Football-Data responses with a flash message instead of a Symfony 500.
- External API failures and unavailable odds snapshots notify the configured Telegram admin chat in prod.
- FIFA World Cup logo import/display on `/matches` is fixed.
- Public web registration is configurable with `APP_PUBLIC_REGISTRATION_ENABLED` and defaults to enabled.
- `sportify:user:create-admin` reports `Password cannot be blank.` instead of crashing when its interactive password prompt is submitted blank.
- Shared filter forms handle submitted `filter[...]` arrays without Symfony 7 `InputBag::get()` scalar access errors.
- Prediction forms reuse an existing user/match prediction on stale or duplicate BET submits instead of triggering a duplicate-key 500.
- Fixture odds imports keep Football-Data teams canonical, auto-learn reusable The Odds API team-name mappings per tournament/provider from safe Football-Data metadata plus kickoff/home-away constraints, and notify the admin when provider team mappings are missing or ambiguous.

## Notes

- For future verification, see the "Verification rule" section of `AGENTS.md`.
