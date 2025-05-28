FROM php:8.2-apache

# Install PHP extensions required by phpMyAdmin
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Optional: enable getenv() for phpMyAdmin
RUN echo "variables_order = \"EGPCS\"" > /usr/local/etc/php/conf.d/99-env.ini

# Copy your app files into the document root (if any)
COPY ./ /var/www/html/

# Enable Apache mods
RUN a2enmod rewrite headers

# Fix ownership and permissions
RUN chown -R www-data:www-data /var/www/html /usr/share/phpmyadmin \
 && chmod -R 755 /var/www/html /usr/share/phpmyadmin

EXPOSE 80
