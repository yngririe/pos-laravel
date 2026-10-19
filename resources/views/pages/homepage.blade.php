<!DOCTYPE html>
<html lang="en" data-theme="light"><head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Twist &amp; Roll POS</title>
    <link rel="stylesheet" href="<?= $base_url ?>assets/index.css?v=20261015-xpad1">
    <link rel="stylesheet" href="<?= $base_url ?>modules/homepage/homepage.css?v=20261015-delivery-notify1">
    <script>
        const DISCOUNT_MAP = <?= json_encode($discount_map) ?>;
        const MENU_ITEMS   = <?= json_encode(array_column($menu_items, null, 'id')) ?>;
        const BASE_URL     = <?= json_encode($base_url) ?>;
    </script>
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#1C3924">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <link rel="apple-touch-icon" href="/assets/images/icon-192.png">
    <script src="{{ asset('assets/js/csrf.js?v=20261015-xpad1') }}"></script>
    <link rel="stylesheet" href="{{ asset('assets/mobile.css?v=20261015-mobile2') }}">
</head>
<body>

<div class="toast-container" id="toast-container"></div>

<header class="navbar">
    <div class="navbar__logo-wrap">
        <a href="{{ route('pos.home') }}"
            target="_self"
            class="navbar__logo-link">
            <img src="<?= $base_url ?>assets/images/logo.png" alt="Twist &amp; Roll" class="navbar__logo-img">
        </a>
        <span class="branch-badge"><?= htmlspecialchars($branch_name) ?></span>
    </div>
    <nav class="navbar__nav">
        <a href="{{ route('pos.home') }}"
            target="_self"
            class="nav-link <?= $current_page==='home' ? 'nav-link--active' : '' ?>">
                Home
        </a>
        <a href="{{ route('orders') }}"     class="nav-link <?= $current_page==='orders'     ?'nav-link--active':'' ?>">Orders</a>
        <a href="{{ route('served') }}"     class="nav-link <?= $current_page==='served'     ?'nav-link--active':'' ?>">Served</a>
        <?php if ($role === 'admin'): ?>
        <a href="{{ route('statistics') }}" class="nav-link <?= $current_page==='statistics' ?'nav-link--active':'' ?>">Statistics</a>
        <?php endif; ?>
    </nav>
    <div class="navbar__right">
        <div class="navbar__datetime">
            <div class="navbar__day"  id="current-day"></div>
            <div class="navbar__date" id="current-date"></div>
        </div>
        <div class="profile-menu" id="profile-menu">
            <button class="profile-btn" id="profile-btn" aria-label="Profile">
                <?php if (!empty($nav_user['avatar'])): ?>
                    <img src="<?= htmlspecialchars($nav_user['avatar']) ?>" class="profile-icon" alt="Profile" style="object-fit:cover;border-radius:50%;">
                <?php else: ?>
                    <img src="<?= $base_url ?>assets/images/profile.png" class="profile-icon" alt="Profile">
                <?php endif; ?>
            </button>
            <div class="profile-dropdown" id="profile-dropdown">
                <a href="{{ route('profile') }}" class="dropdown-item">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="7" r="4"/><path d="M5.5 21a6.5 6.5 0 0 1 13 0"/>
                    </svg>
                    Profile
                </a>
                <?php if ($role === 'admin'): ?>
                                <a href="{{ route('settings') }}" class="dropdown-item">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .34 1.88l.06.06-2.83 2.83-.06-.06A1.7 1.7 0 0 0 15 19.4a1.7 1.7 0 0 0-1 .6 1.7 1.7 0 0 0-.4 1.1V21H9.6v-.1A1.7 1.7 0 0 0 8.5 19.4a1.7 1.7 0 0 0-1.88.34l-.06.06-2.83-2.83.06-.06A1.7 1.7 0 0 0 4.1 15a1.7 1.7 0 0 0-.6-1 1.7 1.7 0 0 0-1.1-.4H2V9.6h.4A1.7 1.7 0 0 0 4.1 8.5a1.7 1.7 0 0 0-.34-1.88l-.06-.06 2.83-2.83.06.06A1.7 1.7 0 0 0 8.5 4.1a1.7 1.7 0 0 0 1-.6A1.7 1.7 0 0 0 9.9 2H14v.4a1.7 1.7 0 0 0 1.1 1.7 1.7 1.7 0 0 0 1.88-.34l.06-.06 2.83 2.83-.06.06A1.7 1.7 0 0 0 19.5 8.5a1.7 1.7 0 0 0 .6 1 1.7 1.7 0 0 0 1.1.4h.4V14h-.4a1.7 1.7 0 0 0-1.8 1z"/></svg>
                    Settings
                </a>
                <a href="{{ route('cashiers.create') }}" class="dropdown-item">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="9" cy="7" r="4"/><path d="M2 21a7 7 0 0 1 14 0"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="16" y1="11" x2="22" y2="11"/>
                    </svg>
                    Create Cashier
                </a>
                <?php endif; ?>
                <button class="logout-btn" id="logout-btn" data-logout-url="{{ route('logout') }}">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                        <polyline points="16 17 21 12 16 7"/>
                        <line x1="21" y1="12" x2="9" y2="12"/>
                    </svg>
                    Logout
                </button>
            </div>
        </div>
    </div>
