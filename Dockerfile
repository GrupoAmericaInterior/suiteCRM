FROM php:8.1-apache

# Install PHP extensions required by SuiteCRM 7.15
RUN apt-get update && apt-get install -y \
        libcurl4-openssl-dev \
        libpng-dev \
        libjpeg62-turbo-dev \
        libfreetype6-dev \
        libicu-dev \
        libzip-dev \
        libonig-dev \
        libxml2-dev \
        libldap2-dev \
        unzip \
        git \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-configure ldap \
    && docker-php-ext-install -j$(nproc) curl gd intl zip pdo pdo_mysql mysqli soap mbstring bcmath exif opcache gettext ldap \
    && docker-php-ext-enable curl gd intl zip pdo pdo_mysql mysqli soap mbstring bcmath exif opcache gettext ldap \
    && rm -rf /var/lib/apt/lists/*

RUN a2enmod rewrite && \
    sed -ri 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

COPY docker/php/suitecrm.ini /usr/local/etc/php/conf.d/suitecrm.ini
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY . /var/www/html
