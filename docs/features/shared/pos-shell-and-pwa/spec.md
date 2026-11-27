# POS Shell, Printer and PWA

**Status:** Implemented  
**Reviewed:** 27 November 2026

## Current behavior

- `GET /home` (named `home`) renders `shared.pos-shell`: a full-window page holding one iframe whose `src` is `/pos/home`. The shell loads `assets/js/escpos_bluetooth.js` once, so the Bluetooth receipt-printer connection survives while the iframe moves between Home, Orders, Served and Statistics. It exposes `window.getPersistentBluetoothPrinter()` and `window.posNavigateTop(url)` (used for a full-window logout).
- The app is installable: `public/manifest.json` (name "Twist & Roll POS", landscape, standalone) and `public/service-worker.js` (cache `tnr-pos-v5`, offline page `/offline.html`), registered by `assets/js/pwa_register.js`.
- `assets/js/csrf.js` and `order-notifications.js` support CSRF handling and cashier alerts.

## Gaps and acceptance direction

- Bump the service-worker cache name when cached assets change.
- Printing needs a Bluetooth ESC/POS printer and a browser with Web Bluetooth; not tested here.

## Source evidence

`routes/web.php`, `resources/views/shared/pos-shell.blade.php`, `public/assets/js/`, `public/manifest.json`, `public/service-worker.js`

## Related documentation

See [domain status](../../../domain-feature-status.md), the relevant [domain page](../../../domains/), and the [feature implementation guide](../../../feature-implementation-guide.md).
