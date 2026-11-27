# Branch Setup

**Status:** Implemented  
**Reviewed:** 27 November 2026

## Current behavior

- `GET /branches/create` and `POST /branches` (throttled 5/min) are **not** behind login. They are protected by the `BRANCH_SETUP_KEY` value in `.env` (`config('pos.branch_setup_key')`). With an empty key the page is shown as disabled and submissions fail.
- A valid request needs branch name, full name, username, a valid email, and a password that meets the shared rule. It creates an `admin` user, then sets that user's `branch_id` to its own id, so the new admin owns the branch.
- Duplicate usernames are rejected.

## Gaps and acceptance direction

- The setup key is the only protection; keep it secret and rotate it after use.

## Source evidence

`app/Http/Controllers/Admin/BranchController.php`, `resources/views/admin/branches/create.blade.php`, `config/pos.php`

## Related documentation

See [domain status](../../../domain-feature-status.md), the relevant [domain page](../../../domains/), and the [feature implementation guide](../../../feature-implementation-guide.md).
