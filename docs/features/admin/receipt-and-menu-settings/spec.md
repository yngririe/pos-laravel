# Receipt and Menu Settings

**Status:** Implemented  
**Reviewed:** 27 November 2026

## Current behavior

All routes are admin-only.

- `GET /settings` shows receipt settings (the single row `id = 1`, created on first visit if missing) and every menu item.
- `POST /settings/receipt` saves store name, address, contact, Facebook URL, TIN, header, footer, and the `show_discount`, `show_cashier`, `show_order_type`, `show_payment_type`, `show_beeper` flags.
- `POST /settings/logo` uploads a logo (`jpg/jpeg/png/webp`, max 2 MB) to `public/assets/images/settings/`.
- `POST /settings/menu`, `PUT /settings/menu/{menuItem}` and `DELETE /settings/menu/{menuItem}` manage items: unique name (max 100), category (max 50), price 0 to 999999.99, optional discounted price not above the price, optional image (max 3 MB, saved to `public/assets/images/menu/`), and an "Available on cashier menu" switch (`is_active`). Hidden items disappear from the POS menu. Replacing or deleting an item removes its managed image file.

## Gaps and acceptance direction

- Receipt settings are shared by **all branches**.
- Deleting a menu item does not touch past orders (`order_items` keep their own name and price).
- Verified by static review only.

## Source evidence

`app/Http/Controllers/Admin/SettingsController.php`, `app/Models/Catalog/MenuItem.php`, `app/Models/Settings/ReceiptSetting.php`, `resources/views/admin/settings/index.blade.php`, `public/modules/admin/settings/`

## Related documentation

See [domain status](../../../domain-feature-status.md), the relevant [domain page](../../../domains/), and the [feature implementation guide](../../../feature-implementation-guide.md).
