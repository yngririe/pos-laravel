# Working Rules for Twist & Roll POS

## Scope and source of truth

- These rules apply to this repository. Follow the user's current instructions first.
- Preserve existing work. Before editing, inspect the affected files and `git status`. Do not discard, overwrite, reformat, or refactor unrelated changes.
- This is a Laravel 13 / PHP 8.3 point-of-sale system for Twist & Roll. The current application uses Blade views, Laravel authentication/sessions, Vite, Tailwind CSS, and a relational database through Laravel.
- The main application code lives in:
  - `app/Http/Controllers/`
  - `app/Http/Middleware/`
  - `app/Models/`
  - `app/Support/`
  - `routes/`
  - `resources/views/`
  - `database/migrations/`
- Treat the current working code as the primary evidence of what the system actually supports. Do not assume a feature exists because it is common in other POS systems.
- Do not introduce React, Vue, a separate API architecture, Sanctum, or another framework unless the user explicitly requests an architectural change.
- Keep the existing POS structure and conventions unless there is a clear reason to change them.

## Current system context

The POS currently supports two account roles:

- `admin`
- `cashier`

The system is branch-aware. Orders, reports, and operational data must remain isolated to the authenticated user's branch unless an explicitly authorized admin operation is designed to work across branches.

Current major areas include:

- Authentication
- Branch creation
- Admin and cashier accounts
- POS/home page
- Menu items
- Order creation
- Pending orders
- Order editing
- Serving orders
- Voiding orders
- Served-order history
- Statistics and reports
- Receipt settings
- Profile management
- Password reset
- Dark mode and store/receipt configuration

Do not invent additional roles, workflows, inventory systems, supplier systems, customer accounts, or other ERP features unless requested.

## Find the right context

Before changing a feature, inspect only the files needed for that feature.

| Task | Inspect |
|---|---|
| Login/logout/authentication | `routes/web.php`, `AuthController.php`, `User.php` |
| Branch creation | `BranchController.php`, branch view, `User.php`, migration |
| Cashier accounts | `CashierController.php`, `EnsureAdmin.php`, cashier view |
| POS ordering | `HomeController.php`, `OrderController.php`, `Pos.php`, homepage/order Blade views |
| Pending orders | `OrderController.php`, `pages/orders.blade.php` |
| Served orders | `ServedController.php`, `pages/served.blade.php` |
| Reports/statistics | `StatisticsController.php`, `pages/statistics.blade.php`, `Pos.php` |
| Profile/settings | `ProfileController.php`, `pages/profile.blade.php`, `ReceiptSetting.php` |
| Password reset | `PasswordResetController.php`, auth views, `password_resets` table |
| Menu/product data | `MenuItem.php`, migration, POS views/scripts using menu data |
| Database changes | Existing migrations and affected models/controllers |
| Branch/time behavior | `app/Support/Pos.php` |

For a small UI change, do not inspect or rewrite the entire project.

If existing behavior is unclear, trace it from:

`route → middleware → controller → model/query → Blade/JavaScript`

before changing it.

## Working loop

1. Identify the exact requested feature and the affected route, controller, model/query, view, middleware, and database fields.

2. Inspect the existing implementation before writing new code.

3. Make the smallest complete change that satisfies the request.

4. Preserve existing naming, route names, Blade conventions, CSS structure, JavaScript behavior, and database vocabulary whenever possible.

5. Do not refactor unrelated code just because another implementation looks cleaner.

6. Keep authorization and validation on the server. A hidden button or missing navigation item is not access control.

7. After editing, perform a static review of the changed files and inspect `git diff`.

8. Do not create or run automated tests, browser tests, database resets, seeders, migrations, or manual application walkthroughs unless the user explicitly asks.

9. In the final handoff, explain:
   - what changed,
   - which files changed,
   - the practical effect,
   - and anything that remains unverified.

Stay on the current branch and leave changes uncommitted unless the user specifically asks for a commit, branch, push, or merge.

## Branch and account isolation

Branch isolation is a core POS rule.

The current branch context is derived through `App\Support\Pos`.

- Admin accounts own or represent a branch.
- Cashiers inherit the branch associated with their admin/branch account.
- Orders must belong to the current branch.
- Pending orders must be queried by the current branch.
- Served orders must be queried by the current branch.
- Statistics and normal reports must use the authorized branch scope.
- Cashiers must not gain access to another branch by submitting a different `branch_id`.
- Request-supplied IDs may identify a record, but they must never grant access to that record.

For order mutations, always verify both the resource ID and branch ownership.

Example principle:

```php
Order::where('id', $orderId)
    ->where('branch_id', Pos::branchId())
```

Do not remove branch constraints to make a feature easier to implement.

Any intentional cross-branch reporting must remain admin-only and must be explicit in the code.

## Roles and authorization

