#!/bin/bash
export PHP="/c/laragon/bin/php/php-8.3.30-Win32-vs16-x64/php.exe"
export PATH="/c/laragon/bin/nodejs/node-v22:/c/laragon/bin/php/php-8.3.30-Win32-vs16-x64:$PATH"

cd /z/Dropshipping/lesrats

echo "[PHP] Lancement de php artisan serve..."
"$PHP" artisan serve &
PHP_PID=$!

echo "[VITE] Lancement de Vite..."
node node_modules/vite/bin/vite.js &
VITE_PID=$!

echo "PHP PID: $PHP_PID"
echo "Vite PID: $VITE_PID"

wait
