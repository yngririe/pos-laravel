# Order Flow Decisions

**Reviewed:** 27 November 2026 · Source: `app/Http/Controllers/Cashier/OrderController.php`, `app/Support/Pos.php`

These rules describe what the code does today. Change them only with an explicit owner request, and update this page in the same change.

## Order types

`dine-in`, `take-out`, `delivery`, `pick-up`.

| Type | Beeper | Customer name | Extra |
|---|---|---|---|
| dine-in, take-out | Required, 1–16 | Not stored | — |
| pick-up | Optional, 1–16 | Not stored | — |
| delivery | Never stored | Required, up to 100 characters | Flat ₱30 delivery fee added to the total |

A beeper already on a **pending** order in the same branch cannot be reused (`beeper_in_use`).

## Payment

- `cash` or `gcash`. Anything else is rejected.
- Cash: `amount_paid` must be at least the total; `change_amount = amount_paid − total`.
- GCash: `amount_paid` is set to the total and change is 0. The current code writes `gcash_reference` (and `gcash_reference_extra` on edits) as `null` for both new and edited orders, so no GCash reference number is captured; the statistics queries still read those columns.

## Pricing and discounts

- The server prices each line from `menu_items` using the submitted item id and quantity. Browser-supplied prices are ignored. Quantity must be at least 1.
- Discount type is one of `none`, `20`, `10`, `minus10`. It applies to the **lowest-priced line item price**, not the whole subtotal: 20% of it, 10% of it, or ₱10 (never more than that price).
- `total = max(0, subtotal − discount) + deliveryFee`.
- `order_items` keep the name and price at the time of sale; do not rewrite them.

## Status

| Status | How it is set |
|---|---|
| `pending` | A new order |
| `served` | `POST /orders/serve`; also sets `served_at` (UTC) |
| `voided` | `POST /orders/void` |

Every serve/void/update looks the order up by id **and** `branch_id`.

Editing a pending order (`POST /orders/update`) re-prices it, then records the difference against the old total: an increase is stored in `gcash_extra_amount`, a decrease in `refund_amount`.

## Business day

- The business day starts at **3:00 PM Manila time**. A sale at 1:00 AM belongs to the previous calendar date. `Pos::bizDate()` implements this as `DATE(CONVERT_TZ(col,'+00:00','+08:00') − INTERVAL 15 HOUR)`.
- The live statistics panel is open from 3:00 PM to 1:59 AM; petty cash and expenses can be entered from 3:00 PM until 5:00 AM. After closing, the shift appears in the Sales Archive.
- The served list resets every **Monday at 3:00 PM** Manila time.
- Earlier versions of `AGENTS.md` said the day starts at 5:00 AM. That was wrong; the code and this page are authoritative.

## Reports

Revenue counts `served` orders only; `pending` and `voided` are excluded. Cash and GCash totals are split by `payment_method`.
