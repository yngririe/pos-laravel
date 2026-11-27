# Domain and Feature Status

**Reviewed:** 27 November 2026. This is a code-based snapshot, not a promise of production readiness. Nothing here was run or browser-tested during the restructure; owner verification is pending.

| Domain | Status | Implemented in code | Main gaps / next work |
|---|---|---|---|
| Admin | Implemented | Receipt settings and logo; menu add/edit/delete with images and availability; statistics (live shift, sales archive, charts, JSON report with optional all-branch merge); petty cash and expense entry; cashier account creation; branch + owner creation behind a setup key | `receipt_settings` is one global row; merged report is open to any admin; no cashier list/edit/disable screen; no audit log |
| Cashier | Implemented | POS ordering (four order types, cash/GCash, discounts, receipt printing over Bluetooth), pending orders (edit, serve, void), served-order history, admin-order notifications | Serve/void do not check current status; GCash reference numbers are never stored (the columns are always written as `null`) |
| Shared | Implemented | Login/logout, password reset by email, profile (details, password, avatar), POS shell with persistent printer connection, installable PWA with offline page | Logout is GET; no automated tests |

## Features

| Feature | Role | Status | Spec |
|---|---|---|---|
| Authentication | Shared | Implemented | [spec](features/shared/authentication/spec.md) |
| Password reset | Shared | Implemented | [spec](features/shared/password-reset/spec.md) |
| Profile | Shared | Implemented | [spec](features/shared/profile/spec.md) |
| POS shell, printer and PWA | Shared | Implemented | [spec](features/shared/pos-shell-and-pwa/spec.md) |
| POS ordering | Cashier | Implemented | [spec](features/cashier/pos-ordering/spec.md) |
| Pending orders | Cashier | Partial | [spec](features/cashier/pending-orders/spec.md) |
| Served orders | Cashier | Implemented | [spec](features/cashier/served-orders/spec.md) |
| Branch setup | Admin | Implemented | [spec](features/admin/branch-setup/spec.md) |
| Cashier accounts | Admin | Partial | [spec](features/admin/cashier-accounts/spec.md) |
| Receipt and menu settings | Admin | Implemented | [spec](features/admin/receipt-and-menu-settings/spec.md) |
| Statistics and reports | Admin | Implemented | [spec](features/admin/statistics-and-reports/spec.md) |

## Evidence by code area

- Routes: [`routes/web.php`](../routes/web.php)
- Controllers: [`app/Http/Controllers`](../app/Http/Controllers)
- Models and helpers: [`app/Models`](../app/Models), [`app/Support/Pos.php`](../app/Support/Pos.php)
- Views: [`resources/views`](../resources/views)
- Database shape: [`database/migrations`](../database/migrations)
