#!/bin/bash
set -e

# Ждём готовности MySQL
echo "Waiting for MySQL..."
while ! php -r "
    try {
        new PDO(
            'mysql:host=${MYSQL_HOST:-mysql};dbname=${MYSQL_DATABASE:-ubtdatabase}',
            '${MYSQL_USER:-ubt}',
            '${MYSQL_PASSWORD:-secret}',
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        exit(0);
    } catch (Exception \$e) {
        exit(1);
    }
" 2>/dev/null; do
    sleep 2
done
echo "MySQL is ready."

# Установка зависимостей, если нет vendor
if [ ! -f vendor/autoload.php ]; then
    echo "Running composer install..."
    composer install --no-interaction --prefer-dist --optimize-autoloader
fi

# Миграции при каждом запуске контейнера
echo "Running migrations..."
php yii migrate --interactive=0

# Папки для записи веб-процессом (www-data)
mkdir -p web/assets web/runtime
chown -R www-data:www-data web/assets web/runtime 2>/dev/null || true
chmod -R 775 web/assets web/runtime 2>/dev/null || true

# PHP-FPM в foreground
exec php-fpm -F