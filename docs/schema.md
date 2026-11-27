# Database Schema

**Reviewed:** 27 November 2026 · Source: `database/migrations/` (6 migrations, 2026-10-14 → 2026-10-15)

The first migration only creates a table when it does not already exist, so it can run against the older imported POS database. Never edit a migration that has run; add a new one.

| Table | Purpose | Key columns |
|---|---|---|
| `users` | Admin and cashier accounts. An admin is also a branch. | `username` (unique), `role` enum(`admin`,`cashier`), `status`, `branch_name`, `branch_id` (nullable), `avatar`, `password` (bcrypt), `created_at` only |
| `menu_items` | Sellable items | `name` (unique), `price`, `discounted_price` (nullable), `category`, `image`, `is_active` (added 2026-10-14) |
| `orders` | One row per order | `branch_id` (indexed), `created_by_user_id` (nullable, indexed), `beeper_number`, `customer_name`, `pickup_time` (time, nullable), `notes` (nullable), `order_type`, `payment_method`, `amount_paid`, `subtotal`, `discount`, `total`, `change_amount`, `gcash_reference`, `gcash_reference_extra`, `gcash_extra_amount`, `refund_amount`, `status`, `created_at` (UTC), `served_at` (UTC, nullable) |
| `order_items` | Price recorded for each line at sale time | `order_id` → `orders.id` cascade delete, `menu_item_id` (nullable, not a foreign key), `name`, `price`, `quantity` |
| `receipt_settings` | One shared row, `id = 1` | store name/logo/address/contact/Facebook/TIN, receipt header/footer, `show_*` flags, `dark_mode`, `updated_at` only |
| `password_resets` | Reset tokens | `email`, `token` (SHA-256 of the emailed token), `expires_at` (UTC) |
| `daily_cash_records` | Petty cash per branch per business date | unique(`branch_id`,`business_date`), `petty_cash`, `expense` (legacy column) |
| `daily_expenses` | Expense entries | `branch_id`, `business_date`, `amount`, `note` (added 2026-10-15) |

Notes:

- `orders.order_type` holds `dine-in`, `take-out`, `delivery` or `pick-up`; `status` holds `pending`, `served` or `voided`. They are plain strings; the controller validates them.
- `receipt_settings` is a single global row. It is **not** per branch.
- `daily_cash_records.expense` was copied into `daily_expenses` by the third migration; new code reads `daily_expenses`.
- Models exist only for `User`, `MenuItem`, `Order`, `OrderItem`, `ReceiptSetting`. Statistics, expenses, petty cash and password resets use raw SQL or `DB::table`.
