# Cashier Accounts

**Status:** Partial (create only)  
**Reviewed:** 27 November 2026

## Current behavior

- `GET /cashiers/create` and `POST /cashiers` are admin-only (`EnsureAdmin`).
- The form takes full name, username, a valid email, and a password that meets the shared rule. The new user gets `role = cashier`, `status = active`, and the admin's branch id. Duplicate usernames are rejected.

## Gaps and acceptance direction

- No page lists, edits, disables or resets cashier accounts.
- Verified by static review only.

## Source evidence

`app/Http/Controllers/Admin/CashierController.php`, `resources/views/admin/cashiers/create.blade.php`, `app/Http/Middleware/EnsureAdmin.php`

## Related documentation

See [domain status](../../../domain-feature-status.md), the relevant [domain page](../../../domains/), and the [feature implementation guide](../../../feature-implementation-guide.md).
