FROM php:8.2-apache

RUN apt-get update && apt-get install -y \
    libpq-dev \
    libonig-dev \
    libzip-dev \
    && docker-php-ext-install \
    pdo \
    pdo_pgsql \
    pgsql \
    mbstring \
    zip \
    && a2enmod rewrite

# PHP limits for restaurant export/import (large files with embedded/zipped images)
RUN { \
      echo 'upload_max_filesize=128M'; \
      echo 'post_max_size=128M'; \
      echo 'memory_limit=512M'; \
      echo 'max_execution_time=600'; \
      echo 'max_input_time=600'; \
    } > /usr/local/etc/php/conf.d/birmenu-uploads.ini

WORKDIR /var/www/html

COPY . /var/www/html

RUN chown -R www-data:www-data /var/www/html

RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

EXPOSE 80

CMD ["apache2-foreground"]
