FROM nginx:stable-alpine

# Copiar la configuración de Nginx
COPY ./docker/nginx/default.conf /etc/nginx/conf.d/default.conf

# Copiar los archivos de la aplicación
COPY ./backend /var/www/html