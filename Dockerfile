# ==============================================================================
# Dockerfile para despliegue de Sistema de Gestión de Pasantes (SGP) en Render.com
# PHP 8.2 con Apache, Composer, fuentes TrueType y soporte mod_rewrite
# ==============================================================================
FROM php:8.2-apache

# Actualizar repositorios e instalar dependencias del sistema y fuentes TTF para CAPTCHA
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
    fonts-dejavu-core \
    fonts-liberation \
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

# Copiar configuración limpia de Apache VirtualHost con DocumentRoot en /public
COPY docker/apache-site.conf /etc/apache2/sites-available/000-default.conf

# Asegurar permisos globales para /var/www/ en apache2.conf
RUN echo '<Directory /var/www/>\n\
    Options FollowSymLinks\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>' >> /etc/apache2/apache2.conf

# Ajustes de PHP para producción
RUN echo 'upload_max_filesize = 15M\n\
post_max_size = 20M\n\
memory_limit = 256M\n\
variables_order = "EGPCS"\n\
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
