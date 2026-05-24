#!/bin/sh
set -eu

cd /app

CONSOLE="php bin/console --env=prod --no-debug"

run_console() {
    echo "> $CONSOLE $*"
    $CONSOLE "$@"
}

wait_for_database() {
    while true; do
        if $CONSOLE doctrine:database:create --if-not-exists --no-interaction >/tmp/sportify-prod-db-wait.log 2>&1; then
            rm -f /tmp/sportify-prod-db-wait.log
            return 0
        fi

        echo "Waiting for database..." >&2
        sleep 2
    done
}

wait_for_database
run_console doctrine:schema:update --force --no-interaction
run_console assets:install web --symlink --relative
run_console cache:clear --no-warmup
run_console cache:warmup
