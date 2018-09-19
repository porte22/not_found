FROM edimotive/php-apache:7.1

COPY vhost-feedcheker.conf /etc/apache2/sites-available/

RUN a2ensite vhost-feedcheker

WORKDIR /var/www

############### PHP INI SETTINGS #####################
COPY ./custom.ini /usr/local/etc/php/conf.d/
