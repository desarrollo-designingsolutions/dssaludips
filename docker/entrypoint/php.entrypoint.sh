#!/bin/sh 

# Ejecutar migraciones y comandos de Laravel
cd /var/www/html

# Instalar dependencias si no existen
if [ ! -d "vendor" ]; then
    composer install --no-interaction --prefer-dist
fi

# Ejecutar script de permisos 
echo "🔧 Configurando permisos de Laravel..."

# Crear directorios necesarios si no existen
mkdir -p /var/www/html/storage/logs
mkdir -p /var/www/html/storage/framework/cache
mkdir -p /var/www/html/storage/framework/sessions
mkdir -p /var/www/html/storage/framework/views
mkdir -p /var/www/html/bootstrap/cache

# Configurar permisos para directorios de Laravel
echo "📁 Configurando permisos de directorios..."
chown -R www-data:www-data /var/www/html/storage
chown -R www-data:www-data /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage
chmod -R 775 /var/www/html/bootstrap/cache



# Esperar a que MySQL esté listo
echo "Esperando a MySQL..."
while ! nc -z mysql 3306; do
  sleep 1
done

echo "MySQL está listo!"

php artisan migrate



# Generar claves de Laravel Passport si no existen
echo "🔑 Verificando claves de Passport..."
if [ ! -f "/var/www/html/storage/oauth-private.key" ] || [ ! -f "/var/www/html/storage/oauth-public.key" ]; then
    echo "Generando claves de Passport..."
    cd /var/www/html
    php artisan passport:keys --force
    chown www-data:www-data /var/www/html/storage/oauth-*.key
    chmod 600 /var/www/html/storage/oauth-*.key
fi

php artisan key:generate

# Limpiar y optimizar cache
echo "🧹 Limpiando cache..."
cd /var/www/html
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

echo "✅ Permisos configurados correctamente!"

echo "🚀 Iniciando contenedor PHP..."

set -e
   
 
# Iniciar PHP-FPM
echo "🎯 Iniciando PHP-FPM..."
exec php-fpm

echo "🎯 Finalizado..."