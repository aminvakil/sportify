# Production deployment

This guide describes the Docker Compose production stack in `docker-compose.prod.yml`.
The stack builds immutable `php` and `httpd` runtime images from `docker/Dockerfile.prod` and stores production database/upload data in required external Docker volumes.

## Prerequisites

On the deployment host you need:

- Docker with the Compose plugin.
- A checkout of this repository.
- A DNS name or reverse proxy that points to the host/port you expose with `HTTP_PORT`.
- Production secrets for the database, Symfony secret, SMTP, and football-data API.

Do not commit production `app/config/parameters.yml`, `.env`, database dumps, or generated runtime files.

## Configuration files

Production uses two local, gitignored files:

1. `.env` for Docker Compose infrastructure settings.
2. `app/config/parameters.yml` for Symfony application settings.

Create them on the deployment host before building images:

```sh
cp .env.example .env
cp app/config/parameters.yml.dist app/config/parameters.yml
```

### `.env`

Set the exposed HTTP port, timezone, registration flag, and MySQL bootstrap credentials:

```dotenv
HTTP_PORT=8080
APP_TIMEZONE=UTC
APP_PUBLIC_REGISTRATION_ENABLED=true
MYSQL_DATABASE=sportify
MYSQL_USER=sportify
MYSQL_PASSWORD=replace-with-a-secret
MYSQL_ROOT_PASSWORD=replace-with-a-secret
```

Set `APP_TIMEZONE` to the deployment's IANA timezone, for example `Europe/Amsterdam`. Docker Compose passes it to production containers as `TZ`, and PHP uses the same value for `date.timezone`.

Set `APP_PUBLIC_REGISTRATION_ENABLED=false` to disable public web registration while keeping command-line user creation available.

Keep `MYSQL_DATABASE`, `MYSQL_USER`, and `MYSQL_PASSWORD` in sync with the matching database values in `app/config/parameters.yml`.

### `app/config/parameters.yml`

At minimum, set:

- `database_host: db`
- `database_port: 3306`
- `database_name`, `database_user`, `database_password` matching `.env`
- `secret` to a unique random value, for example from `openssl rand -hex 20`
- `mailer_dsn` and `mailer_sender_address` for registration/password reset emails
- `football_api.token` from football-data.org if API fixture/result imports are used
- `odds_api.token` from The Odds API if upcoming fixture imports should add probability snapshots
- `slack.url` and `slack.channel` if Slack notifications are used
- `telegram.bot_token` and `telegram.chat_id` if Telegram notifications are used
- `telegram.admin_chat_id` if admin-only external API failure alerts are used
- `sportify_api.client_id` and `sportify_api.client_secret` only if the API token flow is used

`app/config/parameters.yml` is copied into the production image at build time. Rebuild and redeploy the images after changing it.

## First deployment

Create the required external volumes before starting the stack:

```sh
docker volume create sportify-prod-db-data
docker volume create sportify-prod-team-logos
docker volume create sportify-prod-tournament-logos
```

Build and start the production stack:

```sh
docker compose -f docker-compose.prod.yml build
docker compose -f docker-compose.prod.yml up -d
```

The `init` service runs before `php` and `httpd`. It waits for MySQL, creates the configured database if needed, updates the schema, installs bundle assets into the shared `public/bundles` volume, and clears/warms the prod cache. Uploaded team and tournament logos are stored in external volumes; PHP mounts them writable and httpd mounts them read-only.

Check the stack:

```sh
docker compose -f docker-compose.prod.yml ps
docker compose -f docker-compose.prod.yml logs --tail=100 init php httpd
HTTP_PORT=$(grep '^HTTP_PORT=' .env | cut -d= -f2-)
curl -fsSI --max-time 10 http://localhost:${HTTP_PORT:-8080}/
```

Create the first admin account after initialization finishes:

```sh
docker compose -f docker-compose.prod.yml run --rm php php bin/console --env=prod --no-debug sportify:user:create-admin admin@example.com admin --password='change-me'
```

Omit `--password` to enter it interactively. The command refuses to run if an admin already exists.

If SMTP is not ready yet, create enabled regular users or reset passwords from the command line:

