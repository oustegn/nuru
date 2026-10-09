FROM composer:2.8 AS composer

FROM php:8.3-apache-bookworm
RUN apt-get update && apt-get install -y --no-install-recommends libonig-dev libxml2-dev unzip \
    && docker-php-ext-install mbstring dom xml pdo_mysql \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*
COPY --from=composer /usr/bin/composer /usr/local/bin/composer
WORKDIR /var/www/html
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction
COPY bin/ bin/
COPY config/ config/
COPY src/ src/
COPY templates/ templates/
COPY public/ public/
COPY docker/ docker/
COPY migrations/ migrations/
RUN composer dump-autoload --no-dev --classmap-authoritative \
    && mkdir -p var/cache var/log \
    && chown -R www-data:www-data var \
    && sed -i 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf \
    && printf '<Directory /var/www/html/public>\nAllowOverride All\nRequire all granted\n</Directory>\n' > /etc/apache2/conf-enabled/nuru.conf
COPY docker/start.sh /usr/local/bin/nuru-start
RUN chmod +x /usr/local/bin/nuru-start
ENV SESSION_SECURE=1 SESSION_SAMESITE=none APP_ENV=prod APP_DEBUG=0 SITE_PUBLIC_ORIGIN="" APP_RUNTIME_OPTIONS='{"disable_dotenv":true}'
EXPOSE 3000
CMD ["nuru-start"]
