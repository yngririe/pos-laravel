# Architecture

**Reviewed:** 27 November 2026

## Stack

- Laravel **12** (`composer.json` requires `^12.0`; `composer.lock` resolves `laravel/framework` v12.70.0) on PHP `^8.3`, with `config.platform.php` pinned to `8.3.0` so Composer resolves for the host's PHP.
- Server-rendered Blade pages plus per-page static CSS and JavaScript in `public/modules/{admin,auth,cashier,shared}/<page>/` (grouped by role, like the controllers and views). `modules/shared/base/homepage.css` is the base stylesheet loaded by every page. Vite and Tailwind are scaffolded (`vite.config.js`, `resources/css/app.css`, `resources/js/app.js`) but the POS pages do not use them.
- MySQL or MariaDB. Several queries use MySQL-only functions (`CONVERT_TZ`, `GROUP_CONCAT`), so SQLite is not a supported runtime.
- Laravel web guard with file sessions. No Sanctum, no API tokens, no React/Vue, no separate API. Keep it that way unless the owner asks.
- The Laravel 13 `composer.json`/`composer.lock` backups that used to sit in the repository root were removed on 27 November 2026. They remain in git history (the commit before `6c29c14`).

## Request flow

`route → middleware → controller → Pos helper / model / raw SQL → Blade view → static JS in public/modules`

| Layer | Location |
|---|---|
| Routes | `routes/web.php` (the only route file in use) |
| Middleware | `auth`, `guest`, `throttle`, and `App\Http\Middleware\EnsureAdmin` (`role === 'admin'`) |
| Controllers | `app/Http/Controllers/{Auth,Admin,Cashier}/` plus `ProfileController` at the root because both roles use it |
| Models | `app/Models/Sales/{Order,OrderItem}`, `Catalog/MenuItem`, `Settings/ReceiptSetting`, `User` |
| Helpers | `app/Support/Pos.php` — branch scope, UTC↔Manila time, business-day SQL, shared view data, raw-query helpers, password rule |
| Views | `resources/views/{admin,auth,cashier,emails,shared}/` |
| Browser code | `public/modules/<role>/<page>/<page>.{css,js}`, `public/assets/` (images, `escpos_bluetooth.js`, `csrf.js`, `main.js`, `order-notifications.js`, `pwa_register.js`) |

Eloquent infers table names from the class name only, so moving a model between namespaces does not change its table.

## View names

| View | Used by |
|---|---|
| `auth.login`, `auth.forgot`, `auth.reset`, `emails.reset-password` | Auth controllers |
| `cashier.homepage`, `cashier.orders`, `cashier.served` | Cashier controllers |
| `admin.statistics.index`, `admin.settings.index`, `admin.cashiers.create`, `admin.branches.create` | Admin controllers |
| `shared.profile`, `shared.pos-shell` | `ProfileController`, `/home` route |

## Uploads

User uploads are saved straight into `public/assets/images/`: avatars in `avatars/`, menu photos in `menu/`, the store logo in `settings/`. The database stores the relative path (for example `assets/images/menu/menu_….jpg`) and pages render it as `$base_url . path`. These folders are gitignored (only `avatars/.gitkeep` is tracked), so uploaded photos never enter the repository. They are not backed up by git: copy these folders when moving the app to another server.

Uploads were deliberately **not** moved to `storage/app/public` with `storage:link`. That would change three upload methods, every place that displays an image and the stored paths in existing rows, and it needs a symlink that shared hosting and Windows often do not allow. Revisit it only if the deployment target changes.

## Time

Everything is stored in UTC and shown in `Asia/Manila`. The business day starts at **3:00 PM Manila time** (see [order flow decisions](order-flow-decisions.md)). Use the `Pos` helpers; do not use `DATE(created_at)`.

## Branch model

An admin account is a branch (`branch_id` equals the admin's own `id`). Cashiers copy their admin's `branch_id`. `Pos::branchId()` is the only source of branch scope.

## Browser shell

`/home` serves a thin page that holds the Bluetooth printer connection and loads the real POS pages in an iframe (`/pos/home`). See [POS shell, printer and PWA](features/shared/pos-shell-and-pwa/spec.md).

## Source evidence

`routes/web.php`, `bootstrap/app.php`, `app/Support/Pos.php`, `composer.json`, `composer.lock`, `config/pos.php`.
