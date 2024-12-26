<<<<<<< HEAD
FROM php:8.2-apache

# Install dependencies
RUN apt-get update && apt-get install -y \
    libzip-dev \
    zip \
    unzip \
    && docker-php-ext-install zip

# Enable Apache modules
RUN a2enmod rewrite headers ssl

# Set working directory
WORKDIR /var/www/html

# Copy static files
COPY public/ /var/www/html/
COPY .htaccess /var/www/html/.htaccess
COPY apache.conf /etc/apache2/sites-available/000-default.conf

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && find /var/www/html -type d -exec chmod 755 {} \; \
    && find /var/www/html -type f -exec chmod 644 {} \;

# Configure Apache
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf \
    && echo "DirectoryIndex index.html" >> /etc/apache2/apache2.conf

# Enable the site
RUN a2ensite 000-default

# Expose ports
EXPOSE 80
EXPOSE 443

# Start Apache
CMD ["apache2-foreground"] 
=======
# Build stage
FROM composer:2.6 as composer

WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --ignore-platform-reqs

COPY . .
RUN composer dump-autoload --optimize

# Production stage
FROM php:8.1-apache

# Install system dependencies
RUN apt-get update && apt-get install -y \
    libpq-dev \
    redis \
    postgresql-client \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install \
    pdo \
    pdo_pgsql \
    && pecl install redis \
    && docker-php-ext-enable redis

# Configure Apache
RUN a2enmod rewrite headers

# Copy custom configurations
COPY docker/php/custom.ini $PHP_INI_DIR/conf.d/
COPY docker/000-default.conf /etc/apache2/sites-available/
COPY docker/start.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/start.sh

# Copy application files
WORKDIR /var/www/html
COPY --from=composer /app .
RUN chown -R www-data:www-data /var/www/html

# Expose port
EXPOSE 80

# Health check
HEALTHCHECK --interval=30s --timeout=3s \
    CMD curl -f http://localhost/health.php || exit 1

# Start script
CMD ["/usr/local/bin/start.sh"]
>>>>>>> aa145722f6a011a22d3e9f2b280787ab3c45a8fc
