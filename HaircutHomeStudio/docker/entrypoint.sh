#!/bin/sh
set -e

echo "Configurando permisos de almacenamiento..."
mkdir -p /app/storage/logs /app/bootstrap/cache
chown -R www-data:www-data /app/storage /app/bootstrap/cache
chmod -R 775 /app/storage /app/bootstrap/cache

echo "Waiting for MySQL..."
counter=0
while ! nc -z hhs-db 3306; do
  counter=$((counter+1))
  if [ $counter -gt 30 ]; then
    echo "ERROR: MySQL did not start"
    exit 1
  fi
  echo "MySQL not ready (attempt $counter/30)..."
  sleep 1
done
echo "✓ MySQL is ready"

echo "Running migrations..."
php /app/artisan migrate --force

echo "Checking initial data..."
usuarios_count=$(php -r 'require "/app/vendor/autoload.php"; $app = require "/app/bootstrap/app.php"; $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap(); echo Illuminate\Support\Facades\DB::table("USUARIO")->count();')

if [ "$usuarios_count" -eq 0 ]; then
  echo "Database is empty; loading seed data..."
  php /app/artisan db:seed --force
else
  echo "Database already has users; skipping seed data."
fi

echo "Creating storage link..."
php /app/artisan storage:link --force

echo "Starting application..."
exec supervisord -c /etc/supervisord.conf
