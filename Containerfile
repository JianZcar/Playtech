FROM phpmyadmin/phpmyadmin:latest AS phpmyadmin_stage
FROM php:apache

# Install required PHP extensions for Adminer and MySQL
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Optional but good: enable $_ENV and getenv()
RUN echo "variables_order = \"EGPCS\"" > /usr/local/etc/php/conf.d/99-env.ini

# Copy your website files
COPY ./ /var/www/html/

COPY --from=phpmyadmin_stage /var/www/html /usr/share/phpmyadmin
    

RUN echo "<?php\n\
\$cfg['Servers'][1]['port'] = 3306;\n\
\$cfg['Servers'][1]['connect_type'] = 'tcp';\n\
\$cfg['Servers'][1]['auth_type'] = 'cookie';\n\
\$cfg['ForceSSL'] = false;\n\

?>" > /usr/share/phpmyadmin/config.inc.php

# Apache config
RUN "<VirtualHost *:80>'; \n\
  ServerAdmin webmaster@localhost'; \n\
  DocumentRoot /var/www/html'; \n\
  <Directory /var/www/html>'; \n\
    Options Indexes FollowSymLinks'; \n\
    AllowOverride All'; \n\
    Require all granted'; \n\
  </Directory>'; \n\
  ErrorLog ${APACHE_LOG_DIR}/error.log'; \n\
  CustomLog ${APACHE_LOG_DIR}/access.log combined'; \n\
</VirtualHost>'" > /etc/apache2/sites-available/000-default.conf

RUN echo "Alias /admin/database /usr/share/phpmyadmin\n\
<Directory /usr/share/phpmyadmin>\n\
    Options FollowSymLinks\n\
    DirectoryIndex index.php\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>" > /etc/apache2/conf-available/phpmyadmin.conf

# Enable Apache modules and set permissions
RUN a2enmod rewrite headers \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

