FROM php:8.2-apache

RUN apt-get update && apt-get install -y \
    unzip \
    curl \
    bc \
    && rm -rf /var/lib/apt/lists/*

RUN echo '<VirtualHost *:80>\n\
    DocumentRoot /var/www/html/bookmarkly/public\n\
    Alias /data /var/www/html/bookmarkly/data\n\
    <Directory /var/www/html/bookmarkly/public>\n\
        Options Indexes FollowSymLinks\n\
        AllowOverride All\n\
        Require all granted\n\
    </Directory>\n\
    <Directory /var/www/html/bookmarkly/data>\n\
        Options Indexes FollowSymLinks\n\
        AllowOverride All\n\
        Require all granted\n\
    </Directory>\n\
</VirtualHost>' > /etc/apache2/sites-available/000-default.conf

RUN a2enmod rewrite

RUN echo "session.auto_start = 0\n\
session.use_strict_mode = 1\n\
session.use_cookies = 1\n\
session.use_only_cookies = 1\n\
session.cookie_secure = 0\n\
session.cookie_httponly = 1\n\
session.cookie_samesite = 'Lax'\n\
session.gc_maxlifetime = 3600\n\
session.sid_length = 48\n\
session.sid_bits_per_character = 6\n\
output_buffering = 4096" > /usr/local/etc/php/conf.d/custom.ini

COPY update-bookmarkly.sh /usr/local/bin/
COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/update-bookmarkly.sh \
    && chmod +x /usr/local/bin/docker-entrypoint.sh

COPY bookmarkly /var/www/html/bookmarkly

RUN mkdir -p /var/www/html/bookmarkly/data/uploads/icons && \
    chown -R www-data:www-data /var/www/html/bookmarkly/data && \
    find /var/www/html/bookmarkly/data -type d -exec chmod 777 {} \;

VOLUME ["/var/www/html/bookmarkly/data"]

EXPOSE 80

# Gebruik het nieuwe entrypoint script
ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
