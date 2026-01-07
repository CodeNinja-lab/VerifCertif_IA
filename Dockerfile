FROM dunglas/frankenphp:latest

# Installer les extensions PHP nécessaires
RUN install-php-extensions gd

# Installer Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app
COPY . .

# Installer les dépendances Laravel
RUN composer install \
    --no-dev \
    --optimize-autoloader \
    --no-interaction

EXPOSE 8000
