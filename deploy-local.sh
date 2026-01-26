#!/bin/bash
echo "🚀 Desplegando en LOCAL..."

# Check if .env file exists
if [ ! -f .env ]; then
    if [ -f .env.local ]; then
        cp .env.local .env 
        exit 1
    else
        exit 1
    fi
fi

docker compose down
docker compose up -d --build
echo "✅ AWS listo en http://localhost:8001"