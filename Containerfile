FROM php:apache

# Install required PHP extensions for Adminer and MySQL
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Optional but good: enable $_ENV and getenv()
RUN echo "variables_order = \"EGPCS\"" > /usr/local/etc/php/conf.d/99-env.ini

# Copy your website files
COPY ./ /var/www/html/

# Download Adminer to /admin/database
RUN mkdir -p /var/www/html/admin/database \
    && curl -s -L https://www.adminer.org/latest.php -o /var/www/html/admin/database/index.php

# Apache config
RUN { \
    echo '<VirtualHost *:80>'; \
    echo '  ServerAdmin webmaster@localhost'; \
    echo '  DocumentRoot /var/www/html'; \
    echo; \
    echo '  <Directory /var/www/html>'; \
    echo '    Options Indexes FollowSymLinks'; \
    echo '    AllowOverride All'; \
    echo '    Require all granted'; \
    echo '  </Directory>'; \
    echo; \
    echo '  ErrorLog ${APACHE_LOG_DIR}/error.log'; \
    echo '  CustomLog ${APACHE_LOG_DIR}/access.log combined'; \
    echo '</VirtualHost>'; \
} > /etc/apache2/sites-available/000-default.conf

# Enable Apache modules and set permissions
RUN a2enmod rewrite headers \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

