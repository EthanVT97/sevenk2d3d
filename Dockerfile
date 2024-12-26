FROM php:8.2-apache

# Install dependencies and Apache modules
RUN apt-get update && apt-get install -y \
    libssl-dev \
    ssl-cert \
    && rm -rf /var/lib/apt/lists/* \
    && a2enmod rewrite headers ssl proxy proxy_http http2

# Copy Apache configuration
COPY docker/000-default.conf /etc/apache2/sites-available/000-default.conf

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . .

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Copy health check script
COPY scripts/healthcheck.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/healthcheck.sh

# Create required directories
RUN mkdir -p /var/log/apache2 \
    && mkdir -p /var/run/apache2

# Environment variables
ENV APACHE_LOG_DIR=/var/log/apache2 \
    APACHE_RUN_DIR=/var/run/apache2 \
    APACHE_PID_FILE=/var/run/apache2/apache2.pid \
    APACHE_RUN_USER=www-data \
    APACHE_RUN_GROUP=www-data

# Health check
HEALTHCHECK --interval=30s --timeout=3s \
    CMD /usr/local/bin/healthcheck.sh

# Start Apache
CMD ["apache2-foreground"]
