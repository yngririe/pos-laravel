# Future Plan

**Reviewed:** 27 November 2026. Items are gaps found in the code, not commitments.

## Behavior and safety

- Guard serve/void/update by current status (see [security](security.md) gap 1).
- Decide whether the all-branch statistics merge needs a higher role.
- Make receipt settings per branch, or document that they are global on purpose.
- Replace GET logout with POST plus a form on the navbar buttons.
- Add a cashier list with disable/reset-password; today an admin can only create cashiers.

## Quality

- Add MySQL-backed feature tests for orders, branch isolation and the business-day boundary.
- Align `.github/workflows/tests.yml` with this repository (`main`, PHP 8.3, MySQL service).
- Decide whether Laravel stays on 12 (current lock) or moves to 13; the 13 files are in git history before commit `6c29c14`.

## Structure follow-ups

- Page CSS/JS live in `public/modules/<role>/<page>/`. Moving them under `resources/` with Vite, as Vendo does, would change the PWA cache list and every `asset()` path, so it was left alone.
- Models for `daily_expenses` and `daily_cash_records` if the statistics SQL is ever refactored.
