FROM dunglas/frankenphp:latest

# Paquets système nécessaires à Composer
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    zip \
    && rm -rf /var/lib/apt/lists/*

# Extensions PHP nécessaires
RUN install-php-extensions \
    gd \
    zip \
    pdo_pgsql \
    pgsql

# Installer Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app
COPY . .

# Installer les dépendances Laravel
RUN composer install \
    --no-dev \
    --optimize-autoloader \
    --no-interaction

# Permissions Laravel
RUN chmod -R 775 storage bootstrap/cache

# Render fournit aussi $PORT
EXPOSE 10000

# Lancer Laravel avec FrankenPHP sur Render
CMD ["sh", "-c", "php artisan config:clear && php artisan config:cache && php artisan migrate --force || true && frankenphp run --host 0.0.0.0 --port $PORT --root public"]
