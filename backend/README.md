# Coiny — Backend API

Laravel JSON API for Coiny, using Sanctum SPA (cookie) auth. The contract lives in `../CLAUDE.md`.

## First-time setup

```bash
composer install
cp .env.example .env && php artisan key:generate
# set DB_USERNAME / DB_PASSWORD in .env, then:
mysql -u root -p -e "CREATE DATABASE coiny CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
                     CREATE DATABASE coiny_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
php artisan migrate:fresh --seed   # demo login: demo@coiny.test / password
php artisan serve                  # http://127.0.0.1:8000
```

## Demo account

`php artisan migrate:fresh --seed` creates a demo user with a few months of transactions:

| Email | Password |
|---|---|
| `demo@coiny.test` | `password` |

Log in at http://localhost:5173 with the frontend dev server running (`npm run dev` in `../frontend`).
These are local seed credentials only — never seed this account in production.

Tests run against `coiny_test` (see `phpunit.xml`):

```bash
php artisan test
./vendor/bin/pint --test
```
