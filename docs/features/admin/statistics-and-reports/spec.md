# Statistics and Reports

**Status:** Implemented  
**Reviewed:** 27 November 2026

## Current behavior

All routes are admin-only. Figures count **served** orders; voided and pending orders are excluded. Dates use the 3:00 PM business day (see [order flow decisions](../../../order-flow-decisions.md)).

- `GET /statistics` shows the live shift panel (open 3:00 PM to 1:59 AM) with order counts and cash/GCash sales, selectable year and month views, a sidebar of sections, charts, and a Sales Archive of completed business dates (the active shift joins the archive after closing).
- `POST /statistics/daily-cash` adds petty cash to the active business date (amounts add to any existing value). Only allowed during business hours (3:00 PM to 5:00 AM).
- `POST /statistics/expenses` and `PUT /statistics/expenses/{expense}` add or edit an expense (amount and a required note up to 255 characters), scoped to the admin's branch, within the same hours.
- `GET /statistics/report` returns a JSON report for a year and month (optional `filter_month`). With `merge=1` it covers **every branch**.

## Gaps and acceptance direction

- Any admin can request the merged all-branch report; decide whether that needs a higher role.
- Report SQL is MySQL-specific.
- Verified by static review only.

## Source evidence

`app/Http/Controllers/Admin/StatisticsController.php` (page), `Admin/DailyFinanceController.php` (petty cash, expenses), `Admin/StatisticsReportController.php` (Excel JSON feed), `app/Support/Pos.php`, `resources/views/admin/statistics/index.blade.php`, `public/modules/admin/statistics/`

## Related documentation

See [domain status](../../../domain-feature-status.md), the relevant [domain page](../../../domains/), and the [feature implementation guide](../../../feature-implementation-guide.md).
