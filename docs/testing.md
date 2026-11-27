# Testing

**Reviewed:** 27 November 2026

## Owner's rule

The owner tests the application. Do not create or run automated tests, browser tests, migrations, seeders, or manual walkthroughs unless the owner explicitly asks. After a change, review the code and `git diff` statically and say plainly what was not run.

## What exists

- `tests/Feature/ExampleTest.php` and `tests/Unit/ExampleTest.php` — Laravel defaults only. There is no POS coverage.
- `phpunit.xml` runs against in-memory SQLite. Several POS queries use MySQL-only SQL (`CONVERT_TZ`, `GROUP_CONCAT`), so feature tests of orders, served orders and statistics would need a MySQL test database.
- `.github/workflows/tests.yml` is the stock Laravel workflow (it triggers on `master` and `*.x`, while this repository uses `main`).

## Suggested manual checks after a structural change

1. Log in as an admin and as a cashier.
2. Open `/home` and confirm the POS loads inside the shell, then visit Orders, Served and Profile.
3. As an admin, open Settings, Statistics and Create Cashier; as a cashier, confirm those URLs redirect home.
4. Place, edit, serve and void an order.
5. Request a password reset email.
