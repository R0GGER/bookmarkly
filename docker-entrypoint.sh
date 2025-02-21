#!/bin/bash
set -e

# Start PHP-FPM
php-fpm -D

COUNTER=0
while ! nc -z 127.0.0.1 9000 && [ $COUNTER -lt 30 ]; do
    echo "Waiting for PHP-FPM... ($COUNTER)"
    COUNTER=$((COUNTER+1))
    sleep 1
done

if ! nc -z 127.0.0.1 9000; then
    echo "PHP-FPM not available after 30 seconds!"
    exit 1
fi

echo "PHP-FPM is available, starting Apache..."

source /etc/apache2/envvars
rm -f /var/run/apache2/apache2.pid
exec apache2 -DFOREGROUND 
