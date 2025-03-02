FROM php:8.2-apache  # Use a PHP 8.2 image with Apache

# Copy your application code into the container
COPY . /var/www/html/

# Set the document root (if needed)
# <Directory /var/www/html/>
#     AllowOverride All
# </Directory>

# Install any necessary PHP extensions (example)
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Enable mod_rewrite (if needed)
RUN a2enmod rewrite

# Expose port 80
EXPOSE 80

# (Optional) Set environment variables
ENV APP_ENV production

#CMD ["apache2-foreground"] #not sure if needed.
