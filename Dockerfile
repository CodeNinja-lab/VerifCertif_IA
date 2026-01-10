FROM dunglas/frankenphp:latest

RUN apt-get update && apt-get install -y \
    git unzip zip libpng-dev libjpeg-dev libfreetype6-dev \
    && rm -rf /var/lib/apt/lists/*

RUN install-php-extensions pdo_pgsql pgsql zip gd

# Copier Composer depuis l'image officielle
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app
COPY . .

RUN composer install --no-dev --optimize-autoloader --no-interaction

RUN chmod -R 775 storage bootstrap/cache

EXPOSE 10000

# Utiliser la variable d'environnement $PORT et l'exécutable frankenphp du PATH
CMD ["sh", "-c", "frankenphp run --host 0.0.0.0 --port ${PORT:-10000} --root public"]
