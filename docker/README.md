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
