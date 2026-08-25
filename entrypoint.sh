#!/bin/sh
set -e

# Generate an app key only if one isn't already set via env vars
if [ -z "$APP_KEY" ]; then
  php artisan key:generate --force
fi

# Render auto-injects RENDER_EXTERNAL_URL with your live https:// URL.
# Laravel needs APP_URL set correctly so it generates correct links/redirects
# instead of defaulting to http://localhost.
if [ -n "$RENDER_EXTERNAL_URL" ]; then
  export APP_URL="$RENDER_EXTERNAL_URL"
fi

# Rebuild the sqlite file fresh on every boot (fine for a demo;
# for persistence across restarts, switch to Render's free Postgres instead)
php artisan migrate:fresh --seed --force

# Cache config/routes for a small speed boost
php artisan config:cache
php artisan route:cache

# Render sets $PORT dynamically — bind to it, and to 0.0.0.0 so it's reachable
php artisan serve --host 0.0.0.0 --port "${PORT:-8000}"
