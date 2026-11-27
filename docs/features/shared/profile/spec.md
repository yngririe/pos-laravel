# Profile

**Status:** Implemented  
**Reviewed:** 27 November 2026

## Current behavior

`GET /profile` shows the signed-in user's page; `POST /profile` dispatches on the `action` field:

- `edit_profile` updates name, email (validated if not empty) and phone.
- `change_password` requires the current password, then the shared password rule; the form reports "New passwords do not match."
- `upload_avatar` accepts `jpg/jpeg/png/webp` up to 2 MB, saves it to `public/assets/images/avatars/` under a generated name, and stores `/assets/images/avatars/<file>` on the user.

Both roles use this page; admins additionally see the "Create Cashier" menu item.

## Gaps and acceptance direction

- The controller sits at `app/Http/Controllers/ProfileController.php` (root) because both roles share it.
- Old avatar files are not deleted when replaced.

## Source evidence

`app/Http/Controllers/ProfileController.php`, `resources/views/shared/profile.blade.php`, `public/modules/shared/profile/`

## Related documentation

See [domain status](../../../domain-feature-status.md), the relevant [domain page](../../../domains/), and the [feature implementation guide](../../../feature-implementation-guide.md).
