FROM php:8.3-fpm-alpine

# Instalar dependencias del sistema
RUN apk add --no-cache \
    git \
    curl \
    mysql-client \
    msmtp \
    perl \
    wget \
    procps \
    shadow \
    libzip \
    libpng \
    libjpeg-turbo \
    libwebp \
    freetype \
    icu \
    netcat-openbsd \
    libxslt  # Asegúrate de que libxslt esté instalado

# Instalar dependencias para compilar extensiones
RUN apk add --no-cache --virtual build-essentials \
    icu-dev \
    icu-libs \
    zlib-dev \
    g++ \
    make \
    automake \
    autoconf \
    libzip-dev \
    libpng-dev \
    libwebp-dev \
    libjpeg-turbo-dev \
    libxslt-dev \
    freetype-dev && \
    docker-php-ext-configure gd --enable-gd --with-freetype --with-jpeg --with-webp && \
    docker-php-ext-install gd mysqli pdo_mysql intl bcmath opcache exif zip xsl pcntl && \
    apk del icu-dev zlib-dev g++ make automake autoconf libzip-dev libpng-dev libwebp-dev libjpeg-turbo-dev freetype-dev && \
    rm -rf /usr/src/php*

# Instalar Redis
RUN apk add --no-cache pcre-dev $PHPIZE_DEPS && \
    pecl install redis && \
    docker-php-ext-enable redis.so && \
    apk del pcre-dev $PHPIZE_DEPS

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Crear directorio de trabajo
WORKDIR /var/www/html

# Copiar el código de la aplicación
COPY ./backend /var/www/html

# Permisos para Laravel
RUN addgroup -g 1000 laravel && adduser -G laravel -g laravel -s /bin/sh -D laravel
RUN chown -R laravel:laravel /var/www/html

# Punto de entrada para ejecutar comandos cuando el contenedor inicie
COPY ./docker/entrypoint/php.entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/php.entrypoint.sh

ENTRYPOINT ["/usr/local/bin/php.entrypoint.sh"]
CMD ["php-fpm"]
