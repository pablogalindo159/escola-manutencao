FROM php:8.2-fpm-alpine

# Install system dependencies
RUN apk add --no-cache \
    git \
    curl \
    libpng-dev \
    libjpeg-turbo-dev \
    libwebp-dev \
    zlib-dev \
    libzip-dev \
    postgresql-dev \
    nodejs \
    npm

# Install PHP extensions
RUN docker-php-ext-configure gd --with-jpeg --with-webp && \
    docker-php-ext-install -j$(nproc) \
    gd \
    zip \
    pdo \
    pdo_pgsql \
    bcmath \
    ctype \
    json \
    tokenizer

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /app

# Copy project files
COPY . .

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader

# Install Node dependencies for assets
RUN npm install && npm run build

# Create necessary directories and set permissions
RUN mkdir -p storage/logs storage/app/uploads storage/app/certificates && \
    chmod -R 775 storage bootstrap/cache && \
    chown -R www-data:www-data /app

# Expose port
EXPOSE 8000

CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
