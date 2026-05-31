FROM php:8.2-apache

# Install MySQLi extension
RUN docker-php-ext-install mysqli

# Allow .htaccess overrides and enable mod_rewrite
RUN sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf \
    && a2enmod rewrite

# Copy application files
COPY . /var/www/html/

# Apply custom PHP config
RUN cp /var/www/html/php.ini /usr/local/etc/php/php.ini

# Create upload directories, copy seed product images, set permissions
RUN mkdir -p /var/www/html/assets/products \
    && mkdir -p /var/www/html/assets/ids \
    && cp /var/www/html/assets/images/sample_1.svg /var/www/html/assets/products/sample_1.svg \
    && cp /var/www/html/assets/images/sample_2.svg /var/www/html/assets/products/sample_2.svg \
    && cp /var/www/html/assets/images/sample_3.svg /var/www/html/assets/products/sample_3.svg \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 775 /var/www/html/assets/products \
    && chmod -R 775 /var/www/html/assets/ids

EXPOSE 80
