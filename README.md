<p align="center">
  <a href="https://laravel.com" target="_blank">
    <img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo">
  </a>
</p>

<p align="center">
  <a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
  <a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
  <a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
  <a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## 📦 About Laravel

Laravel is a web application framework with expressive, elegant syntax. It makes web development a joy by easing common tasks like:

- [Simple, fast routing engine](https://laravel.com/docs/routing)  
- [Powerful dependency injection container](https://laravel.com/docs/container)  
- [Session and cache backends](https://laravel.com/docs/session)  
- [Eloquent ORM](https://laravel.com/docs/eloquent)  
- [Schema migrations](https://laravel.com/docs/migrations)  
- [Queue workers & job handling](https://laravel.com/docs/queues)  
- [Event broadcasting](https://laravel.com/docs/broadcasting)

Laravel is robust, scalable, and ready for enterprise-grade apps.

---

## ⚙️ Laravel Production Deployment Script

This script is intended to streamline your Laravel app deployment process for a production server.

### 🔧 `deploy.sh`

```bash
#!/bin/bash

cd /var/www/itrend-commerce || exit

echo "🔐 Please enter your sudo password..."
sudo -v

echo "🔒 Putting app in maintenance mode..."
sudo php artisan down

echo "📥 Pulling latest code..."
sudo git pull origin main

echo "📂 Running migrations..."
sudo php artisan migrate

echo "🧼 Setting correct permissions..."
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
sudo chmod -R 775 storage/logs

echo "🧹 Clearing Laravel caches..."
sudo php artisan config:clear
sudo php artisan route:clear
sudo php artisan view:clear
sudo php artisan cache:clear

echo "📦 Caching configuration..."
sudo php artisan config:cache

echo "📂 Running migrations (forced)..."
sudo php artisan migrate --force

echo "♻️ Restarting queue workers..."
sudo php artisan queue:restart

echo "🔁 Restarting Supervisor..."
sudo supervisorctl restart all

echo "🔓 Bringing app back online..."
sudo php artisan up

echo "✅ Deployment completed successfully!"
```

🧠 Redis Cache Setup (Local & Server)

This project uses Redis for caching (including cache tags).
Because cache tags require Redis, the setup differs slightly between local Windows and production server.

🖥️ Local Redis Setup (Windows)

Since Redis does not run natively on Windows and we use cache tags, we connect to Redis using one of the following supported methods.

✅ Recommended: External Redis (Redis Cloud / Server Redis)

We connect Laravel locally to an external Redis instance (same or separate from production).

🔹 Composer dependency (required locally)
```bash
composer require predis/predis


predis is used only in local development.
Production continues to use phpredis.

🔹 Local .env (Windows / Laragon)
CACHE_DRIVER=redis
REDIS_CLIENT=predis

REDIS_HOST=<redis-host>
REDIS_PORT=<redis-port>
REDIS_PASSWORD=<redis-password>

REDIS_DB=0
REDIS_CACHE_DB=0
```

⚠️ Important

Redis Cloud supports DB 0 only

REDIS_DB and REDIS_CACHE_DB must be 0

Do not put port inside REDIS_HOST

🔹 Clear & verify
```bash
php artisan config:clear
php artisan cache:clear
```

Verify Redis + tags:
```bash
php artisan tinker
```
```bash
Cache::put('redis_test', 'ok', 60);
Cache::get('redis_test');

Cache::tags(['ads_overview'])->put('tag_test', 1, 60);
Cache::tags(['ads_overview'])->get('tag_test');
```
If values return correctly → Redis is working.

🖥️ Production Server Redis Setup (Linux)

On the server we use phpredis (native PHP extension) for best performance.

🔹 Server .env
```bash
CACHE_DRIVER=redis
REDIS_CLIENT=phpredis

REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=null

REDIS_DB=0
REDIS_CACHE_DB=0
```
🔹 Required PHP extension (server)
```bash
php -m | grep redis
```

If missing:
```bash
sudo apt install php-redis
sudo systemctl restart php8.x-fpm
```
🔹 Clear cache (server)
```bash
php artisan cache:clear
php artisan config:clear
php artisan config:cache

````
Restart workers if applicable:
```bash
php artisan queue:restart
sudo supervisorctl restart all
```
🧹 Clearing Redis Cache
Clear only Laravel cache
```bash
php artisan cache:clear
```
Clear module-specific cache (tags)
```bash
Cache::tags(['ads_overview'])->flush();
```
⚠️ Clear entire Redis DB (server only, use with caution)
```bash
redis-cli FLUSHDB
```

⚠️ Do NOT use FLUSHDB if Redis is shared for queues/sessions.

✅ Redis Client Strategy (Important)
Environment	Redis Client
Local (Windows)	predis
Production (Linux)	phpredis

This is fully supported by Laravel and safe.
The unused client has zero impact on performance or security.

---
## 🧵 Queue Workers

If your Laravel app uses queued jobs (like emails, report generation, notifications), make sure queue workers are properly configured and running.

📖 Docs: [Laravel Queues – Running the Queue Worker](https://laravel.com/docs/queues#running-the-queue-worker)

---

## 🛠️ Supervisor (Optional)

Supervisor is a process monitor used in production to keep Laravel queue workers alive and restarted if they fail.

📖 Docs: [Laravel Queues – Supervisor Configuration](https://laravel.com/docs/queues#supervisor-configuration)

---
