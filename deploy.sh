#!/bin/bash
echo "Starting deployment updates..."

# Clear Laravel caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Restart Octane and PM2
php artisan octane:reload
pm2 restart es4-solutions

# Optional: If you use FrankenPHP as a system service
# sudo systemctl restart frankenphp

echo "Deployment finished successfully!"
