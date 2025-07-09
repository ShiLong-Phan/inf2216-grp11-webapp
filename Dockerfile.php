FROM php:8.2-fpm

RUN apt-get update && apt-get install -y \
    libzip-dev \
    && docker-php-ext-install mysqli pdo pdo_mysql zip

# Configure PHP
RUN echo "upload_max_filesize = 100M" > /usr/local/etc/php/conf.d/uploads.ini
RUN echo "post_max_size = 100M" >> /usr/local/etc/php/conf.d/uploads.ini

RUN apt-get install -y nano
RUN docker-php-ext-install mysqli

# You can add other PHP extensions here as needed
# RUN docker-php-ext-install pdo pdo_mysql gd

# Any other custom configurations for your PHP service can go here
# Install PHPUnit - FIXED COMMAND
RUN curl -L -o phpunit-9.phar https://phar.phpunit.de/phpunit-9.phar \
    && chmod +x phpunit-9.phar \
    && mv phpunit-9.phar /usr/local/bin/phpunit