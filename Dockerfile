# Imagen base oficial: PHP 8.2 con Apache ya configurado como servidor web
FROM php:8.2-apache

# Extensiones de base de datos necesarias para conectar con MySQL 8.0
RUN docker-php-ext-install mysqli pdo_mysql

# Codigo fuente de la aplicacion dentro del DocumentRoot de Apache
COPY ./src/ /var/www/html/

# Apache sirve en el puerto 80 dentro del contenedor
EXPOSE 80
