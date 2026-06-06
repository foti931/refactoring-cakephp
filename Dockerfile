FROM php:8.3-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git \
        libicu-dev \
        libonig-dev \
        unzip \
        zip \
    && docker-php-ext-install intl mbstring pdo_mysql \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY composer.json composer.lock ./
RUN composer install --no-interaction --prefer-dist --no-progress

COPY . .

RUN mkdir -p tmp logs tmp/cache \
    && chown -R www-data:www-data tmp logs \
    && chmod -R 775 tmp logs

COPY docker/apache/000-default.conf /etc/apache2/sites-available/000-default.conf

EXPOSE 80
