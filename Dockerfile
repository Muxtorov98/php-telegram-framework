FROM php:8.3-cli

WORKDIR /app

# Composer o‘rnatish
RUN apt-get update && apt-get install -y git unzip libpq-dev supervisor \
    && docker-php-ext-install pdo pdo_pgsql \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# composer cache ni tezlashtirish uchun
ENV COMPOSER_ALLOW_SUPERUSER=1
ENV COMPOSER_CACHE_DIR=/tmp/cache

# dependencies ni cache bilan o‘rnatish
COPY composer.json composer.lock* ./
RUN composer install --no-interaction --prefer-dist || true

# qolgan fayllarni ko‘chirib kiritamiz (faqat strukturani)
COPY . /app

# bash default shell bo‘lishi uchun
CMD ["bash"]
