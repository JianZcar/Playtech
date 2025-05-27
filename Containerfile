# Stage 1: Use official phpMyAdmin image to get phpMyAdmin files
FROM phpmyadmin/phpmyadmin:latest AS phpmyadmin_stage

# Stage 2: Build your custom Ubuntu + Apache + PHP + phpMyAdmin container
FROM ubuntu:24.04

ENV DEBIAN_FRONTEND=noninteractive

# Install Apache, PHP, and dependencies
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

# Copy phpMyAdmin files from official image
COPY --from=phpmyadmin_stage /var/www/html /usr/share/phpmyadmin

# Create minimal phpMyAdmin config
RUN echo "<?php\n\
\$cfg['Servers'][1]['host'] = 'PlaytechDB';\n\
\$cfg['Servers'][1]['port'] = 3306;\n\
\$cfg['Servers'][1]['connect_type'] = 'tcp';\n\
\$cfg['Servers'][1]['auth_type'] = 'cookie';\n\
\$cfg['ForceSSL'] = false;\n\
?>" > /usr/share/phpmyadmin/config.inc.php

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

# Copy your PHP app into /var/www/html if needed
COPY . /var/www/html/

EXPOSE 80

CMD ["apache2ctl", "-D", "FOREGROUND"]

