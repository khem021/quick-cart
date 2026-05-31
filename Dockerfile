FROM php:8.2-apache

# Install MySQLi extension
RUN docker-php-ext-install mysqli

# Allow .htaccess overrides and enable mod_rewrite
RUN sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf \
    && a2enmod rewrite

# Copy application files
COPY . /var/www/html/

# Create upload directories and set permissions
RUN mkdir -p /var/www/html/assets/products \
    && mkdir -p /var/www/html/assets/ids \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 775 /var/www/html/assets/products \
    && chmod -R 775 /var/www/html/assets/ids

EXPOSE 80
