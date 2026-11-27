# Admin Domain

An **admin** account is a branch owner. It has every cashier page plus the admin-only pages below. All admin routes sit behind `auth` and `EnsureAdmin`, except branch creation, which uses a setup key instead.

| Capability | Route | Controller | Spec |
|---|---|---|---|
| Receipt settings, logo, menu | `/settings*` | `Admin\SettingsController` | [spec](../features/admin/receipt-and-menu-settings/spec.md) |
| Statistics, archive, report | `/statistics*` | `Admin\StatisticsController`, `Admin\DailyFinanceController`, `Admin\StatisticsReportController` | [spec](../features/admin/statistics-and-reports/spec.md) |
| Create cashier | `/cashiers/create`, `/cashiers` | `Admin\CashierController` | [spec](../features/admin/cashier-accounts/spec.md) |
| Create branch + owner | `/branches/create`, `/branches` | `Admin\BranchController` | [spec](../features/admin/branch-setup/spec.md) |

Views: `resources/views/admin/`. Rules: [AGENTS.md](../../AGENTS.md), [security](../security.md).
