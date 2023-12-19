FROM php:8.1-apache as app

COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/

#RUN docker-php-ext-install pdo pdo_mysql
RUN set --eux; \
    install-php-extensions pdo pdo_mysql; \
    docker-php-ext-install mysqli && docker-php-ext-enable mysqli; \
    a2enmod rewrite
# for mysqli if you want
RUN apt-get update && apt-get install -y cron

ENV COMPOSER_ALLOW_SUPERUSER=1

EXPOSE 465

FROM app as app_dev

ENV XDEBUG_MODE=off

COPY ./docker/apache/php/conf.d/xdebug.ini /usr/local/etc/php/conf.d/xdebug.ini

RUN set -eux; \
    install-php-extensions xdebug;
