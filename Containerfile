# ---------- Stage 1: Get phpMyAdmin ----------
FROM phpmyadmin/phpmyadmin:latest AS phpmyadmin_stage

# ---------- Stage 2: Setup Apache + PHP + phpMyAdmin ----------
FROM php:apache

# Install PHP extensions required by phpMyAdmin
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Optional: enable getenv() for phpMyAdmin
RUN echo "variables_order = \"EGPCS\"" > /usr/local/etc/php/conf.d/99-env.ini

# Copy your app files into the document root (if any)
COPY ./ /var/www/html/

# Copy phpMyAdmin to /usr/share/phpmyadmin
COPY --from=phpmyadmin_stage /var/www/html/ /usr/share/phpmyadmin/

# Create minimal config.inc.php (optional; phpMyAdmin can auto-config)
RUN echo "<?php\n\
\$cfg['Servers'][1]['port'] = 3306;\n\
\$cfg['Servers'][1]['connect_type'] = 'tcp';\n\
\$cfg['Servers'][1]['auth_type'] = 'cookie';\n\
\$cfg['ForceSSL'] = false;\n\
?>" > /usr/share/phpmyadmin/config.inc.php

# Alias phpMyAdmin under /admin/database
RUN echo "Alias /admin/database /usr/share/phpmyadmin\n\
<Directory /usr/share/phpmyadmin>\n\
    Options FollowSymLinks\n\
    DirectoryIndex index.php\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>" > /etc/apache2/conf-available/phpmyadmin.conf \
 && a2enconf phpmyadmin

# Enable Apache mods
RUN a2enmod rewrite headers

# Fix ownership and permissions
RUN chown -R www-data:www-data /var/www/html /usr/share/phpmyadmin \
 && chmod -R 755 /var/www/html /usr/share/phpmyadmin

EXPOSE 80

