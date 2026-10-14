<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Served — Twist &amp; Roll</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= $base_url ?>assets/index.css">
    <link rel="stylesheet" href="<?= $base_url ?>modules/homepage/homepage.css">
    <link rel="stylesheet" href="<?= $base_url ?>modules/served/served.css">
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#1C3924">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <link rel="apple-touch-icon" href="/assets/images/icon-192.png">
    <script src="{{ asset('assets/js/csrf.js') }}"></script>
</head>
<body>
<header class="navbar">
    <div class="navbar__logo-wrap">
    <a href="{{ route('home') }}" style="display:flex;align-items:center;">
        <img src="<?= $base_url ?>assets/images/logo.png" class="navbar__logo-img" alt="Twist & Roll">
    </a>
    <span class="branch-badge"><?= htmlspecialchars($branch_name) ?></span>
</div>
    <nav class="navbar__nav">
        <a href="{{ route('home') }}"       class="nav-link <?= $current_page==='home'       ?'nav-link--active':''?>">Home</a>
        <a href="{{ route('orders') }}"     class="nav-link <?= $current_page==='orders'     ?'nav-link--active':''?>">Orders</a>
        <a href="{{ route('served') }}"     class="nav-link <?= $current_page==='served'     ?'nav-link--active':''?>">Served</a>
        <?php if ($role === 'admin'): ?>
        <a href="{{ route('statistics') }}" class="nav-link <?= $current_page==='statistics' ?'nav-link--active':''?>">Statistics</a>
        <?php endif; ?>
    </nav>
    <div class="navbar__right">
        <div class="navbar__datetime">
            <div id="current-day" class="navbar__day"></div>
            <div id="current-date" class="navbar__date"></div>
        </div>
        <div class="profile-menu">
            <button id="profile-btn" class="profile-btn">
                <?php if (!empty($nav_user['avatar'])): ?>
                    <img src="<?= htmlspecialchars($nav_user['avatar']) ?>" class="profile-icon" alt="Profile" style="object-fit:cover;border-radius:50%;">
                <?php else: ?>
                    <img src="<?= $base_url ?>assets/images/profile.png" class="profile-icon" alt="Profile">
                <?php endif; ?>
            </button>
            <div class="profile-dropdown" id="profile-dropdown">
                <a href="{{ route('profile') }}" class="dropdown-item">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="7" r="4"/><path d="M5.5 21a6.5 6.5 0 0 1 13 0"/></svg>
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
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                    Logout
                </button>
            </div>
        </div>
    </div>
</header>

<div class="served-page">
    <div class="served-title">Served</div>
    <div style="font-size:12px;color:#777;margin-bottom:10px;">
        Showing served orders since <?= date('M d, Y h:i A', strtotime($reset_datetime)) ?>
    </div>
    <div class="served-controls">
        <div class="served-search-wrapper">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><circle cx="7" cy="7" r="6"/><line x1="11" y1="11" x2="15" y2="15"/></svg>
            <input type="text" id="servedSearch" class="served-search" placeholder="Search orders">
        </div>
        <div class="served-filters">
            <button class="served-filter active" data-filter="all"   data-group="payment">All</button>
            <button class="served-filter"        data-filter="cash"  data-group="payment">Cash</button>
            <button class="served-filter"        data-filter="gcash" data-group="payment">GCash</button>
            <?php if (!empty($unique_biz_dates)): ?>
            <span class="served-filter-divider">·</span>
            <div class="served-day-dropdown" id="served-day-dropdown">
                <button class="served-day-dropdown__btn" id="served-day-btn" type="button">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    <span id="served-day-label">Date</span>
                    <span class="served-day-dropdown__arrow">▼</span>
                </button>
                <div class="served-day-dropdown__menu" id="served-day-menu">
                    <div class="served-day-option served-day-option--active" data-value="all">All dates</div>
                    <?php foreach ($unique_biz_dates as $bd): ?>
                    <div class="served-day-option" data-value="<?= htmlspecialchars($bd) ?>">
                        <?= date('M j, Y', strtotime($bd)) ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="served-grid" id="served-grid">
        <?php if (empty($served_orders)): ?>
        <div class="served-empty" id="served-empty">
            <svg width="52" height="52" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="1"/><line x1="9" y1="12" x2="15" y2="12"/><line x1="9" y1="16" x2="13" y2="16"/></svg>
            <p>No served orders yet.</p>
        </div>
        <?php else: ?>
        <?php foreach ($served_orders as $o):
            $names=$prices=$qtys=[];
            if($o['item_names'])  $names  = explode('||',$o['item_names']);
            if($o['item_prices']) $prices = explode('||',$o['item_prices']);
            if($o['item_qtys'])   $qtys   = explode('||',$o['item_qtys']);
            $ordered    = date('M d, g:i A', strtotime($o['created_at']));
            $served     = !empty($o['served_at']) ? date('M d, g:i A', strtotime($o['served_at'])) : '—';
            $type       = $o['order_type'];
            $typeLabel = ['dine-in'=>'Dine in','take-out'=>'Take out','delivery'=>'Delivery','pick-up'=>'Pick up'][$type] ?? ucfirst($type);
            $badgeClass = $type==='dine-in' ? 'dine' : 'takeout';
            $bizDate    = $o['biz_date'] ?? '';
        ?>
        <div class="served-card" data-type="<?= htmlspecialchars($type) ?>" data-payment="<?= htmlspecialchars(strtolower($o['payment_method'])) ?>" data-biz-date="<?= htmlspecialchars($bizDate) ?>">
            <div class="served-card__top">
                <span class="served-status">✓ Served</span>
                <span class="served-type-badge <?= $badgeClass ?>"><?= $typeLabel ?></span>
                <?php if ($o['beeper_number']): ?><span class="served-beeper">#<?= $o['beeper_number'] ?></span><?php endif; ?>
            </div>
            <div class="served-card__items">
                <?php for ($i=0;$i<count($names);$i++): if(!$names[$i]) continue; ?>
                <div class="served-card__row">
                    <span><?= $qtys[$i] ?>x <?= htmlspecialchars($names[$i]) ?></span>
                    <span>Php <?= number_format($prices[$i]*$qtys[$i],0) ?></span>
                </div>
                <?php endfor; ?>
            </div>
            <div class="served-card__divider"></div>
            <div class="served-card__meta">
                <div class="served-meta-row"><span>Mode of Payment</span><span><?= ucfirst($o['payment_method']) ?></span></div>
                <div class="served-meta-row"><span>Subtotal</span><span>Php <?= number_format($o['subtotal'],0) ?></span></div>
                <?php if ($o['discount']>0): ?>
                <div class="served-meta-row"><span>Discount</span><span class="served-discount">−Php <?= number_format($o['discount'],0) ?></span></div>
                <?php endif; ?>
            </div>
            <div class="served-card__total">
                <strong>Total</strong>
                <span class="served-total-price">Php <?= number_format($o['total'],2) ?></span>
            </div>
            <div class="served-card__footer">Ordered: <?= $ordered ?> &nbsp;·&nbsp; Served: <?= $served ?></div>
        </div>
        <?php endforeach; endif; ?>
    </div>
    <div class="pagination" id="pagination"></div>
</div>
<script src="<?= $base_url ?>modules/served/served.js"></script>
<script src="/assets/js/pwa_register.js"></script>
</body>
</html>