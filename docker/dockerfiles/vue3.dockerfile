FROM node:24-alpine

WORKDIR /var/www/html

# Copiar package.json y package-lock.json
COPY frontend/package.json ./
COPY frontend .

# Instalar dependencias con npm
RUN npm i --force

RUN npm install --save-dev laravel-echo pusher-js --force
RUN npm i @tinymce/tinymce-vue --force

# Iniciar la aplicación en modo desarrollo
# CMD npm run dev -- --host
CMD node -v && tail -f /dev/null



