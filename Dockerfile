FROM php:8.2-apache

# Copy your application code into the container
COPY . /var/www/html/

# Install any necessary PHP extensions (example)
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Enable mod_rewrite (if needed)
RUN a2enmod rewrite

# Expose port 80
EXPOSE 80

# (Optional) Set environment variables
ENV APP_ENV production
