FROM php:apache AS base

# Install required PHP extensions for MySQL
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Optional but good: enable $_ENV and getenv()
RUN echo "variables_order = \"EGPCS\"" > /usr/local/etc/php/conf.d/99-env.ini

# Copy your website files
COPY ./ /var/www/html/


# Use the official phpMyAdmin image as a build stage
FROM phpmyadmin/phpmyadmin AS phpmyadmin

# Back to base image to copy phpMyAdmin files from phpmyadmin stage
FROM base

# Create target directory
RUN mkdir -p /var/www/html/admin/database

# Copy phpMyAdmin files from the phpmyadmin image into your web root
COPY --from=phpmyadmin /usr/src/phpmyadmin /var/www/html/admin/database

# Set permissions
RUN chown -R www-data:www-data /var/www/html/admin/database

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

