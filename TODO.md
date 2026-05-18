# TODO

## Current status

- Docker development setup exists and has been verified.
- Symfony has been upgraded to 3.4 LTS.
- Basic integration workflow test exists for tournaments, users, predictions, scoring, standings, and helper/repository calls.
- GitHub Actions CI workflow has been committed and pushed.
- Symfony deprecation notices have been reduced to the remaining vendor-level batch.

## Next steps

1. Bump Docker PHP from 7.0 to 7.1.
   - Run full clean Docker verification.
   - Fix only PHP 7.1-related failures.
2. Repeat for PHP 7.2, 7.3, and 7.4.
3. After PHP 7.4 is stable, review Composer package constraints for stale/abandoned packages.
4. Keep frontend upgrade work separate from PHP/Symfony upgrade work.

## Always verify each step

```sh
docker compose down -v
cp docker/symfony/parameters.yml app/config/parameters.yml
docker compose build
docker compose run --rm php composer install --no-interaction --no-progress
docker compose run --rm node npm install
docker compose run --rm node bower install
docker compose run --rm node gulp
docker compose run --rm php php bin/console cache:clear --env=test
docker compose run --rm php php bin/console doctrine:database:create --if-not-exists
docker compose run --rm php php bin/console doctrine:schema:validate --skip-sync
docker compose run --rm php php bin/console doctrine:schema:update --force
docker compose run --rm php vendor/bin/simple-phpunit --testsuite 'Project Test Suite'
docker compose up -d php
curl -I --max-time 10 http://localhost:8000/app_dev.php/
docker compose down -v
```
