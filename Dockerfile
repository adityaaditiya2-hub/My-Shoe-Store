FROM php:8.2-apache

# Enable Apache URL rewriting
RUN a2enmod rewrite

# Install SQLite dependencies required for the database
RUN apt-get update && apt-get install -y libsqlite3-dev sqlite3
RUN docker-php-ext-install pdo pdo_sqlite

# Copy all project files into the Apache web root
COPY . /var/www/html/

# Create necessary directories and set full permissions so SQLite and sessions can be written to
RUN mkdir -p /var/www/html/sessions /var/www/html/database
RUN chown -R www-data:www-data /var/www/html/sessions /var/www/html/database
RUN chmod -R 777 /var/www/html/sessions /var/www/html/database

EXPOSE 80
