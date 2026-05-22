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

Before building, place a production `app/config/parameters.yml` on the host (this file
is gitignored). Making this env-driven is a separate, follow-up step on the deployment
track.

Database credentials and the host port come from environment variables; the stack
will fail to start if they are missing:

```sh
export MYSQL_DATABASE=sportify
export MYSQL_USER=sportify
export MYSQL_PASSWORD=...
export MYSQL_ROOT_PASSWORD=...
export HTTP_PORT=8080

docker compose -f docker-compose.prod.yml build
docker compose -f docker-compose.prod.yml up -d
```

Initial schema creation, prod cache warm, and operational documentation are tracked
as follow-up tasks in `TODO.md`.
