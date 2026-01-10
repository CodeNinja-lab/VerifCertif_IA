FROM dunglas/frankenphp:latest

# Installer les dépendances système
RUN apt-get update && apt-get install -y \
    git unzip zip libpng-dev libjpeg-dev libfreetype6-dev \
    && rm -rf /var/lib/apt/lists/*

# Installer les extensions PHP
RUN install-php-extensions pdo_pgsql pgsql zip gd

# Copier Composer depuis l'image officielle
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Définir le répertoire de travail
WORKDIR /app

# Copier les fichiers du projet
COPY . .

# Installer les dépendances PHP
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Définir les permissions
RUN chmod -R 775 storage bootstrap/cache

# Exposer le port
EXPOSE 10000

# Démarrer FrankenPHP avec le chemin complet
CMD ["/bin/sh", "-c", "/usr/local/bin/frankenphp run --domain localhost --listen 0.0.0.0:${PORT:-10000} --root /app/public"]