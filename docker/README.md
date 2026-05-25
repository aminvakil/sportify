# Docker development baseline

This setup is intentionally old: it is for reproducing the legacy PHP 7.0/Gulp stack while upgrading it incrementally.

## First run

```sh
docker compose down -v
cp docker/symfony/parameters.yml app/config/parameters.yml
docker compose build
docker compose run --rm php composer install --no-interaction --no-progress
docker compose run --rm node npm install
docker compose run --rm node gulp
docker compose run --rm php vendor/bin/simple-phpunit --testsuite 'Project Test Suite'
docker compose up php
```

Open <http://localhost:8000/app_dev.php>.

## Useful checks

Reset Docker state before validating an upgrade step:

```sh
docker compose down -v
```

Then run smoke checks:

```sh
docker compose run --rm php composer install --no-interaction --no-progress
docker compose run --rm php php bin/console cache:clear --env=test
docker compose run --rm php php bin/console doctrine:database:create --if-not-exists
docker compose run --rm php php bin/console doctrine:schema:validate --skip-sync
docker compose run --rm php php bin/console doctrine:schema:update --force
docker compose run --rm php vendor/bin/simple-phpunit --testsuite 'Project Test Suite'
```

The upgrade milestone is to keep these smoke checks passing after each step.

## Production stack

`docker-compose.prod.yml` is a separate stack intended for deployments. It builds two
runtime images from `docker/Dockerfile.prod` — `php` (php-fpm) and `httpd` — with the
application source, vendor, and built frontend assets baked in. There is no bind
mount and no Node/Composer in the runtime images.

Before building, create a production `app/config/parameters.yml` on the host
(this file is gitignored) and edit it for the deployment:

```sh
cp app/config/parameters.yml.dist app/config/parameters.yml
vim app/config/parameters.yml
```

Docker Compose infrastructure settings live in `.env` (also gitignored). Start
from the example file and replace the placeholder secrets before deploying. Keep
the MySQL values in `.env` in sync with the database values in
`app/config/parameters.yml`.

```sh
cp .env.example .env
vim .env

docker compose -f docker-compose.prod.yml build
docker compose -f docker-compose.prod.yml up -d
```

On startup, the `init` service waits for MySQL, creates the configured database if
needed, updates the schema, installs bundle assets into a shared `web/bundles`
volume, and clears/warms the shared prod cache before `php` and `httpd` start. To
rerun that idempotent initialization after a deployment or parameter change:

```sh
docker compose -f docker-compose.prod.yml run --rm init
```

Create the first admin account after the schema is initialized:

```sh
docker compose -f docker-compose.prod.yml run --rm php php bin/console --env=prod --no-debug sportify:user:create-admin admin@example.com admin --password='change-me'
```

Omit `--password` to enter it interactively. The command refuses to run after an admin user already exists.
