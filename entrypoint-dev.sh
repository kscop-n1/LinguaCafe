#!/bin/sh

# Folders needed for persistence
folder_paths="
    ./storage/app/dictionaries
    ./storage/app/fonts
    ./storage/app/images/book_images
    ./storage/app/public
    ./storage/app/temp/dictionaries
    ./storage/framework/cache/data
    ./storage/framework/sessions
    ./storage/framework/testing
    ./storage/framework/views
    ./storage/logs
    ./storage/backup
"

# Ensure the folders exist
for folder_path in $folder_paths; do
    if [ ! -d "$folder_path" ]; then
        mkdir -p "$folder_path"
        echo "Folder created: $folder_path"
    else
        echo "Folder already exists: $folder_path"
    fi
done

composer install \
    && npm install

retry_count=0

wait_for_database() {
    echo "Waiting for database at ${DB_HOST}:${DB_PORT}..."

    while ! php -r '
$host = getenv("DB_HOST");
$port = (int) (getenv("DB_PORT") ?: 3306);
$errno = 0;
$errstr = "";
$connection = @fsockopen($host, $port, $errno, $errstr, 2);

if ($connection === false) {
    fwrite(STDERR, $errstr . PHP_EOL);
    exit(1);
}

fclose($connection);
exit(0);
'; do
        sleep 5
    done
}

wait_for_database

while [ $retry_count -lt 40 ] && ! php artisan migrate --force; do
    sleep 15
    retry_count=$((retry_count+1))
done

php artisan db:seed --force

exec "$@"
