FROM php:8.2-fpm-alpine

RUN docker-php-ext-install pcntl

# Install composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