The two current roles are `admin` and `cashier`.

Admin-only routes currently use `EnsureAdmin`.

Keep admin-only functionality protected server-side, including features such as:

- statistics/report administration where currently restricted,
- cashier creation,
- branch-level administrative settings,
- any future cross-branch action.

Do not rely only on Blade conditions such as:

```php
@if ($role === 'admin')
```

for security.

If a cashier should not perform an operation, protect the route/controller as well as the UI.

Do not create new roles or permission systems unless requested.

## POS order rules

Current orders support:

- Beeper numbers
- Dine-in
- Take-out
- Cash
- GCash
- Discounts
- Amount paid
- Change
- Pending status
- Served status
- Voided status
- GCash reference information
- Additional GCash/refund information during order edits

Preserve the current status vocabulary unless a requested feature requires changing it.

Do not silently rename statuses because another POS convention uses different terminology.

### Beeper numbers

The current POS accepts beeper numbers from `1` through `16`.

A beeper already attached to a pending order in the same branch must not be reused.

If changing this rule, inspect all affected order creation/editing UI and backend validation first.

### Order types

Current valid values are:

```text
dine-in
take-out
```

Do not introduce another value without updating validation, storage, displays, reports, and receipt behavior where applicable.

### Payment methods

Current valid payment methods are:

```text
cash
gcash
```

GCash references currently follow the application's existing reference validation.

Do not weaken payment validation simply to make a request succeed.

### Order totals

Treat financial values as sensitive business data.

When modifying checkout/order logic:

- Validate menu item IDs and quantities.
- Prefer authoritative menu/database prices over arbitrary browser-supplied prices when changing the pricing implementation.
- Keep subtotal, discounts, total, amount paid, change, refunds, and GCash adjustments internally consistent.
- Do not allow a client-supplied branch ID to determine ownership.
- Use database transactions when several related order/order-item writes must succeed together.

Do not casually change historical `order_items` prices. They represent the price recorded for that transaction.

## Order status and history

Current important statuses are:

```text
pending
served
voided
```

A newly placed order is pending.

Serving an order changes it to `served` and records `served_at`.

Voiding an order changes it to `voided`.

When adding status-related behavior:

- scope the order to the authenticated branch,
- validate whether the transition makes sense,
- avoid creating duplicate state changes,
- preserve historical order data,
- keep reports consistent with the same status definitions.

Do not delete completed or voided transaction history merely to remove it from the interface.

## Time and business-day rules

Time handling is centralized in `App\Support\Pos`.

Important rules:

- Database transaction timestamps are treated as UTC.
- User-facing times are converted to `Asia/Manila`.
- The POS business day begins at **5:00 AM Manila time**, not midnight.

This affects:

- daily sales,
- served orders,
- statistics,
- reports,
- month/year grouping where business-date logic is used.

Use the existing `Pos` time helpers when applicable.

Do not replace business-day calculations with a simple `DATE(created_at)` without checking the existing 5:00 AM boundary.

Do not mix UTC and Manila timestamps silently.

## Reports and statistics

Statistics must remain consistent with actual POS transaction rules.

When changing reports:

- preserve branch isolation,
- preserve the 5:00 AM business-day rule,
- distinguish served, pending, and voided transactions correctly,
- do not count voided sales as completed revenue,
- keep discounts and refunds represented consistently,
- do not silently merge branches unless the operation is intentionally authorized to do so.

Cross-branch or merged reports must remain explicitly admin-controlled.

Do not modify sales figures only at the UI layer. Fix the underlying query/calculation when the data itself is wrong.

## Database and migrations

Inspect the existing schema before changing it.

Current primary tables include:

- `menu_items`
- `users`
- `orders`
- `order_items`
- `password_resets`
- `receipt_settings`

When adding schema changes:

- create a new migration rather than rewriting an already-used migration unless the user specifically says the database is disposable,
- preserve existing rows,
- provide safe defaults/backfills when adding required fields,
- maintain branch ownership where relevant,
- use foreign keys where appropriate,
- avoid destructive database operations unless explicitly requested.

Never run:

```text
migrate:fresh
db:wipe
DROP DATABASE
```

or equivalent destructive operations unless the user explicitly asks and understands that data will be removed.

The existing POS migration was designed to coexist with an older imported POS schema. Preserve compatibility unless the user asks to retire that legacy behavior.

## Controllers and models

Keep controllers focused on coordinating HTTP behavior.

For small features, do not create unnecessary service classes or abstractions.

For larger repeated business logic, prefer a focused reusable class/helper instead of duplicating calculations across controllers.

Use the existing Eloquent models where appropriate:

- `User`
- `MenuItem`
- `Order`
- `OrderItem`
- `ReceiptSetting`

The project also intentionally uses raw/database queries in some POS/reporting code through `App\Support\Pos`.

