# Use Ubuntu 24.04 as base
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
    wget \
    unzip \
    && rm -rf /var/lib/apt/lists/*

# Download Adminer PHP file to /usr/share/adminer
RUN mkdir -p /usr/share/adminer && \
    wget "https://github.com/vrana/adminer/releases/download/v5.3.0/adminer-5.3.0.php" -O /usr/share/adminer/adminer.php

# Configure Apache to serve Adminer at /admin/database
RUN echo "Alias /admin/database /usr/share/adminer\n\
<Directory /usr/share/adminer>\n\
    Options FollowSymLinks\n\
    DirectoryIndex adminer.php\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>" > /etc/apache2/conf-available/adminer.conf

RUN a2enconf adminer
RUN a2enmod rewrite

# Copy your PHP app into /var/www/html if needed
COPY . /var/www/html/

EXPOSE 80

CMD ["apache2ctl", "-D", "FOREGROUND"]

