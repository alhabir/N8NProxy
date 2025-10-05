#!/usr/bin/env bash
set -euo pipefail

APP_ROOT=${APP_ROOT:-/var/www/n8nproxy}
REPO_URL=${REPO_URL:-git@github.com:n8ndesigner/N8NProxy.git}
GIT_REF=${GIT_REF:-main}
PHP_BINARY=${PHP_BINARY:-/usr/bin/php}
COMPOSER_BINARY=${COMPOSER_BINARY:-$(command -v composer || echo /usr/bin/composer)}
PHP_FPM_SERVICE=${PHP_FPM_SERVICE:-php8.3-fpm}

RELEASES_DIR="$APP_ROOT/releases"
SHARED_DIR="$APP_ROOT/shared"
TIMESTAMP=$(date +%Y%m%d%H%M%S)
NEW_RELEASE="$RELEASES_DIR/$TIMESTAMP"

mkdir -p "$RELEASES_DIR" "$SHARED_DIR/storage" "$SHARED_DIR"/bootstrap/cache

trap 'echo "Deployment failed. Cleaning up $NEW_RELEASE" >&2; rm -rf "$NEW_RELEASE"' ERR

echo "==> Cloning $REPO_URL@$GIT_REF"
git clone --depth 1 --branch "$GIT_REF" "$REPO_URL" "$NEW_RELEASE"

# Flatten repository layout so Laravel lives at the release root
if [ -d "$NEW_RELEASE/app" ] && [ -f "$NEW_RELEASE/app/artisan" ]; then
  rsync -a "$NEW_RELEASE/app/" "$NEW_RELEASE/"
  rm -rf "$NEW_RELEASE/app"
fi

rm -rf "$NEW_RELEASE/.git"

cd "$NEW_RELEASE"

echo "==> Installing PHP dependencies"
"$COMPOSER_BINARY" install --no-dev --prefer-dist --optimize-autoloader

if [ ! -f "$SHARED_DIR/.env" ]; then
  echo "ERROR: $SHARED_DIR/.env is missing. Copy .env.production.example and update secrets before deploying." >&2
  exit 1
fi

ln -sfn "$SHARED_DIR/.env" "$NEW_RELEASE/.env"
rm -rf "$NEW_RELEASE/storage"
ln -sfn "$SHARED_DIR/storage" "$NEW_RELEASE/storage"

if ! grep -q '^APP_KEY=' "$SHARED_DIR/.env" || grep -q '^APP_KEY=$' "$SHARED_DIR/.env"; then
  echo "==> Generating APP_KEY"
  "$PHP_BINARY" artisan key:generate --force
fi

echo "==> Running database migrations"
"$PHP_BINARY" artisan migrate --force

echo "==> Optimising caches"
"$PHP_BINARY" artisan config:cache
"$PHP_BINARY" artisan route:cache
"$PHP_BINARY" artisan view:cache
"$PHP_BINARY" artisan storage:link --force >/dev/null 2>&1 || true

echo "==> Activating new release"
ln -sfn "$NEW_RELEASE" "$APP_ROOT/current"

if systemctl list-units "$PHP_FPM_SERVICE" >/dev/null 2>&1; then
  echo "==> Reloading $PHP_FPM_SERVICE"
  sudo systemctl reload "$PHP_FPM_SERVICE"
else
  echo "WARN: $PHP_FPM_SERVICE not found; skip reload" >&2
fi

echo "Deployment completed: $NEW_RELEASE"
