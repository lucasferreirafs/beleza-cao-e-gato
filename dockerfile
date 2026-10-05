FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --prefer-dist --no-interaction --no-progress --optimize-autoloader --ignore-platform-req=ext-pdo_pgsql

FROM php:8.3-apache
RUN apt-get update && apt-get install -y --no-install-recommends libpq-dev \
    && docker-php-ext-install pdo_pgsql \
    && a2enmod rewrite headers deflate \
    && rm -rf /var/lib/apt/lists/*
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf
WORKDIR /var/www/html
COPY --from=vendor /app/vendor ./vendor
COPY . .
RUN mkdir -p storage && chown -R www-data:www-data storage
COPY docker/apache.conf /etc/apache2/conf-available/app-security.conf
COPY docker/uploads.ini /usr/local/etc/php/conf.d/uploads.ini
# Falha no build se o runtime não oferecer as extensões usadas pelo Storage.
RUN php -r 'exit(extension_loaded("curl") && extension_loaded("fileinfo") ? 0 : 1);'
RUN a2enconf app-security
EXPOSE 80
