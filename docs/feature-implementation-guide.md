# Feature Implementation Guide

Use this order for a new feature or a behavior change. Keep each step as small as the request allows.

1. **Actor and scope.** Which role uses it (`admin`, `cashier`, or both)? Which branch data does it touch? Branch scope always comes from `Pos::branchId()`.
2. **Route.** Add it to `routes/web.php` inside the right group (`guest`, `auth`, or `auth` + `EnsureAdmin`). Keep existing route names; Blade pages call them with `route()`.
3. **Controller.** Put it under `app/Http/Controllers/Admin/`, `Cashier/`, or `Auth/`. A controller used by both roles belongs in the `Controllers/` root. Validate on the server and check authorization with the operation.
4. **Data.** Add a **new** migration for schema changes (never edit one that has run). Follow the existing string-column convention for statuses and types.
5. **View and assets.** Blade goes in `resources/views/<role>/`. Page CSS and JavaScript go in `public/modules/<role>/<page>/` (`admin`, `auth`, `cashier` or `shared`). Keep the existing Twist & Roll look.
6. **Docs, same session.** Append to `docs/logs/PROGRESS-YYYY-MM-DD.md`; update the feature's `spec.md`; update [domain-feature-status](domain-feature-status.md), [schema](schema.md), [architecture](architecture.md), or [order flow decisions](order-flow-decisions.md) when they would otherwise be stale.
7. **Verify.** Static review and `git diff`. The owner runs the tests; say what was not run.

New spec template: `docs/features/<role>/<feature>/spec.md` with **Status**, **Reviewed**, **Current behavior**, **Gaps and acceptance direction**, **Source evidence**, **Related documentation**.
