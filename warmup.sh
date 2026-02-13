#!/bin/bash
set -e

echo "🐘 high-perf-poc Warmup Utility"
echo "------------------------------"

# 1. Environment file
if [ ! -f .env ]; then
    echo "📄 Creating .env from .env.example..."
    cp .env.example .env
fi

# 2. Docker Up
echo "🐳 Starting Docker containers..."
docker-compose up -d --build

# 3. Dependencies
echo "📦 Installing PHP dependencies inside the container..."
docker exec dv-high-perf-poc-app-1 composer install --no-interaction --optimize-autoloader

# 4. Data Seeding
echo "🚀 Seeding 10,000,000 keys into DragonflyDB (PHP Worker Mode)..."
docker exec dv-high-perf-poc-app-1 php seed.php

echo "------------------------------"
echo "✅ SUCCESS: Project is healthy and running!"
echo "🔗 Dashboard: http://localhost"
echo "🔗 Session Check: http://localhost/user/session/1"
echo "------------------------------"
