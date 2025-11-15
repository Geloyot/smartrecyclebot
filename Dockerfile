FROM php:8.2-cli

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libpq-dev \
    libzip-dev \
    zip \
    unzip \
    && docker-php-ext-install pdo pdo_pgsql pgsql mbstring exif pcntl bcmath gd zip \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /app

# Copy application files
COPY . .

# Install dependencies
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader --no-scripts

# Run package discovery
RUN php artisan package:discover --ansi

# Set permissions
RUN chown -R www-data:www-data /app \
    && chmod -R 775 storage bootstrap/cache

# Cache config/routes/views at BUILD time (not runtime)
RUN php artisan config:cache && \
    php artisan route:cache && \
    php artisan view:cache

# Expose port
EXPOSE 8000

# Simple startup - just migrate and serve
CMD php artisan migrate --force && \
    php artisan serve --host=0.0.0.0 --port=${PORT:-8000}
