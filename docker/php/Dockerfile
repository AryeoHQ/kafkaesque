FROM php:8.4 AS base

# Install PHP extension installer
ADD --chmod=0755 https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/

# Install required PHP extensions
RUN install-php-extensions rdkafka

# Install unzip package required by Composer
RUN apt-get update && \
    apt-get install -y unzip

# Copy application files
COPY . /usr/src/app

WORKDIR /usr/src/app

# Install Composer and application dependencies
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
RUN composer install --prefer-dist

# Clean up packages
RUN apt-get autoremove -y && \
    rm -rf /var/lib/apt/lists/* /var/cache/apt/archives /tmp/* /var/tmp/*

FROM base AS local

# Copy PHP INI file optimized for local development
COPY ./docker/php/php.local.ini $PHP_INI_DIR/php.ini

# Temporarily disable checks to determine if extension can be enabled. Otherwise, the Docker image build will break.
ENV IPE_SKIP_CHECK=1
RUN install-php-extensions xdebug
ENV IPE_SKIP_CHECK=0
