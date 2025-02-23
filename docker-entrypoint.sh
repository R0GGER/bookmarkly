#!/bin/bash

/usr/local/bin/update-bookmarkly.sh

mkdir -p /var/www/html/bookmarkly/data/uploads/icons
chown -R www-data:www-data /var/www/html/bookmarkly/

find /var/www/html/bookmarkly/data -type d -exec chmod 777 {} \;
find /var/www/html/bookmarkly/data -type f -exec chmod 666 {} \;

exec apache2-foreground 
