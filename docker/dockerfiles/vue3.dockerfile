FROM node:24-alpine

WORKDIR /var/www/html

# Copy package files for caching
COPY frontend/package*.json ./

# Install all dependencies (including dev dependencies)
RUN npm install --legacy-peer-deps

# Install additional packages
RUN npm install --save-dev laravel-echo pusher-js --legacy-peer-deps
RUN npm install @tinymce/tinymce-vue --legacy-peer-deps

# Copy application source
COPY frontend/ ./

# Expose Vite dev server port
EXPOSE 5173

# Start development server with hot reload
CMD ["npm", "run", "dev", "--", "--host", "0.0.0.0"]