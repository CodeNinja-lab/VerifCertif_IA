#!/bin/bash
set -e

echo "🚀 Starting Laravel application..."

# Wait for database to be ready (if needed)
if [ ! -z "$DATABASE_URL" ]; then
    echo "⏳ Waiting for database..."
    sleep 5
fi

# Run Laravel setup
echo "📦 Setting up Laravel..."

# Generate app key if not exists
php artisan key:generate --force --no-interaction || true

# Clear caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Run migrations (optional, comment if you don't want auto-migrations)
php artisan migrate --force --no-interaction || echo "⚠️ Migrations skipped or failed"

# Cache configuration for better performance
php artisan config:cache
php artisan route:cache

# Set correct permissions
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

echo "✅ Laravel setup complete!"
echo "🌐 Starting web server..."

# Start supervisor
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
