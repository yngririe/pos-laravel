# Security Overview

**Reviewed:** 27 November 2026 · Code-based review, not a penetration test.

## Controls in the code

- **Authentication:** Laravel session guard, `Auth::attempt` by username, session regenerated on login, invalidated on logout. Login is throttled to 10/min, password reset request to 5/min, reset submit to 10/min, branch creation to 5/min.
- **Authorization:** `EnsureAdmin` guards settings, statistics, and cashier creation server-side and returns 403 JSON for JSON clients. Cashiers are redirected home.
- **Branch isolation:** order lookups and mutations filter by `branch_id` from `Pos::branchId()`, never from request input.
- **Passwords:** bcrypt via the model's `hashed` cast. One shared rule (`Pos::passwordError`): 8+ characters with an uppercase letter, a number, and a special character.
- **Reset tokens:** only the SHA-256 of the token is stored; tokens expire after 1 hour.
- **Branch creation:** `/branches/create` needs `BRANCH_SETUP_KEY` (compared with `hash_equals`). An empty key disables the page.
- **Uploads:** avatar, logo and menu images are validated as `jpg/jpeg/png/webp` and size-limited (2 MB avatar and logo, 3 MB menu image), then saved under `public/assets/images/`.
- **Pricing:** line prices come from the database, not the browser.

## Known gaps (not fixed by the restructure)

1. **Serve and void do not check the current status.** A served order can be voided and a voided order served. Add a status guard before relying on reports.
2. **Merged statistics report.** `GET /statistics/report?merge=1` lists every branch, and any branch admin can call it. Decide whether that is intended cross-branch reporting or needs a super-admin.
3. **`receipt_settings` is a single global row**, so every branch edits the same receipt.
4. **Logout is a GET route** (kept for the existing navbar buttons), so a third party can log a user out with a link.
5. **Local `.env`** has `APP_DEBUG=true`, a public tunnel `APP_URL`, and a production database name. Do not deploy with debug on. `.env` is git-ignored and must never be committed.
6. **Uploads go to `public/`** and keep a random generated name; there is no content scan.
7. **Password reset reveals registered emails** ("No account found with that email.").
8. **Disabled accounts can still sign in**; `users.status` is not checked at login.
9. Automated tests do not cover authorization (see [testing](testing.md)).

## Source evidence

`routes/web.php`, `app/Http/Middleware/EnsureAdmin.php`, `app/Support/Pos.php`, `app/Http/Controllers/**`, `config/pos.php`.
