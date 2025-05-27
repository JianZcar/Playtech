FROM ubuntu:24.04

ENV DEBIAN_FRONTEND=noninteractive

# Install Apache, PHP, phpMyAdmin dependencies
RUN apt-get update && apt-get install -y \
    apache2 \
    php \
    php-mysqli \
    php-json \
    php-mbstring \
    php-zip \
    php-gd \
    php-curl \
    php-xml \
    php-bcmath \
    php-xmlrpc \
    php-soap \
    php-intl \
    unzip \
    wget \
    && rm -rf /var/lib/apt/lists/*

# Download phpMyAdmin
RUN mkdir -p /usr/share/phpmyadmin && \
    wget https://files.phpmyadmin.net/phpMyAdmin/5.2.1/phpMyAdmin-5.2.1-all-languages.zip -O /tmp/phpmyadmin.zip && \
    unzip /tmp/phpmyadmin.zip -d /usr/share/phpmyadmin && \
    mv /usr/share/phpmyadmin/phpMyAdmin-5.2.1-all-languages/* /usr/share/phpmyadmin/ && \
    rm -rf /usr/share/phpmyadmin/phpMyAdmin-5.2.1-all-languages /tmp/phpmyadmin.zip

RUN echo "<?php\n\
$cfg['Servers'][1]['host'] = 'PlaytechDB';\n\
$cfg['Servers'][1]['socket'] = ''; \n\
$cfg['ForceSSL'] = false;\n\
$cfg['Servers'][1]['auth_type'] = 'cookie';\n\
?>" > /usr/share/phpmyadmin/config.inc.php

# Copy your PHP app into /var/www/html
COPY . /var/www/html/

# Configure Apache to serve phpMyAdmin at /admin/database
RUN echo "Alias /admin/database /usr/share/phpmyadmin\n\
<Directory /usr/share/phpmyadmin>\n\
    Options FollowSymLinks\n\
    DirectoryIndex index.php\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>" > /etc/apache2/conf-available/phpmyadmin.conf

RUN a2enconf phpmyadmin
RUN a2enmod rewrite

EXPOSE 80

CMD ["apache2ctl", "-D", "FOREGROUND"]

