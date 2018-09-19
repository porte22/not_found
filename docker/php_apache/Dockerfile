FROM edimotive/php-apache:7.1

COPY vhost-notfound.conf /etc/apache2/sites-available/

RUN a2ensite vhost-notfound

WORKDIR /var/www

############### PHP INI SETTINGS #####################
COPY ./custom.ini /usr/local/etc/php/conf.d/
