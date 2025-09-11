#!/bin/bash
echo "🚀 Desplegando en LOCAL..."
cp .env.local .env          # <- COPIA el archivo local al .env activo
docker compose down
docker compose up -d --build
echo "✅ LOCAL listo en http://localhost:8001"