```sh
docker compose -f docker-compose.prod.yml run --rm php php bin/console --env=prod --no-debug sportify:user:create user@example.com username --password='change-me'
docker compose -f docker-compose.prod.yml run --rm php php bin/console --env=prod --no-debug sportify:user:reset-password user@example.com --password='new-password'
```

## Initial application setup

In the admin UI:

1. Create at least one tournament.
2. Add teams and matches manually, or create API mappings and import them from football-data.org.
3. If using API imports, confirm `football_api.token` and `odds_api.token` are configured before running import commands.

For API clients, create an OAuth client and store the printed public id/secret securely:

```sh
docker compose -f docker-compose.prod.yml run --rm php php bin/console --env=prod --no-debug oauth:client:create client-name https://example.com/callback password
```

## Scheduled commands

Run scheduled commands from the deployment checkout so Compose can find `.env` and `docker-compose.prod.yml`. These examples use `exec -T` against the running `php` service. Set `SPORTIFY_DIR` to that checkout path.

A ready-to-edit crontab template is available at `deploy/cron/sportify.crontab`. Merge those entries into the deployment host's crontab, or install the file directly if the host has no existing crontab:

```sh
crontab deploy/cron/sportify.crontab
```

It includes these jobs:

```cron
# Fetch match fixtures for the next 7 days every morning.
SPORTIFY_DIR=/path/to/sportify

15 6 * * * cd "$SPORTIFY_DIR" && docker compose -f docker-compose.prod.yml exec -T php php bin/console --env=prod --no-debug sportify:data:update matches-fixtures 7 >> /var/log/sportify-data-updates.log 2>&1

# Send submitted predictions to the configured Telegram group after kickoff.
5,35 * * * * cd "$SPORTIFY_DIR" && docker compose -f docker-compose.prod.yml exec -T php php bin/console --env=prod --no-debug sportify:telegram:send-predictions >> /var/log/sportify-telegram.log 2>&1

# Check recently ended matches and update scores/standings when results arrive.
*/10 * * * * cd "$SPORTIFY_DIR" && docker compose -f docker-compose.prod.yml exec -T php php bin/console --env=prod --no-debug sportify:data:update matches-results 1 >> /var/log/sportify-data-updates.log 2>&1
```

Notify users who have not predicted matches starting soon, if you want Slack reminders too:

```cron
5,35 * * * * cd "$SPORTIFY_DIR" && docker compose -f docker-compose.prod.yml exec -T php php bin/console --env=prod --no-debug sportify:notify users-not-predicted >> /var/log/sportify-notify.log 2>&1
```

Adjust `SPORTIFY_DIR`, schedule frequency, and log paths for your host.

## Upgrades and redeploys

For a new application revision, first make sure `.env` contains `APP_TIMEZONE` set to the intended application timezone, especially when upgrading from older deployments that followed the host `/etc/localtime` mount.

```sh
git fetch --all --prune
git checkout <release-branch-or-tag>
git pull --ff-only
docker compose -f docker-compose.prod.yml build
docker compose -f docker-compose.prod.yml up -d
```

The `init` service reruns during `up -d` when its image changed. To rerun the idempotent initialization manually:

```sh
docker compose -f docker-compose.prod.yml run --rm init
```

Before upgrades that may change schema or data, take a database backup using your normal backup process. `docker compose -f docker-compose.prod.yml down -v` removes only Compose-managed cache/bundle-asset volumes; the external database and logo volumes remain until an admin explicitly removes them with `docker volume rm`.

## Smoke checks

After each deployment, verify:

```sh
docker compose -f docker-compose.prod.yml ps
HTTP_PORT=$(grep '^HTTP_PORT=' .env | cut -d= -f2-)
curl -fsSI --max-time 10 http://localhost:${HTTP_PORT:-8080}/
curl -fsSI --max-time 10 http://localhost:${HTTP_PORT:-8080}/css/style.css
curl -fsSI --max-time 10 http://localhost:${HTTP_PORT:-8080}/js/all-scripts.js
docker compose -f docker-compose.prod.yml exec -T php php bin/console --env=prod --no-debug doctrine:schema:validate
```

Then sign in through the browser, open the admin panel, and confirm the active tournament, predictions, standings, and scheduled-command logs look correct.
