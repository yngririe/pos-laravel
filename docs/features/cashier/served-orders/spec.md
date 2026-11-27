# Served Orders

**Status:** Implemented  
**Reviewed:** 27 November 2026

## Current behavior

- `GET /served` lists the branch's served orders with item names, prices and quantities, newest first, plus the business dates present.
- The list **resets every Monday at 3:00 PM Manila time**: only orders served since the most recent reset are shown, and the reset time is displayed. Older orders stay in the database and in statistics.
- Times are converted from UTC to Manila; each row also carries its business date (3:00 PM boundary).

## Gaps and acceptance direction

- No search or export on this page.
- Verified by static review only.

## Source evidence

`app/Http/Controllers/Cashier/ServedController.php`, `resources/views/cashier/served.blade.php`, `public/modules/cashier/served/`, `app/Support/Pos.php`

## Related documentation

See [domain status](../../../domain-feature-status.md), the relevant [domain page](../../../domains/), and the [feature implementation guide](../../../feature-implementation-guide.md).
