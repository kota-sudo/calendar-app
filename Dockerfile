FROM php:8.2-cli

RUN apt-get update && apt-get install -y \
    git \
    unzip \
    curl \
    libzip-dev \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libpq-dev \
    nodejs \
    npm \
    && docker-php-ext-install pdo_pgsql mbstring zip exif pcntl bcmath

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

COPY ./src /var/www

RUN composer install --no-dev --optimize-autoloader --no-interaction
RUN npm install
RUN npm run build

CMD sh -c "php artisan config:clear && php artisan cache:clear && php artisan view:clear && php artisan route:clear && php artisan migrate --force && php artisan serve --host=0.0.0.0 --port=${PORT:-10000}"