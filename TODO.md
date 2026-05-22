# TODO

## Current status

### Backend

- Docker development stack exists and has been verified with httpd serving `web/` and proxying dynamic requests to PHP.
- Runtime baseline is Symfony 7.4 LTS on Docker PHP 8.5, with explicit Symfony component packages instead of the unsupported `symfony/symfony` meta-package.
- Composer dependencies have been reviewed and upgraded within current constraints; known abandoned direct blockers have been removed.
- Legacy Symfony bundles/components removed or replaced: SensioDistributionBundle, SensioFrameworkExtraBundle, FOSUserBundle, FOSOAuthServerBundle, FOSRestBundle, NelmioApiDocBundle, Swiftmailer, Doctrine annotations/cache/reflection.
- App-owned auth, password reset, mailer, OAuth token, and API JSON response flows replace removed bundles.
- Doctrine stack is upgraded to DoctrineBundle 2.18, ORM 3.6, DBAL 3.10, Persistence 3.4, and Event Manager 2.1.
- Doctrine mappings live in XML under `app/config/doctrine`; routes and validation constraints live in YAML.
- Test coverage exists for the main tournament/prediction/scoring workflow, auth flows, frontend smoke checks, and CI.
- `phpunit.xml.dist` is migrated to PHPUnit 9.6 schema; Symfony 7.4 app/direct deprecation checks are clean. Remaining known deprecations are indirect Doctrine DBAL vendor notices.

### Frontend

- Docker Node runtime is upgraded to Node 26 / npm 11.
- Bower has been removed; frontend dependencies install through npm.
- Gulp 3 and Laravel Elixir have been replaced with a plain Gulp 4 build.
- `node-sass` has been replaced with Dart Sass via `gulp-sass` 5.

## Next steps

Backend infrastructure (PHP runtime, Symfony, Doctrine) and frontend runtime modernization are good enough for now. Next focus should be deployment ergonomics, then the deferred MySQL runtime upgrades.

## PR sizing strategy

Use bigger PRs, but keep them coherent:

- Good larger PR: "add production Docker deployment stack" or "upgrade Docker MySQL from 5.7 to 8 with schema compatibility checks".
- Bad larger PR: mixing deployment stack work, MySQL upgrades, Symfony upgrades, and unrelated cleanup.
- Every large PR should include or expand tests/checks for the flows it changes.
- Prefer one PR per milestone below.
- If a milestone uncovers unrelated work, note it here instead of expanding scope indefinitely.

## Deployment path

Create a deployment-oriented Docker stack separate from the local development stack. Keep the current `docker-compose.yml` optimized for local testing, and add production/deployment files with explicit environment-driven configuration.

### Backend deployment tasks

1. Add a production Docker Compose stack separate from the dev stack, for example `docker-compose.prod.yml` plus production-specific Dockerfiles or targets if needed.
2. Move deployment configuration to environment variables / `.env` examples instead of requiring manual edits to `app/config/parameters.yml`.
3. Add an app entrypoint or one-shot init service that performs first-deployment setup automatically and idempotently:
   - install/use production Composer dependencies from the image or a build stage;
   - generate or render required Symfony parameters/config from env;
   - wait for the database;
   - create the database if missing;
   - apply schema setup/update or migrations;
   - clear/warm prod cache.
4. Decide how first admin creation should work now that FOSUserBundle is gone:
   - preferred: app-owned console command driven by env for first deployment;
   - alternative: document a one-off `docker compose run` command.
5. Add deployment docs covering required env vars, first deploy, upgrades, backup/restore, and rollback expectations.
6. Add scheduled task support for existing production commands:
   - `sportify:data:update matches-fixtures <days>`;
   - `sportify:data:update matches-results <days>`;
   - `sportify:notify users-not-predicted`.
7. Add a production health check for httpd/PHP and a minimal smoke check documented for deploy verification.

### Frontend deployment tasks

1. Build frontend assets during the production image build, not at container startup.
2. Ensure built CSS/JS/fonts/images are copied into the runtime image and served from `web/`.
3. Keep npm/node tooling out of the final runtime image unless it is explicitly needed.
4. Document how asset rebuilds happen during deployment.

## Deferred backend infrastructure path

Pick this back up only after deployment basics are in place. Keep each milestone as its own PR.

1. Upgrade Docker MySQL from 5.7 to 8 in a focused PR.
   - Explicitly check schema compatibility, reserved words, SQL modes, charset/collation behavior, and Doctrine schema validation output.
2. Upgrade Docker MySQL from 8 to 9 in a separate focused PR after the MySQL 8 runtime is stable.
   - Re-check schema compatibility, reserved words, SQL modes, charset/collation behavior, and Doctrine schema validation output against MySQL 9.
3. Defer structural modernization until a framework step requires it.
   - Prefer compatibility shims and focused route/config changes over broad rewrites unless a milestone explicitly calls for a replacement.

## Always verify each step

See the "Verification rule" section of `AGENTS.md`. Note: CI also replaces `football_api.token` with the `FOOTBALL_DATA_API_TOKEN` secret before running.
