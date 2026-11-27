# Pending Orders

**Status:** Partial (works; status guard missing)  
**Reviewed:** 27 November 2026

## Current behavior

- `GET /orders` lists the branch's pending orders with their items.
- `POST /orders/update` edits a pending order in a transaction: same validation as a new order, beeper reuse check against other pending orders, re-pricing, and the difference against the old total stored as `gcash_extra_amount` (more to pay) or `refund_amount` (less). The response includes the difference.
- `POST /orders/serve` sets `status = served` and `served_at` (UTC). `POST /orders/void` sets `status = voided`.
- `GET /order-notifications` returns, **for cashiers only**, up to 20 pending orders created by an admin in the last 24 hours, so the cashier is alerted to admin-entered orders. Admins get an empty list.
- Every action filters by order id **and** branch.

## Gaps and acceptance direction

- Serve and void do not require the order to be `pending`; a served order can be voided and the reverse. Add a status condition to those queries.
- Verified by static review only.

## Source evidence

`app/Http/Controllers/Cashier/OrderController.php`, `resources/views/cashier/orders.blade.php`, `public/modules/cashier/orders/`, `public/assets/js/order-notifications.js`

## Related documentation

See [domain status](../../../domain-feature-status.md), the relevant [domain page](../../../domains/), and the [feature implementation guide](../../../feature-implementation-guide.md).
