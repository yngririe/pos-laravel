# Twist & Roll POS — Laravel version

This folder is an **overlay**: it contains only the files that are specific to your POS.
You drop it on top of a fresh Laravel 11 or 12 project.

## 1. Create the Laravel project and copy this overlay over it

```bash
composer create-project laravel/laravel pos-laravel
cd pos-laravel
# copy everything from this folder into the project, accepting overwrites
# (routes/web.php, app/Models/User.php, app/Providers/AppServiceProvider.php, DatabaseSeeder.php)
```

Delete Laravel's default migrations (they create a different `users` table):

```bash
rm database/migrations/0001_01_01_00000*.php
rm database/factories/UserFactory.php
```

## 2. Configure `.env`

```env
APP_URL=http://localhost:8000
APP_TIMEZONE=UTC            # keep UTC: the app stores UTC and shows Asia/Manila

DB_CONNECTION=mysql         # use mariadb if you prefer; both are handled
DB_HOST=127.0.0.1
DB_DATABASE=pos_system
DB_USERNAME=root
DB_PASSWORD=

SESSION_DRIVER=file
CACHE_STORE=file
QUEUE_CONNECTION=sync

# Password-reset emails (Gmail needs an App Password)
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_ENCRYPTION=tls
MAIL_USERNAME=your-address@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_FROM_ADDRESS=your-address@gmail.com
MAIL_FROM_NAME="Twist & Roll"

# Needed only for /branches/create (leave empty to disable that page)
BRANCH_SETUP_KEY=
```

## 3. Database — choose ONE

**A. Keep your existing data** (users, orders, settings)

```bash
# import pos_system.sql with phpMyAdmin or:  mysql -u root pos_system < pos_system.sql
php artisan migrate        # skips tables that already exist
```
Then run this **once**. Your old `served_at` values were saved in Manila time, the new code stores UTC:
```sql
UPDATE orders SET served_at = served_at - INTERVAL 8 HOUR WHERE served_at IS NOT NULL;
```
Existing logins keep working (same bcrypt hashes).

**B. Start clean**

```bash
php artisan migrate --seed   # menu + settings + an "admin" user (password is printed once)
```

## 4. Run

```bash
php artisan key:generate
php artisan serve
```
Open http://localhost:8000. Menu images live in `public/assets/images/`.

## Route map (old file -> new route)

| Old | New |
|---|---|
| index.php (login/logout) | `/login`, `/logout` |
| modules/homepage | `/home`, `/receipt-settings` |
| place_order / update_order / serve_order / void_order | `POST /orders`, `/orders/update`, `/orders/serve`, `/orders/void` |
| modules/orders, served | `/orders`, `/served` |
| modules/statistics (+ statistics_ajax.php) | `/statistics` (admin), `/statistics/report` |
| modules/profile | `/profile` |
| signup.php | `/cashiers/create` (admin) |
| create_branch.php | `/branches/create` |
| modules/forgot-password | `/forgot-password`, `/reset-password` |
| delete_order.php | removed (nothing called it, and it had no login/branch check) |
