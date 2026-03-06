FROM php:8.3-apache

# Instalar dependencias del sistema (Debian/Apt)
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libzip-dev \
    libjpeg62-turbo-dev \
    libwebp-dev \
    libfreetype6-dev \
    libicu-dev \
    libxslt1-dev \
    netcat-openbsd \
    nano \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Instalar extensiones de PHP
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install gd mysqli pdo_mysql intl bcmath opcache exif zip xsl pcntl

# Instalar Redis
RUN pecl install redis && docker-php-ext-enable redis

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Configurar Apache
# Habilitar mod_rewrite para Laravel/PHP
RUN a2enmod rewrite

# Cambiar el DocumentRoot de Apache a /var/www/html/public (típico de Laravel)
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Crear directorio de trabajo
WORKDIR /var/www/html

# Copiar el código de la aplicación (opcional si se usa volúmenes en compose)
# COPY ./backend /var/www/html

# Permisos para Laravel (el usuario por defecto en este imagen es www-data)
RUN chown -R www-data:www-data /var/www/html

# Punto de entrada
COPY ./docker/entrypoint/php.entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/php.entrypoint.sh

ENTRYPOINT ["/usr/local/bin/php.entrypoint.sh"]
