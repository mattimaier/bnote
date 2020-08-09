FROM php:7.1.2-apache 

# To access a MySQL database the according PHP module must be installed
RUN docker-php-ext-install mysqli

# The .htaccess file in the export API folder contains rewrite rules. Those
# rules are not supported by Apache by default. The according Apache module
# must be installed. Otherwise the BNote-App will not work.
RUN a2enmod rewrite

# The SimpleImage class used by website module and share module requires the GD
# library for basic image processing. The base Docker image does not include
# that library. The PHP GD library module itself needs libraries to handle PNG
# and JPEG.
RUN apt-get update -y && apt-get install -y libpng-dev libjpeg-dev
RUN docker-php-ext-configure gd --with-jpeg-dir
RUN docker-php-ext-install gd
