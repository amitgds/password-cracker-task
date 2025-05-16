FROM php:8.2-fpm

# Install dependencies
RUN apt-get update && apt-get install -y \
    libpq-dev \
    && docker-php-ext-install pdo pdo_mysql

# Install AWS SDK and Monolog
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
COPY composer.json .
RUN composer install

# Copy application files
COPY public /app/public
COPY src /app/src
COPY data /app/data
COPY php-fpm.conf /usr/local/etc/php-fpm.d/www.conf

WORKDIR /app

# Expose port
EXPOSE 9000

CMD ["php-fpm"]