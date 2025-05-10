FROM php:8.2-apache

RUN apt-get update
RUN apt-get install -y nano
RUN docker-php-ext-install mysqli

# You can add other PHP extensions here as needed
# RUN docker-php-ext-install pdo pdo_mysql gd

# Any other custom configurations for your PHP service can go here
