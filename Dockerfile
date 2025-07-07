FROM php:8.2-apache


# Install necessary PHP extensions for MySQL connection
# It's good practice to install both mysqli and pdo_mysql
RUN docker-php-ext-install mysqli pdo_mysql && \
    docker-php-ext-enable mysqli pdo_mysql

# Enable Apache rewrite module if your application uses clean URLs
RUN a2enmod rewrite

# Copy your PHP application code from the local 'html' directory
# into the Apache document root inside the container.
COPY ./app/ /var/www/html/

# Set correct permissions for Apache. Important for some applications.
RUN chown -R www-data:www-data /var/www/html

# Expose port 80, indicating the container listens on this port.
EXPOSE 80

# This is the default command for php:apache images, ensures Apache stays in foreground.
# You can omit it if you like, as the base image provides it, but being explicit is fine.
CMD [ "apache2-foreground" ]