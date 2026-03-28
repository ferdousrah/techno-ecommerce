#!/bin/sh
set -e

cd /var/www/html

# Run artisan commands that need .env (only when APP_KEY is set)
if [ -n "$APP_KEY" ]; then
    echo "Running Laravel setup commands..."

    php artisan storage:link --force
    php artisan migrate --force
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    php artisan event:cache

    # Fix storage permissions after linking
    chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
else
    echo "WARNING: APP_KEY not set — skipping artisan commands."
fi

# Create supervisor log dir
mkdir -p /var/log/supervisor

echo "Starting services..."
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
