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

# Disable Laravel's auto-discovery during build to avoid .env requirement
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader --no-scripts

# Copy package files and install Node dependencies
COPY package.json package-lock.json* ./
RUN npm ci --silent

# Copy application code
COPY . .

# Now run Composer scripts with application code present
RUN composer dump-autoload --optimize

# Create a temporary .env file for Vite build (will be overwritten by Render env vars at runtime)
RUN cp .env.example .env || echo "APP_NAME='Smart Recyclebot'" > .env && \
    echo "VITE_REVERB_APP_KEY=placeholder" >> .env && \
    echo "VITE_REVERB_HOST=https://smartrecyclebot-b86k.onrender.com" >> .env && \
    echo "VITE_REVERB_PORT=443" >> .env && \
    echo "VITE_REVERB_SCHEME=https" >> .env && \
    echo "VITE_PUSHER_APP_KEY=placeholder" >> .env && \
    echo "VITE_PUSHER_HOST=https://smartrecyclebot-b86k.onrender.com" >> .env && \
    echo "VITE_PUSHER_PORT=443" >> .env && \
    echo "VITE_PUSHER_SCHEME=https" >> .env

# Build frontend assets
RUN npm run build

# Remove temporary .env (Render will inject real env vars at runtime)
RUN rm -f .env

# Set permissions
RUN chown -R www-data:www-data /var/www && \
    chmod -R 775 storage bootstrap/cache

# Copy nginx and supervisor configs
COPY docker/nginx.conf /etc/nginx/sites-available/default
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh

# Make entrypoint executable
RUN chmod +x /usr/local/bin/entrypoint.sh

# Expose port
EXPOSE 8080

# Use entrypoint script
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
