# Password Reset

**Status:** Implemented  
**Reviewed:** 27 November 2026

## Current behavior

- `/forgot-password` (guest): the user enters an email. If a user has that email, a 64-hex-character token is generated, only its SHA-256 is stored in `password_resets` (any earlier row for that email is deleted first), and an HTML email (`emails.reset-password`) with the link is sent through the configured mailer. Requests are throttled to 5/min.
- `/reset-password?token=...&email=...`: valid for **1 hour** (UTC). The page shows the outcome from the `status` query value. Submitting (throttled 10/min) applies the shared password rule.
- Password rule (`Pos::passwordError`): at least 8 characters, an uppercase letter, a number, a special character, and a matching confirmation.

## Gaps and acceptance direction

- An unknown email shows "No account found with that email.", which reveals which emails have accounts.
- Needs working SMTP settings in `.env` (see [setup](../../../setup.md)). Mail delivery was not tested.

## Source evidence

`app/Http/Controllers/Auth/PasswordResetController.php`, `resources/views/auth/{forgot,reset}.blade.php`, `resources/views/emails/reset-password.blade.php`, `public/modules/auth/forgot-password/`

## Related documentation

See [domain status](../../../domain-feature-status.md), the relevant [domain page](../../../domains/), and the [feature implementation guide](../../../feature-implementation-guide.md).
