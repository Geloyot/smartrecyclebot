FROM php:8.2-fpm

# Install system dependencies and Node.js
RUN apt-get update && apt-get install -y --no-install-recommends \
    git curl unzip libzip-dev libpng-dev libonig-dev libxml2-dev libicu-dev zlib1g-dev \
    libfreetype6-dev libjpeg62-turbo-dev libpq-dev nginx supervisor \
    build-essential ca-certificates gnupg2 && \
    curl -fsSL https://deb.nodesource.com/setup_18.x | bash - && \
    apt-get install -y nodejs && \
    rm -rf /var/lib/apt/lists/*

# PHP extensions (added pdo_pgsql for PostgreSQL)
RUN docker-php-ext-configure gd --with-freetype --with-jpeg && \
    docker-php-ext-install pdo_mysql pdo_pgsql zip mbstring exif pcntl bcmath intl gd && \
    rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

# Copy dependency files
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader

# Copy package files and install Node dependencies
COPY package.json package-lock.json* ./
RUN npm ci --silent --only=production

# Copy application code
COPY . .

# Build frontend assets
RUN npm run build

# Set permissions
RUN chown -R www-data:www-data /var/www && \
    chmod -R 775 storage bootstrap/cache

# Copy nginx and supervisor configs
COPY docker/nginx.conf /etc/nginx/sites-available/default
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh

# Make entrypoint executable
RUN chmod +x /usr/local/bin/entrypoint.sh

# Expose port (Render assigns PORT dynamically, but nginx listens on 8080)
EXPOSE 8080

# Use entrypoint script
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
