FROM php:8.1-apache

# Install PostgreSQL dependencies and PHP extensions
RUN apt-get update && apt-get install -y libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Copy application files
COPY . /var/www/html/

# Set the working directory
WORKDIR /var/www/html

# Copy public files to Apache web root
RUN cp -r public/* /var/www/html/ && \
    rm -rf public

# Apache configuration to secure includes directory
RUN echo '<Directory "/var/www/html/includes">\n\
    Require all denied\n\
</Directory>' >> /etc/apache2/apache2.conf

# Set permissions
RUN chown -R www-data:www-data /var/www/html

# Expose port 80
EXPOSE 80

# Start Apache
CMD ["apache2-foreground"]