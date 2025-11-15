FROM php:8.2-cli

# Install system dependencies + Node.js
RUN apt-get update && apt-get install -y \
    git curl libpng-dev libonig-dev libxml2-dev libpq-dev libzip-dev zip unzip \
    && docker-php-ext-install pdo pdo_pgsql pgsql mbstring exif pcntl bcmath gd zip \
    && curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app
COPY . .

# Install PHP dependencies
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader

# Install Node dependencies and build assets
RUN npm ci --only=production \
    && npm run build

# Publish Livewire assets
RUN php artisan livewire:publish --assets

RUN mkdir -p storage/framework/{sessions,views,cache} \
    && mkdir -p storage/logs \
    && chmod -R 775 storage bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

EXPOSE 8000

CMD php artisan migrate --force && \
    php artisan serve --host=0.0.0.0 --port=${PORT:-8000}
