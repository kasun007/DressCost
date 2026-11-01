# DressCost in one container: PHP 8.5 serving the Laravel app, with the
# frontend built during the image build. MySQL runs as a separate service
# (see compose.yaml). Start everything with: docker compose up --build

# ---------------------------------------------------------------------------
# Build stage: PHP + Node, all dependencies, compiled frontend assets.
# The Wayfinder Vite plugin runs `php artisan`, so the frontend build needs PHP.
# ---------------------------------------------------------------------------
FROM php:8.5-cli AS build

COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
COPY --from=node:24-slim /usr/local/bin/node /usr/local/bin/node
COPY --from=node:24-slim /usr/local/lib/node_modules /usr/local/lib/node_modules
RUN ln -s /usr/local/lib/node_modules/npm/bin/npm-cli.js /usr/local/bin/npm \
    && apt-get update && apt-get install -y --no-install-recommends git unzip \
    && rm -rf /var/lib/apt/lists/* \
    && install-php-extensions pdo_mysql zip

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install --no-interaction --no-progress --no-scripts --prefer-dist

COPY package.json package-lock.json .npmrc ./
RUN npm ci

COPY . .
RUN cp .env.example .env \
    && php artisan key:generate --force \
    && composer dump-autoload --optimize \
    && npm run build

# ---------------------------------------------------------------------------
# Runtime stage: PHP only, production dependencies, built assets.
# ---------------------------------------------------------------------------
FROM php:8.5-cli AS app

COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
RUN apt-get update && apt-get install -y --no-install-recommends unzip \
    && rm -rf /var/lib/apt/lists/* \
    && install-php-extensions pdo_mysql zip opcache

WORKDIR /var/www/html

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-progress --no-scripts --prefer-dist

COPY . .
COPY --from=build /app/public/build ./public/build
RUN rm -f .env public/hot \
    && composer dump-autoload --optimize --no-dev \
    && chmod +x docker/entrypoint.sh

EXPOSE 8000

ENTRYPOINT ["docker/entrypoint.sh"]