Do not mechanically rewrite all raw queries into Eloquent as part of an unrelated task.

## Blade, JavaScript, and UI

Preserve the existing Twist & Roll POS visual identity and layout unless the user specifically asks for a redesign.

For UI changes:

- modify only the requested area,
- keep existing navigation and page structure,
- keep controls usable on the intended POS screen size,
- preserve responsive behavior,
- avoid overlapping content,
- provide clear empty/error/loading states when needed,
- preserve existing modal and interaction patterns where practical.

Do not redesign an entire page when the user asks to fix one component.

Do not replace working Blade/JavaScript interactions with a new frontend framework unless explicitly requested.

Keep Blade files readable. Extract a component only when it is genuinely reusable or repeated.

## Receipt settings

Receipt/store settings are stored through `ReceiptSetting` / `receipt_settings`.

Current configurable information includes:

- store name
- logo
- store address
- contact information
- Facebook page URL
- TIN
- receipt header
- receipt footer
- whether to show discounts
- whether to show cashier
- whether to show order type
- whether to show payment type
- whether to show beeper number
- dark mode

When changing receipt behavior, check both the saved setting and every place that renders or prints the receipt.

Do not hard-code store information in a receipt if the corresponding value is configurable.

## Authentication and passwords

Use Laravel authentication/session behavior already present in the project.

Do not store plain-text passwords.

The POS has a shared password rule in `App\Support\Pos`.

When adding or changing password flows, preserve the shared rules unless the user requests a policy change.

Password-reset tokens and credentials are sensitive:

- do not log passwords,
- do not expose reset tokens unnecessarily,
- do not commit `.env` credentials,
- do not expose SMTP credentials or application secrets,
- do not return password hashes to the browser.

Keep login and password-reset throttling in place unless there is a specific reason to change it.

## Uploads and images

The POS currently supports image-related data such as:

- user avatars,
- store logo,
- menu item images.

For uploads:

- validate file type,
- validate file size,
- use safe generated filenames,
- do not trust the original filename,
- prevent executable/script uploads,
- keep paths consistent with the current application.

Do not delete an existing file until the replacement has been successfully handled when loss of the old file would be harmful.

## Security and safe changes

Never trust browser input for authorization.

Validate:

- IDs
- quantities
- payment values
- order types
- payment methods
- beeper numbers
- uploaded files
- role-sensitive operations

Do not expose another branch's transactions through:

- route parameters,
- JSON endpoints,
- reports,
- statistics,
- order editing,
- served-order history.

Do not commit:

- `.env`
- database credentials
- mail credentials
- application keys
- production secrets

Do not disable CSRF protection or authentication to fix a request.

Do not weaken authorization because a UI control is hidden.

## Existing conventions over new dependencies

Before adding a package:

1. Check whether Laravel or the existing project already provides the required functionality.
2. Check whether the same behavior already exists elsewhere in the POS.
3. Add a dependency only when it materially improves the requested feature.
4. Explain any necessary new dependency in the final handoff.

Do not add a large library for a small feature that can reasonably use the existing stack.

## Git and repository safety

Before substantial edits, inspect:

```bash
git status
```

Do not:

- discard unrelated modifications,
- use `git reset --hard`,
- force checkout over user changes,
- delete untracked user files,
- rewrite history,
- force push,

unless explicitly requested.

Stay on the user's current branch unless they ask to switch or create a branch.

Do not commit or push automatically.

If asked to commit, keep the commit scoped to the requested work and do not include unrelated files.

## Testing and verification

The user performs application testing unless they explicitly ask the agent to run tests.

By default:

- inspect the changed code,
- inspect `git diff`,
- check for obvious syntax/logic inconsistencies,
- state what has not been executed.

Do not claim:

- "tested successfully,"
- "working,"
- "fixed in the browser,"
- "database migration succeeded,"

unless that action was actually performed.

Do not run destructive database commands as a form of testing.

If the user explicitly asks for testing, use the smallest relevant test/check first.

## Final handoff

After completing a coding task, keep the response concise and include:

1. What was changed.
2. The important files changed.
3. What the change now does.
4. Anything the user still needs to test or verify.

Do not claim unrelated improvements.

Do not describe planned functionality as already implemented.

Do not overwrite or commit unrelated user work.

## Core POS principles

When uncertain, prioritize these rules:

1. **Protect branch isolation.**
2. **Protect transaction accuracy.**
3. **Protect authentication and role restrictions.**
4. **Preserve historical order data.**
5. **Respect the 5:00 AM Asia/Manila business-day boundary.**
6. **Keep served, pending, and voided transactions distinct.**
7. **Use the existing Twist & Roll POS structure before introducing new architecture.**
8. **Make the smallest complete change requested.**
9. **Do not redesign unrelated UI.**
10. **Never claim something was tested when it was not.**