# Gebruik de officiële PHP 8.2 FPM image als basis
FROM php:8.2-fpm

ARG BOOKMARKLY_VERSION=1.4

RUN apt-get update && apt-get install -y \
    apache2 \
    libapache2-mod-fcgid \
    unzip \
    curl \
    netcat-traditional \
    && rm -rf /var/lib/apt/lists/*

RUN a2enmod proxy_fcgi setenvif rewrite

RUN curl -L https://bookmarkly.nl/download/bookmarkly_${BOOKMARKLY_VERSION}.zip -o /tmp/bookmarkly.zip \
    && unzip /tmp/bookmarkly.zip -d /var/www/html/ \
    && rm /tmp/bookmarkly.zip \
    && chown -R www-data:www-data /var/www/html/ \
    && chmod -R 755 /var/www/html/ \
    && mkdir -p /var/www/html/bookmarkly/data \
    && chmod -R 777 /var/www/html/bookmarkly/data

RUN echo "<VirtualHost *:80>\n\
    DocumentRoot /var/www/html/bookmarkly/public\n\
    \n\
    <Directory /var/www/html/bookmarkly/public>\n\
        Options Indexes FollowSymLinks\n\
        AllowOverride All\n\
        Require all granted\n\
    </Directory>\n\
    \n\
    <FilesMatch \.php$>\n\
        SetHandler proxy:fcgi://127.0.0.1:9000\n\
    </FilesMatch>\n\
</VirtualHost>" > /etc/apache2/sites-available/000-default.conf

RUN sed -i 's/listen = \/var\/run\/php-fpm.sock/listen = 127.0.0.1:9000/g' /usr/local/etc/php-fpm.d/www.conf \
    && sed -i 's/;catch_workers_output = yes/catch_workers_output = yes/g' /usr/local/etc/php-fpm.d/www.conf \
    && sed -i 's/;php_admin_flag\[log_errors\] = on/php_admin_flag[log_errors] = on/g' /usr/local/etc/php-fpm.d/www.conf \
    && sed -i 's/;php_admin_value\[error_log\] = .*/php_admin_value[error_log] = \/proc\/self\/fd\/2/g' /usr/local/etc/php-fpm.d/www.conf

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

RUN mkdir -p /var/run/apache2 \
    && mkdir -p /var/lock/apache2 \
    && chown -R www-data:www-data /var/run/apache2 \
    && chown -R www-data:www-data /var/lock/apache2

COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE 80

ENTRYPOINT ["docker-entrypoint.sh"]