</header>

<main class="home-main">

    <!-- ═══════════════════════════════════════════════════════════
         SCREEN 1 — Start new order
         ═══════════════════════════════════════════════════════════ -->
    <section class="screen screen--start screen--active" id="screen-start">
        <div class="start-hero">
            <h1 class="start-hero__title">Welcome to Twist &amp; Roll</h1>
            <p class="start-hero__subtitle">Your new go-to cravings spot for Sushi</p>
        </div>
        <button type="button" class="start-order-btn" id="start-order-btn">
            <img src="<?= $base_url ?>assets/images/mouse-pointer-click.png" alt="" class="start-order-btn__icon">
            <span class="start-order-btn__text">
                <span class="start-order-btn__title">START NEW ORDER</span>
                <span class="start-order-btn__sub">Tap to begin your delicious journey</span>
            </span>
        </button>
    </section>

    <!-- ═══════════════════════════════════════════════════════════
         SCREEN 2 — Order type
         ═══════════════════════════════════════════════════════════ -->
    <section class="screen screen--type" id="screen-type">
        <div class="start-hero">
            <h1 class="start-hero__title">Welcome to Twist &amp; Roll</h1>
            <p class="start-hero__subtitle">Your new go-to cravings spot for Sushi</p>
        </div>
        <div class="order-type-grid">
            <button type="button" class="order-type-card order-type-card--dine-in" id="pick-dine-in" data-type="dine-in">
                <span class="order-type-card__icon-wrap">
                    <img src="<?= $base_url ?>assets/images/dine-in.png" alt="" class="order-type-card__icon">
                </span>
                <span class="order-type-card__title">Dine In</span>
                <span class="order-type-card__sub">Enjoy warm at our tables</span>
            </button>
            <button type="button" class="order-type-card order-type-card--take-out" id="pick-take-out" data-type="take-out">
                <span class="order-type-card__icon-wrap">
                    <img src="<?= $base_url ?>assets/images/take-out.png" alt="" class="order-type-card__icon">
                </span>
                <span class="order-type-card__title">Take Out</span>
                <span class="order-type-card__sub">Packaged fresh for the road</span>
            </button>
            <button type="button" class="order-type-card" id="pick-delivery" data-type="delivery">
                <span class="order-type-card__icon-wrap order-type-card__svg-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 7h11v10H3z"/><path d="M14 10h3l4 4v3h-7z"/><circle cx="7" cy="18" r="2"/><circle cx="18" cy="18" r="2"/></svg>
                </span>
                <span class="order-type-card__title">Delivery</span>
                <span class="order-type-card__sub">Send the order to the customer</span>
            </button>
            <button type="button" class="order-type-card" id="pick-pickup" data-type="pick-up">
                <span class="order-type-card__icon-wrap order-type-card__svg-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M5 8h14l-1 12H6L5 8Z"/><path d="M9 8a3 3 0 0 1 6 0"/><path d="M8 13h8"/></svg>
                </span>
                <span class="order-type-card__title">Pick Up</span>
                <span class="order-type-card__sub">Prepare for customer collection</span>
            </button>
        </div>
    </section>

    <!-- ═══════════════════════════════════════════════════════════
         SCREEN 3 — Menu + ordering (order type is already set by
         whichever card was tapped on screen 2)
         ═══════════════════════════════════════════════════════════ -->
    <section class="screen screen--order pos-layout" id="screen-order">

    <section class="menu-panel">
        <h2 class="menu-panel__title">Menu</h2>
        <div class="menu-grid" id="menu-grid">
            <?php foreach ($menu_items as $item): ?>
            <div class="menu-card"
                 data-id="<?= $item['id'] ?>"
                 data-name="<?= htmlspecialchars($item['name']) ?>"
                 data-price="<?= $item['price'] ?>"
                 data-category="<?= $item['category'] ?>">
                <div class="menu-card__image-wrap">
                    <img src="<?= $base_url . htmlspecialchars($item['image']) ?>"
                         alt="<?= htmlspecialchars($item['name']) ?>"
                         class="menu-card__image"
                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                    <div class="menu-card__image-placeholder" style="display:none;">
                        <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2">
                            <rect x="3" y="3" width="18" height="18" rx="2"/>
                            <circle cx="8.5" cy="8.5" r="1.5"/>
                            <polyline points="21 15 16 10 5 21"/>
                        </svg>
                    </div>
                </div>
                <div class="menu-card__info">
                    <span class="menu-card__name"><?= htmlspecialchars($item['name']) ?></span>
                    <span class="menu-card__price">Php <?= number_format($item['price']) ?></span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>

    <aside class="order-panel">

        <div class="order-panel__type-badge" id="order-type-badge">
            <span class="order-type-badge__icon-wrap">
                <img src="<?= $base_url ?>assets/images/dine-in.png" alt="" class="order-type-badge__icon" id="order-type-badge-icon">
            </span>
            <span class="order-type-badge__label" id="order-type-badge-label">Dine in</span>
            <button type="button" class="order-type-badge__change" id="change-order-type-btn">Change</button>
        </div>

        <div class="order-panel__beeper" id="beeper-wrap">
            <label class="beeper-label" for="beeper-input" id="beeper-label">Beeper #</label>
            <input type="text" inputmode="numeric" class="beeper-input" id="beeper-input" maxlength="100" placeholder="Enter number (1-16)" autocomplete="off">
        </div>
        <p class="beeper-error-msg" id="beeper-error">Beeper number is required.</p>

        <div class="order-items-section">
            <p class="order-items-label">ORDER ITEMS</p>
            <div class="order-items-list" id="order-items-list">
                <div class="order-empty" id="order-empty">
                    <svg class="order-empty__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2">
                        <circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/>
                        <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
                    </svg>
                    <span class="order-empty__text">No items yet</span>
                    <span class="order-empty__sub">Tap menu items to add</span>
                </div>
            </div>
        </div>

        <div class="payment-summary">
            <p class="payment-summary__title">PAYMENT SUMMARY</p>
            <div class="payment-summary__row">
                <label class="discount-label" for="discount-select">Discount</label>
                <select class="discount-select" id="discount-select">
                    <option value="none">No discount</option>
                    <option value="20">20%</option>
                    <option value="10">10%</option>
                    <option value="minus10">− Php 10</option>
                </select>
                <span class="discount-value" id="discount-value">Php 0</span>
            </div>
            <div class="payment-summary__row">
                <span>Subtotal</span>
                <span class="subtotal-value-normal" id="subtotal-value">Php 0</span>
            </div>
            <div class="payment-summary__row" id="delivery-fee-row" style="display:none;">
                <span>Delivery Fee</span>
                <span class="subtotal-value-normal" id="delivery-fee-value">Php 30</span>
            </div>
            <div class="payment-summary__divider"></div>
            <div class="payment-summary__total">
                <span>Total</span>
                <span class="total-amount" id="total-value">Php 0.00</span>
            </div>
        </div>

        <div class="payment-methods">
            <button class="payment-btn payment-btn--active" data-method="cash">Cash</button>
            <button class="payment-btn" data-method="gcash">Gcash</button>
        </div>

        <div class="amount-input-wrap" id="amount-wrap">
            <input type="number" class="amount-input" id="amount-input" placeholder="Php 0.00" step="1" min="0">
        </div>

        <div class="change-display" id="change-display" style="display:none;">
            <span class="change-display__label">Change</span>
            <span class="change-display__amount" id="change-amount">Php 0.00</span>
        </div>

        <button class="place-order-btn" id="place-order-btn" disabled>
            Place order – Php 0.00
        </button>

    </aside>
    </section>
</main>

    <script src="<?= $base_url ?>assets/js/main.js?v=20261015-xpad1"></script>
    <script>
        /*
        * Use the BluetoothPrinter instance owned by the persistent POS shell.
        * This keeps the Bluetooth GATT connection alive while the iframe
        * navigates between POS pages.
        */
        if (window.parent !== window && window.parent.BluetoothPrinter) {
            window.BluetoothPrinter = window.parent.BluetoothPrinter;
            console.log('[Printer] Using persistent printer from POS shell.');
        } else {
            console.warn('[Printer] Persistent POS shell printer not found.');
        }
    </script>
    <script src="<?= $base_url ?>modules/homepage/homepage.js?v=20261015-delivery-notify1"></script>
    <script src="/assets/js/pwa_register.js?v=20261015-xpad1"></script>
<?php if ($role === 'cashier'): ?>
<script>window.ORDER_NOTIFICATIONS_URL = @json(route('orders.notifications')); window.ORDERS_PAGE_URL = @json(route('orders'));</script>
<script src="{{ asset('assets/js/order-notifications.js?v=20261015-bell1') }}"></script>
<?php endif; ?>
</body>
</html>