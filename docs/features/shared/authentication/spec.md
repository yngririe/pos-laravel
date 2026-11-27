# Authentication

**Status:** Implemented  
**Reviewed:** 27 November 2026

## Current behavior

- `GET /login` shows the login page; `POST /login` (throttled 10/min) signs in with **username + password** through `Auth::attempt`, regenerates the session, and redirects to `home`. A failure returns "Invalid username or password."
- `GET /logout` signs out, invalidates the session and CSRF token, and redirects to login. It is a GET on purpose because the navbar buttons use `window.location.href`.
- `GET /` redirects to `home` when signed in, otherwise to `login`.
- Login and reset pages are guest-only. Everything else is behind `auth`.
- Two roles exist: `admin` and `cashier`. `users.status` is stored but **not checked at login**.

## Gaps and acceptance direction

- Logout over GET (see [security](../../../security.md)).
- A disabled account (`status` other than `active`) can still sign in.
- Verified by static review only. Owner verification is pending.

## Source evidence

`routes/web.php`, `app/Http/Controllers/Auth/AuthController.php`, `resources/views/auth/login.blade.php`, `app/Models/User.php`

## Related documentation

See [domain status](../../../domain-feature-status.md), the relevant [domain page](../../../domains/), and the [feature implementation guide](../../../feature-implementation-guide.md).
