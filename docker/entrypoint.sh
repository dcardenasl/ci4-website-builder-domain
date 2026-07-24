#!/bin/sh

set -eu

cd /var/www/html

echo "[entrypoint] Waiting for Domain database..."
i=1
while [ "$i" -le 30 ]; do
    if php -r '
        $connection = @mysqli_connect(
            getenv("DB_HOST") ?: "db",
            getenv("MYSQL_USER") ?: "ci4_user",
            getenv("MYSQL_PASSWORD") ?: "",
            getenv("MYSQL_DATABASE") ?: "ci4_website_builder_domain",
            (int) (getenv("DB_PORT") ?: 3306)
        );
        exit($connection ? 0 : 1);
    '; then
        echo "[entrypoint] Domain database is ready."
        break
    fi

    if [ "$i" -eq 30 ]; then
        echo "[entrypoint] Domain database was unreachable after 30 attempts." >&2
        exit 1
    fi

    i=$((i + 1))
    sleep 2
done

echo "[entrypoint] Running Domain migrations..."
php spark migrate --all

exec "$@"
