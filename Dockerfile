# Use an official PHP runtime as a parent image
FROM php:8.1-apache

# Install the PHP extensions we need
RUN apt-get update && apt-get install -y \
    libzip-dev \
    zip \
    && docker-php-ext-install pdo pdo_mysql zip

# Copy the PHP application files to the container
COPY . /var/www/html/

# Adjust file permissions
RUN chown -R www-data:www-data /var/www/php/
RUN chmod -R 755 /var/www/php/

# Set environment variables for database connection (Render.com)
ENV DB_HOST=${DB_HOST}
ENV DB_USER=${DB_USER}
ENV DB_PASSWORD=${DB_PASSWORD}
ENV DB_NAME=${DB_NAME}

# Expose port 80 (Apache default)
EXPOSE 80

# Start Apache
CMD ["apache2-foreground"]
