FROM php:apache
# RUN apt-get update && apt-get install -y \
# libfreetype6-dev \
#         libjpeg62-turbo-dev \
#         libmcrypt-dev \
#         libpng-dev \
#         ssmtp \
#         gettext \
#         screen
#         # rm -r /var/lib/apt/lists/*
# # RUN docker-php-ext-configure gd
RUN apt-get update && apt-get install -y \
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        libpng-dev \
        && docker-php-ext-configure gd --with-freetype --with-jpeg \
        && docker-php-ext-install -j$(nproc) gd mysqli
        # && docker-php-ext-install  mbstring
RUN a2enmod rewrite
