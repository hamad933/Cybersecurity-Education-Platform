FROM composer:2.10.2 AS php-dependencies
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-progress --prefer-dist --no-scripts --classmap-authoritative

FROM node:24.18.0-bookworm-slim AS frontend
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci --ignore-scripts
COPY resources ./resources
COPY vite.config.ts tsconfig.json env.d.ts ./
RUN npm run build

FROM php:8.5.8-cli-bookworm
RUN apt-get update \
    && apt-get install --no-install-recommends -y libpq-dev \
    && docker-php-ext-install pdo_pgsql \
    && rm -rf /var/lib/apt/lists/*
WORKDIR /app
COPY --chown=www-data:www-data . .
COPY --from=php-dependencies --chown=www-data:www-data /app/vendor ./vendor
COPY --from=frontend --chown=www-data:www-data /app/public/build ./public/build
USER www-data
EXPOSE 8080
HEALTHCHECK --interval=15s --timeout=3s --retries=5 CMD php -r '$s=@fsockopen("127.0.0.1",8080); exit($s?0:1);'
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8080"]
