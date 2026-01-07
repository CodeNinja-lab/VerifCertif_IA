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

EXPOSE 8000
