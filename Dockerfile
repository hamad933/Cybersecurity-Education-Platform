FROM php:8.5.8-cli-bookworm AS php-runtime

RUN apt-get update \
    && apt-get install --no-install-recommends -y libicu-dev libonig-dev libpq-dev libzip-dev \
    && docker-php-ext-install intl mbstring pdo_pgsql zip \
    && rm -rf /var/lib/apt/lists/*

FROM php-runtime AS php-dependencies
WORKDIR /app
COPY --from=composer:2.10.2 /usr/bin/composer /usr/bin/composer
COPY . .
RUN composer install --no-dev --no-interaction --no-progress --prefer-dist --classmap-authoritative \
    && composer check-platform-reqs --no-dev

FROM php-runtime AS php-development
WORKDIR /app
RUN apt-get update \
    && apt-get install --no-install-recommends -y git unzip \
    && rm -rf /var/lib/apt/lists/*
COPY --from=composer:2.10.2 /usr/bin/composer /usr/bin/composer

FROM node:24.18.0-bookworm-slim AS frontend
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci --ignore-scripts
COPY resources ./resources
COPY public ./public
COPY vite.config.ts tsconfig.json env.d.ts ./
RUN npm run build

FROM php-runtime
WORKDIR /app
COPY --from=php-dependencies --chown=www-data:www-data /app /app
COPY --from=frontend --chown=www-data:www-data /app/public/build ./public/build
USER www-data
EXPOSE 8080
HEALTHCHECK --interval=15s --timeout=3s --retries=5 CMD php -r '$s=@fsockopen("127.0.0.1",8080); exit($s?0:1);'
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8080"]
