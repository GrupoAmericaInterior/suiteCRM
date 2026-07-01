#!/usr/bin/env bash
set -euo pipefail

cd /var/www/html

if [ ! -f vendor/autoload.php ]; then
  composer install --no-interaction --prefer-dist
fi

# SuiteCRM installer needs to write in project root and these paths.
touch install.log || true
chown www-data:www-data install.log || true
chmod 664 install.log || true
chmod 775 /var/www/html || true

for d in cache custom modules themes data upload; do
  mkdir -p "$d"
  chown -R www-data:www-data "$d" || true
  find "$d" -type d -exec chmod 775 {} \; || true
  find "$d" -type f -exec chmod 664 {} \; || true
done

exec apache2-foreground