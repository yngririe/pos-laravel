# Cashier Domain

A **cashier** takes orders for the branch of the admin who created the account. Admins use the same pages. The cashier role cannot open Settings, Statistics, or Create Cashier.

| Capability | Route | Controller | Spec |
|---|---|---|---|
| POS terminal | `/pos/home`, `/receipt-settings` | `Cashier\HomeController` | [spec](../features/cashier/pos-ordering/spec.md) |
| Pending orders | `/orders`, `/orders/*`, `/order-notifications` | `Cashier\OrderController` | [spec](../features/cashier/pending-orders/spec.md) |
| Served orders | `/served` | `Cashier\ServedController` | [spec](../features/cashier/served-orders/spec.md) |
| Profile | `/profile` | `ProfileController` (shared) | [spec](../features/shared/profile/spec.md) |

Views: `resources/views/cashier/`, `resources/views/shared/`. Order rules: [order flow decisions](../order-flow-decisions.md).
