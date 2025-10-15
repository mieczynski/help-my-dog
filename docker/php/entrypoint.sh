#!/bin/sh
set -e
cd /var/www/html/api

if [ ! -f vendor/autoload.php ]; then
  echo "[entrypoint] Installing PHP vendors..."
  composer install --prefer-dist --no-progress --no-interaction
fi

if [ ! -f config/jwt/private.pem ] || [ ! -f config/jwt/public.pem ]; then
  php bin/console lexik:jwt:generate-keypair --overwrite
fi

exec php-fpm -F