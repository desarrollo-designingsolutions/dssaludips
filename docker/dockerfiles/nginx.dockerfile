FROM nginx:stable-alpine
WORKDIR /etc/nginx/conf.d
COPY docker/nginx/default.conf .
RUN mv default.conf default.conf
WORKDIR /var/www/html
COPY ../../backend .