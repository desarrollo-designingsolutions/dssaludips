# Usa PHP CLI basado en Debian (no Alpine) para tener compatibilidad total
FROM php:8.3-cli-bullseye

# Instalar supervisor y dependencias comunes
RUN apt-get update && \
    apt-get install -y supervisor && \
    rm -rf /var/lib/apt/lists/*

RUN apt-get update && apt-get install -y \
    libpng-dev libjpeg-dev libonig-dev libxml2-dev zip unzip git curl \
    && docker-php-ext-install pdo_mysql sockets pcntl

# Crear directorios necesarios para supervisor
RUN mkdir -p /etc/supervisor/conf.d \
    /var/log/supervisor \
    /var/run/supervisor

# Copiar configuración de Supervisor principal
COPY ./docker/supervisor/supervisord.conf /etc/supervisor/supervisord.conf

# Copiar configuraciones específicas para cada tipo de contenedor
# COPY ./docker/supervisor/laravel-worker-reverb.conf /etc/supervisor/conf.d/laravel-worker-reverb.conf
# COPY ./docker/supervisor/laravel-worker-queue.conf /etc/supervisor/conf.d/laravel-worker-queue.conf


# Definir directorio de trabajo
WORKDIR /var/www/html

# Supervisord será el proceso principal (importante el -n para no hacer daemonize)
CMD ["/usr/bin/supervisord", "-n", "-c", "/etc/supervisor/supervisord.conf"]
 