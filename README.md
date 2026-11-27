# Twist & Roll POS

A branch-aware point-of-sale system for Twist & Roll, built with Laravel 12, PHP 8.3, Blade and MySQL/MariaDB. Cashiers take dine-in, take-out, pick-up and delivery orders (cash or GCash), print receipts over Bluetooth, and serve or void orders. Admins own a branch: they manage the menu and receipt settings, view statistics, and create cashier accounts. It installs as a PWA.

## Quick start

```bash
composer install
cp .env.example .env        # then set DB_* and mail values
php artisan key:generate
php artisan migrate --seed  # or import an existing database, see docs/setup.md
php artisan serve
```

Full steps, `.env` values and the legacy-data notes are in [docs/setup.md](docs/setup.md).

## Project layout

```text
AGENTS.md       working rules for agents and contributors
docs/           documentation, specs and progress logs  (start at docs/README.md)
app/Http/Controllers/{Admin,Auth,Cashier}/   controllers grouped by actor
app/Models/{Sales,Catalog,Settings}/         models grouped by domain
app/Support/Pos.php                          branch scope, time and SQL helpers
resources/views/{admin,auth,cashier,emails,shared}/
public/modules/{admin,auth,cashier,shared}/<page>/   per-page CSS and JS, grouped by role
routes/web.php  database/migrations/  config/pos.php
```

## Documentation

Start with [docs/README.md](docs/README.md). Progress is logged in [docs/logs/](docs/logs/).
