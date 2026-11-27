# Twist & Roll POS Documentation

This documentation describes the Laravel application as it exists in the repository, checked on **27 November 2026**. It separates working code from known gaps so nobody treats an assumption as a finished feature. When the code and these pages disagree, the code is the evidence: inspect it and fix the page in the same change.

## Start here

- [Working rules for agents and contributors](../AGENTS.md) — workflow, documentation duty, branch isolation, and the owner's testing preference.
- [Setup guide](setup.md) — install, `.env`, database choices, and the old-file → route map.
- [Current implementation status](domain-feature-status.md) — the source of truth for what is implemented.
- [Feature implementation guide](feature-implementation-guide.md) — how a change moves from route to controller to view to docs.
- [Architecture](architecture.md) and [schema](schema.md) — technical structure, time rules, and tables.
- [Order flow decisions](order-flow-decisions.md) — order types, payments, discounts, statuses, and the business day.
- [Security overview](security.md) — code-based controls and known gaps; not a penetration-test report.
- [Testing](testing.md) — what exists, what does not, and the owner's manual-testing rule.
- [Future plan](future-plan.md) — known gaps and follow-ups.

## Domains

- [Admin](domains/Admin.md) — branch owner: settings, menu, statistics, cashier accounts.
- [Cashier](domains/Cashier.md) — POS terminal, pending orders, served orders (admins use these pages too).

## Feature specifications

Each spec states its status and points at the code. A `Planned` label means the current code does not implement it.

| Area | Spec |
|---|---|
| Shared | [Authentication](features/shared/authentication/spec.md) · [Password reset](features/shared/password-reset/spec.md) · [Profile](features/shared/profile/spec.md) · [POS shell, printer and PWA](features/shared/pos-shell-and-pwa/spec.md) |
| Cashier | [POS ordering](features/cashier/pos-ordering/spec.md) · [Pending orders](features/cashier/pending-orders/spec.md) · [Served orders](features/cashier/served-orders/spec.md) |
| Admin | [Branch setup](features/admin/branch-setup/spec.md) · [Cashier accounts](features/admin/cashier-accounts/spec.md) · [Receipt and menu settings](features/admin/receipt-and-menu-settings/spec.md) · [Statistics and reports](features/admin/statistics-and-reports/spec.md) |

## Progress logs

Logs live in [`logs/`](logs/) as `PROGRESS-YYYY-MM-DD.md` (Asia/Manila date). Append an entry for every code change in the same work session.

- [27 November 2026](logs/PROGRESS-2026-11-27.md) — repository restructure to the Vendo layout, documentation set created.

## Status vocabulary

- **Implemented** — routes and server-side behavior exist for the stated scope. This does not claim production readiness.
- **Partial** — some server-side behavior exists, but a required rule, transition, or lifecycle step is missing.
- **Planned** — not in the code.

## Repository layout

```text
AGENTS.md              working rules (kept at the root so agents find it)
README.md              project overview
docs/                  everything below
  README.md            this index
  architecture.md  schema.md  security.md  testing.md  setup.md
  domain-feature-status.md  feature-implementation-guide.md
  order-flow-decisions.md   future-plan.md
  domains/             one page per role
  features/<role>/<feature>/spec.md
  logs/                PROGRESS-YYYY-MM-DD.md
app/
  Http/Controllers/    Admin/  Auth/  Cashier/  + shared root controllers
  Http/Middleware/     EnsureAdmin
  Models/              Sales/  Catalog/  Settings/  + User
  Support/             Pos (branch, time, SQL and password helpers)
resources/views/       admin/  auth/  cashier/  emails/  shared/
public/                assets/  modules/{admin,auth,cashier,shared}/<page>/  manifest, service worker
routes/  database/  config/  storage/  tests/
```
