#!/bin/bash
set -e

# Start PHP-FPM
php-fpm -D

# Wacht tot PHP-FPM beschikbaar is
COUNTER=0
while ! nc -z 127.0.0.1 9000 && [ $COUNTER -lt 30 ]; do
    echo "Wachten op PHP-FPM... ($COUNTER)"
    COUNTER=$((COUNTER+1))
    sleep 1
done

if ! nc -z 127.0.0.1 9000; then
    echo "PHP-FPM niet beschikbaar na 30 seconden!"
    exit 1
fi

echo "PHP-FPM is beschikbaar, start Apache..."

# Zorg ervoor dat Apache in de voorgrond draait
source /etc/apache2/envvars
rm -f /var/run/apache2/apache2.pid
exec apache2 -DFOREGROUND 
