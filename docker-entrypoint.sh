#!/bin/bash

# Update Bookmarkly als er een nieuwe versie beschikbaar is
/usr/local/bin/update-bookmarkly.sh

# Zorg ervoor dat alle benodigde directories bestaan
mkdir -p /var/www/html/bookmarkly/data/uploads/icons
chown -R www-data:www-data /var/www/html/bookmarkly/

# Zet de juiste rechten voor alle data directories
find /var/www/html/bookmarkly/data -type d -exec chmod 777 {} \;
find /var/www/html/bookmarkly/data -type f -exec chmod 666 {} \;

# Start Apache in de voorgrond
exec apache2-foreground 