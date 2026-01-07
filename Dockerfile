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
    zip

# Installer Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app
COPY . .

# Installer les dépendances Laravel
RUN composer install \
    --no-dev \
    --optimize-autoloader \
    --no-interaction

# Permissions pour le storage
RUN chmod -R 775 storage bootstrap/cache

# Railway fournit la variable PORT
EXPOSE 8000

# Démarrer FrankenPHP sur le port dynamique de Railway
CMD ["sh", "-c", "php artisan config:cache && php artisan migrate --force && frankenphp run --listen :${PORT:-8000}"]
