#!/bin/sh
set -eu

cd /app

CONSOLE="php bin/console --env=prod --no-debug"
DB_WAIT_SECONDS=120
DB_WAIT_INTERVAL_SECONDS=2
DB_WAIT_LOG="/tmp/sportify-prod-db-wait.log"

run_console() {
    echo "> $CONSOLE $*"
    $CONSOLE "$@"
}

wait_for_database() {
    elapsed=0

    while true; do
        if $CONSOLE doctrine:database:create --if-not-exists --no-interaction >"$DB_WAIT_LOG" 2>&1; then
            rm -f "$DB_WAIT_LOG"
            return 0
        fi

        if [ "$elapsed" -ge "$DB_WAIT_SECONDS" ]; then
            echo "Database did not become ready after ${DB_WAIT_SECONDS}s." >&2
            cat "$DB_WAIT_LOG" >&2 || true
            return 1
        fi

        echo "Waiting for database..." >&2
        sleep "$DB_WAIT_INTERVAL_SECONDS"
        elapsed=$((elapsed + DB_WAIT_INTERVAL_SECONDS))
    done
}

wait_for_database
run_console doctrine:schema:update --force --no-interaction
run_console assets:install web --symlink --relative
run_console cache:clear --no-warmup
run_console cache:warmup
