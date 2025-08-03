FROM php:8.4.8-cli

ARG GID=1000
ARG UID=1000

RUN groupadd admin -g $GID \
    && useradd -m admin -u $UID -g $GID -s /bin/bash

RUN apt-get update && apt-get install -y \
    git \
    unzip \
    zip \
    gnupg2 \
    libzip-dev \
    libpq-dev

RUN docker-php-ext-install pdo_mysql zip pcntl

RUN pecl install xdebug \
    && docker-php-ext-enable xdebug

RUN  curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

WORKDIR /var/www

USER admin
