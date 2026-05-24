#!/bin/sh
set -eu

cd /app

CONSOLE="php bin/console --env=prod --no-debug"

run_console() {
    echo "> $CONSOLE $*"
    $CONSOLE "$@"
}

run_console doctrine:database:create --if-not-exists --no-interaction
run_console doctrine:schema:update --force --no-interaction
run_console assets:install web --symlink --relative
run_console cache:clear --no-warmup
run_console cache:warmup
