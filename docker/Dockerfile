FROM php:8.2-fpm

# Install system dependencies and PHP extensions
RUN apt-get update && apt-get install -y \
    libsqlite3-dev \
    && docker-php-ext-install pdo pdo_sqlite

# Set working directory
WORKDIR /var/www/html

# Ensure the data directory exists and is writable
RUN mkdir -p /var/www/html/data && chmod 777 /var/www/html/data

# Use the default production configuration
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# For development, we might want to show errors
RUN sed -i 's/display_errors = Off/display_errors = On/' "$PHP_INI_DIR/php.ini" \
    && sed -i 's/error_reporting = E_ALL &/error_reporting = E_ALL | E_STRICT/' "$PHP_INI_DIR/php.ini"

CMD ["php-fpm"]
