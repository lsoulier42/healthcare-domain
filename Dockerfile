FROM php:8.3-cli

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip \
    && git config --global --add safe.directory /app \
    && rm -rf /var/lib/apt/lists/*

COPY composer.json composer.lock* ./
RUN composer install --no-interaction --prefer-dist --no-progress --no-scripts

COPY . .
RUN composer dump-autoload --optimize

CMD ["composer", "test"]
