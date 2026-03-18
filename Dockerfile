FROM php:8.2-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql pgsql \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html
COPY . /var/www/html

RUN a2enmod rewrite \
    && chown -R www-data:www-data /var/www/html/storage /var/www/html/uploads || true

EXPOSE 80

CMD ["apache2-foreground"]
