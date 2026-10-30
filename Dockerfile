# ==============================================================================
# Dockerfile para despliegue de Sistema de Gestión de Pasantes (SGP) en Render.com
# PHP 8.2 con Apache, Composer, extensiones requeridas y soporte mod_rewrite
# ==============================================================================
FROM php:8.2-apache

# Actualizar repositorios e instalar dependencias del sistema requeridas
RUN apt-get update && apt-get install -y --no-install-recommends \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    libicu-dev \
    zip \
    unzip \
    ca-certificates \
    git \
    && rm -rf /var/lib/apt/lists/*

# Configurar e instalar extensiones de PHP necesarias para el SGP
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
    pdo \
    pdo_mysql \
    gd \
    zip \
    intl \
    opcache

# Copiar Composer oficial para instalar dependencias de reportes (DomPDF, TCPDF, etc.)
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Activar módulos de Apache requeridos
RUN a2enmod rewrite headers ssl

# Configurar Apache DocumentRoot a /var/www/html/public
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Habilitar AllowOverride All para soportar .htaccess
RUN echo '<Directory /var/www/html/public>\n\
    Options -Indexes +FollowSymLinks\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>' > /etc/apache2/conf-available/sgp-override.conf \
    && a2enconf sgp-override

# Ajustes de PHP para producción
RUN echo 'upload_max_filesize = 15M\n\
post_max_size = 20M\n\
memory_limit = 256M\n\
date.timezone = America/Caracas\n\
display_errors = Off\n\
log_errors = On' > /usr/local/etc/php/conf.d/sgp-custom.ini

# Establecer directorio de trabajo
WORKDIR /var/www/html

# Copiar dependencias de Composer primero para aprovechar la caché de Docker
COPY composer.json composer.lock* /var/www/html/
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader || true

# Copiar el código fuente completo de la aplicación
COPY . /var/www/html/

# Re-generar autoload optimizado
RUN composer dump-autoload --optimize --no-dev

# Asegurar permisos en carpetas de uploads, logs y almacenamiento temporal
RUN mkdir -p /var/www/html/public/uploads \
    && mkdir -p /var/www/html/public/img/avatars \
    && mkdir -p /var/www/html/storage/logs \
    && mkdir -p /var/www/html/storage/updates \
    && mkdir -p /var/www/html/app/logs \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/public/uploads /var/www/html/public/img/avatars /var/www/html/storage /var/www/html/app/logs

EXPOSE 80

CMD ["apache2-foreground"]
