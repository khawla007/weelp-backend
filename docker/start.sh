#!/usr/bin/env bash

set -euo pipefail

cd /var/www/html

mkdir -p \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache

chown -R www-data:www-data storage bootstrap/cache

php artisan config:cache
php artisan route:cache
php artisan view:cache

if [[ "${RUN_MIGRATIONS:-true}" == "true" ]]; then
    max_attempts="${MIGRATION_MAX_ATTEMPTS:-12}"
    retry_delay="${MIGRATION_RETRY_DELAY:-5}"
    database_connection="${DB_CONNECTION:-mysql}"
    database_ready=false

    if [[ ! "$max_attempts" =~ ^[1-9][0-9]*$ ]]; then
        echo "MIGRATION_MAX_ATTEMPTS must be a positive integer." >&2
        exit 1
    fi

    if [[ ! "$retry_delay" =~ ^[0-9]+$ ]]; then
        echo "MIGRATION_RETRY_DELAY must be a non-negative integer." >&2
        exit 1
    fi

    for ((attempt = 1; attempt <= max_attempts; attempt++)); do
        if php artisan db:show --database="$database_connection" --no-interaction >/dev/null 2>&1; then
            database_ready=true
            break
        fi

        if ((attempt < max_attempts)); then
            echo "Database is not ready; retrying in ${retry_delay}s (${attempt}/${max_attempts})." >&2
            sleep "$retry_delay"
        fi
    done

    if [[ "$database_ready" != "true" ]]; then
        echo "Database did not become ready after ${max_attempts} attempts." >&2
        exit 1
    fi

    php artisan migrate --force
fi

exec apache2-foreground
