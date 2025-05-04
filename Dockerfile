FROM php:8.3-fpm

# Instalações essenciais
RUN apt-get update && apt-get install -y \
    zip unzip curl git libmcrypt-dev libpng-dev libjpeg-dev libfreetype6-dev \
    && docker-php-ext-install pdo pdo_mysql sockets bcmath

# Instala Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Define o diretório de trabalho
WORKDIR /var/www/html
