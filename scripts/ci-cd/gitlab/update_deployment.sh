#!/bin/bash

# add php when remote ssh shell script
export PATH=$PATH:/opt/plesk/php/8.2/bin

# clear caches to force new data
rm -f bootstrap/cache/config.php
php artisan cache:clear
php artisan route:clear

# update db
php artisan migrate --force

# cache laravel components
php artisan config:cache
php artisan event:cache
#php artisan route:cache
php artisan view:cache

# laravel scout
php artisan scout:sync-index-settings

# restart queue
php artisan queue:restart
