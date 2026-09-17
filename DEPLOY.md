# Deploying

No services beyond PHP: no queue worker, no Redis, no search server. Any host that runs PHP 8.5 with cron will do.

## 1. Install

```bash
git clone <repo> && cd wonderful-task
composer install --no-dev --optimize-autoloader
cp .env.example .env
php artisan key:generate
```

## 2. Configure `.env`

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-host

DB_CONNECTION=sqlite            # default; creates database/database.sqlite
# or MySQL:
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_DATABASE=mariacare
# DB_USERNAME=...
# DB_PASSWORD=...

CACHE_STORE=database            # holds the fuzzy-match candidate cache; file works too
QUEUE_CONNECTION=sync           # nothing is queued

DOCTORS_SOURCE_URL=https://example.com/doctors.json   # leave empty to use the bundled file
```

## 3. Migrate and load data

```bash
touch database/database.sqlite   # SQLite only
php artisan migrate --force
php artisan doctors:ingest
php artisan config:cache && php artisan route:cache
```

## 4. Schedule the daily refresh

One cron line runs the scheduler; it triggers `doctors:ingest` at 03:00 server time:

```cron
* * * * * cd /path/to/wonderful-task && php artisan schedule:run >> /dev/null 2>&1
```

## 5. Point the web server at `public/`

Standard Laravel: document root is `public/`, rewrite everything to `index.php`.

## Operating

- Health: `GET /up`
- Refresh by hand: `php artisan doctors:ingest` (exit code 1 and a log entry on failure; old data is kept)
- Logs: `storage/logs/laravel.log` — look for `Doctor ingest complete` / `Doctor ingest failed`
- After changing `.env`: `php artisan config:cache`
