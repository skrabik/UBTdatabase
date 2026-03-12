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

# Установка зависимостей, если vendor отсутствует или lock-файл обновился
if [ ! -f vendor/autoload.php ] || [ composer.lock -nt vendor/autoload.php ]; then
    echo "Running composer install..."
    composer install --no-interaction --prefer-dist --optimize-autoloader
fi

# Миграции при каждом запуске контейнера
echo "Running migrations..."
php yii migrate --interactive=0

# Папки для записи веб- и queue-процессом (www-data)
mkdir -p runtime runtime/queue web/assets
chown -R www-data:www-data runtime web/assets 2>/dev/null || true
chmod -R 775 runtime web/assets 2>/dev/null || true

# Если контейнеру передали команду, запускаем её вместо php-fpm
if [ "$#" -gt 0 ]; then
    exec "$@"
fi

# PHP-FPM в foreground по умолчанию
exec php-fpm -F