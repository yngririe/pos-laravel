# POS Ordering

**Status:** Implemented  
**Reviewed:** 27 November 2026

## Current behavior

- `GET /pos/home` shows the menu (active items only, highest price first) and the price-to-discount map. Page data comes from `Pos::shared('home')` and `Pos::menu()`.
- `GET /receipt-settings` returns the receipt settings and the signed-in user's name as JSON for printing.
- `POST /orders` places an order (JSON in and out). The rules (order types, beeper range, customer name for delivery, the 30 peso delivery fee, cash/GCash, discount types, server-side pricing) are in [order flow decisions](../../../order-flow-decisions.md). The order and its items are saved in one transaction with `branch_id` from `Pos::branchId()` and `created_by_user_id` from the session.

## Gaps and acceptance direction

- The server prices lines by item id but does **not** check `is_active`, so a hidden item can still be ordered if its id is submitted.
- No GCash reference number is stored.
- Verified by static review only.

## Source evidence

`app/Http/Controllers/Cashier/HomeController.php`, `app/Http/Controllers/Cashier/OrderController.php` (`store`), `app/Support/Pos.php`, `resources/views/cashier/homepage.blade.php`, `public/modules/cashier/homepage/`, `public/modules/shared/base/homepage.css`

## Related documentation

See [domain status](../../../domain-feature-status.md), the relevant [domain page](../../../domains/), and the [feature implementation guide](../../../feature-implementation-guide.md).
