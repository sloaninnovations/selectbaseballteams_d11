#!/bin/bash

ln -s $CI_PROJECT_DIR /var/www/html/subdirectory
# enable JIT for Apache only
echo 'opcache.jit_buffer_size=2G' >> /usr/local/etc/php/php.ini
sudo service apache2 start
mkdir -p ./sites/simpletest ./sites/default/files ./build/logs/junit /var/www/.composer
chown -R www-data:www-data ./sites ./build/logs/junit ./vendor /var/www/
sudo -u www-data git config --global --add safe.directory $CI_PROJECT_DIR
