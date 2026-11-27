# Working Rules for Twist & Roll POS

## Scope and source of truth

- These rules apply to this repository. Follow the user's current instructions first. Preserve existing work and inspect `git status` before editing; do not discard unrelated changes.
- This is a Laravel 12 / PHP 8.3 point-of-sale system (`composer.lock` resolves Laravel v12.70.0). It uses Blade views, the Laravel session guard, static per-page CSS/JS in `public/modules/{admin,auth,cashier,shared}/`, and MySQL/MariaDB. Do not introduce React, Vue, a separate API, Sanctum, or another framework unless the user asks for an architectural change.
- Read `docs/README.md`, `docs/domain-feature-status.md`, and the relevant feature spec before changing behavior. Read only the docs needed for the task.
- Treat live code as evidence of what works. Treat `docs/` as the record of intended behavior and status. If they disagree, inspect the code and update the docs in the same change. Do not assume a feature exists because other POS systems have it.
- There are exactly two roles, `admin` and `cashier`. Do not invent roles, inventory, suppliers, customer accounts, or other ERP features unless asked.

## Find the right context

| Task | Read |
|---|---|
| What exists and what is planned | `docs/domain-feature-status.md`, `docs/domains/*.md` |
| Structure, routes, time, data shape | `docs/architecture.md`, `docs/schema.md`, `routes/web.php` |
| Order types, payments, discounts, statuses, business day | `docs/order-flow-decisions.md` |
| Role feature behavior | `docs/features/<role>/<feature>/spec.md` (include each affected spec) |
| Security expectations and known gaps | `docs/security.md` |
| Prior work | latest `docs/logs/PROGRESS-YYYY-MM-DD.md`, `docs/future-plan.md` |

| Feature | Code |
|---|---|
| Login, logout, password reset | `app/Http/Controllers/Auth/`, `resources/views/auth/`, `User.php` |
| Branch creation | `Admin/BranchController.php`, `views/admin/branches/`, `config/pos.php` |
| Cashier accounts | `Admin/CashierController.php`, `EnsureAdmin.php`, `views/admin/cashiers/` |
| POS ordering | `Cashier/HomeController.php`, `Cashier/OrderController.php`, `Support/Pos.php`, `views/cashier/homepage` |
| Pending / served orders | `Cashier/OrderController.php`, `Cashier/ServedController.php`, `views/cashier/` |
| Statistics and reports | `Admin/StatisticsController.php` (page), `Admin/DailyFinanceController.php` (petty cash, expenses), `Admin/StatisticsReportController.php` (Excel feed), `Support/Pos.php`, `views/admin/statistics/` |
| Receipt and menu settings | `Admin/SettingsController.php`, `Models/Settings/ReceiptSetting.php`, `Models/Catalog/MenuItem.php`, `views/admin/settings/` |
| Profile | `app/Http/Controllers/ProfileController.php`, `views/shared/profile` |
| Browser code | `public/modules/<role>/<page>/`, `public/assets/js/`, `views/shared/pos-shell` |

If existing behavior is unclear, trace `route → middleware → controller → model/query → Blade/JavaScript` before changing it. For a small UI change, do not inspect or rewrite the whole project.

## Working loop

1. Identify the actor, route, controller, model/query, view, middleware, database fields and spec for the request.
2. Make the smallest complete change. Keep validation and authorization on the server; a hidden button is not access control. Keep existing route names, status vocabulary, Blade and CSS conventions. Do not refactor or reformat unrelated code.
3. Document **every code change in the same work session**. Append a concise entry to `docs/logs/PROGRESS-YYYY-MM-DD.md` (Asia/Manila date): what changed, why, affected behavior and files, known gaps. Update the relevant `docs/features/**/spec.md` for behavior changes, `docs/domain-feature-status.md` when scope or status changes, and `architecture.md`, `schema.md`, `order-flow-decisions.md` or `docs/README.md` when they would go stale. Never mark a placeholder as implemented.
4. The user performs testing. **Do not create or run automated tests, browser tests, migrations, seeders, database resets, or manual walkthroughs unless the user explicitly asks.** Static review and `git diff` are fine. Say what remains unverified; never claim a test passed or a migration succeeded when it was not run.
5. In the handoff, summarize code and docs changed, the practical effect, and any material limitation.

Stay on the current branch and leave changes uncommitted unless asked. Do not commit, push, force-push, `git reset --hard`, rewrite history, or delete untracked user files without an explicit request. Do not modify anything outside this repository unless the task requires it.

## Code organization

- Controllers live in `app/Http/Controllers/Admin/`, `Auth/`, `Cashier/`. A controller shared by both roles stays in the `Controllers/` root. Models are grouped by domain (`Sales/`, `Catalog/`, `Settings/`); `User` stays in `Models/`. Views mirror this: `resources/views/{admin,auth,cashier,emails,shared}/`.
- Keep controllers focused on HTTP coordination. For small features do not add service classes. Prefer one focused helper over duplicating a calculation. The project deliberately uses raw SQL through `App\Support\Pos` in places; do not mechanically convert it to Eloquent in an unrelated task.
- Check Laravel and existing code before adding a dependency; explain a necessary new one in the handoff.
- For schema changes add a **new** migration, preserve rows, give safe defaults and backfills, and keep branch ownership. Never run `migrate:fresh`, `db:wipe`, `DROP DATABASE`, or equivalents unless the user explicitly asks and understands data will be lost. The first migration coexists with an older imported schema; preserve that unless asked to retire it.

## Branch and role isolation

- Branch scope comes only from `App\Support\Pos::branchId()`. An admin is a branch (`branch_id` equals their own id); a cashier inherits the admin's. Request-supplied IDs may identify a record but never grant access. For order changes verify both the id and the branch: `Order::where('id', $id)->where('branch_id', Pos::branchId())`.
- Orders, pending and served lists, statistics and reports use the authenticated branch. Any cross-branch view must be admin-only and explicit in the code.
- Admin-only routes sit behind `EnsureAdmin`. Protect the route and controller as well as the UI; do not rely on `@if ($role === 'admin')`.

## POS rules

Order types, payment methods, discount types, beeper rules, totals, statuses and the business day are defined in `docs/order-flow-decisions.md`. Read it before touching order, receipt or report code. Key points: the business day starts at **3:00 PM Manila time**; times are stored in UTC and shown in Asia/Manila through the `Pos` helpers; revenue counts `served` orders only; `order_items` keep the price at sale time; do not delete completed or voided history to hide it; keep the status vocabulary (`pending`, `served`, `voided`) unless a requested feature requires a change. Check both the saved setting and every place that prints a receipt when changing receipt behavior, and do not hard-code store details that are configurable.

## Security and safe changes

- Never trust browser input for authorization, IDs, quantities, payment values, order types, beeper numbers or uploads. Prefer authoritative database prices over browser-supplied ones.
- Use transactions when several related order writes must succeed together.
- Passwords: never store plain text; use the shared rule in `Pos::passwordError`; keep login and reset throttling; do not log passwords or reset tokens or return hashes to the browser.
- Uploads: validate type and size, generate safe filenames, block executable files, and do not delete the old file until the replacement succeeded.
- Never commit `.env`, credentials, mail passwords, application keys or production secrets. Do not disable CSRF or authentication to make a request work.
- Keep the existing Twist & Roll look. Modify only the requested area, keep controls usable at POS screen sizes, and keep the existing modal and interaction patterns. Do not redesign a page to fix one component or replace working Blade/JS with a framework.
