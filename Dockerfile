FROM php:8.4-apache
RUN apt-get update && apt-get install -y --no-install-recommends libpq-dev libonig-dev libxml2-dev unzip git && docker-php-ext-install pdo_pgsql pdo_mysql mbstring dom && a2enmod rewrite && rm -rf /var/lib/apt/lists/*
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
WORKDIR /var/www/html
COPY . .
RUN composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader --no-scripts && php artisan package:discover --ansi && mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache && chown -R www-data:www-data storage bootstrap/cache
COPY deploy/apache.conf /etc/apache2/sites-available/000-default.conf
RUN chmod +x deploy/start.sh
EXPOSE 10000
CMD ["sh", "deploy/start.sh"]
