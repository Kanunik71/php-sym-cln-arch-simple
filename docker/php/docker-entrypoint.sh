#!/bin/sh
set -e

cd /var/www/html

echo "[entrypoint] composer install..."
composer install --no-interaction --prefer-dist --optimize-autoloader

if [ "${AUTO_MIGRATE:-0}" = "1" ]; then
  echo "[entrypoint] waiting for database + migrations..."
  i=0
  until php bin/console doctrine:migrations:migrate --no-interaction; do
    i=$((i + 1))
    if [ "$i" -ge 60 ]; then
      echo "[entrypoint] migrate failed after retries" >&2
      exit 1
    fi
    echo "[entrypoint] DB not ready, retry $i/60..."
    sleep 2
  done
  echo "[entrypoint] migrations done"
fi

exec "$@"
