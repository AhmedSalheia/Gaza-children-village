#!/bin/bash
# Post-merge setup script.
# Runs automatically after every task merge.
# Must be: idempotent, non-interactive (stdin is closed), and fast.
set -e

echo "--- composer install ---"
composer install --no-interaction --prefer-dist --no-progress

echo "--- php artisan migrate ---"
php artisan migrate --force --no-interaction

echo "--- npm ci ---"
npm ci --prefer-offline --no-audit --no-fund

echo "--- npm run build ---"
npm run build

echo "--- done ---"
