#!/bin/bash
set -Eeuox pipefail

cd /app

php bin/console --env=prod --no-debug doctrine:database:create --if-not-exists --no-interaction
php bin/console --env=prod --no-debug doctrine:schema:update --force --no-interaction
php bin/console --env=prod --no-debug assets:install public --symlink --relative
php bin/console --env=prod --no-debug cache:clear --no-warmup
php bin/console --env=prod --no-debug cache:warmup
