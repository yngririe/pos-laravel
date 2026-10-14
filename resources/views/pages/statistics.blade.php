<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statistics — Twist &amp; Roll</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= $base_url ?>assets/index.css">
    <link rel="stylesheet" href="<?= $base_url ?>modules/homepage/homepage.css">
    <link rel="stylesheet" href="<?= $base_url ?>modules/statistics/statistics.css">
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
        <a href="{{ route('home') }}"       class="nav-link">Home</a>
        <a href="{{ route('orders') }}"     class="nav-link">Orders</a>
        <a href="{{ route('served') }}"     class="nav-link">Served</a>
        <?php if ($role === 'admin'): ?>
        <a href="{{ route('statistics') }}" class="nav-link nav-link--active">Statistics</a>
        <?php endif; ?>
    </nav>
    <div class="navbar__right">
        <div class="navbar__datetime">
            <div id="current-day"  class="navbar__day"></div>
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

<div class="stats-wrapper">

    <aside class="stats-sidebar <?= $sidebar_open ? 'stats-sidebar--open' : '' ?>" id="stats-sidebar">
        <div class="sidebar-header">
            <button class="sidebar-toggle" id="sidebar-toggle"
                onclick="<?= $sidebar_open ? 'location.href=\'?page=statistics&year='.$selected_year.'\'' : 'closeSidebar()' ?>">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                    <line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/>
                </svg>
            </button>
            <a href="{{ route('statistics') }}" class="sidebar-title">Statistics</a>
        </div>
        <div class="sidebar-tree">
            <div class="year-nav">
                <span class="folder-icon">📁</span>
                <select class="year-select" onchange="location.href='?page=statistics&year='+this.value" onfocus="this.size=4;" onblur="this.size=1;">
                    <?php foreach ($year_range as $yr): ?>
                    <option value="<?= $yr ?>" <?= $yr==$selected_year?'selected':'' ?>><?= $yr ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="tree-months">
                <?php foreach ($months_list as $num => $name): ?>
                <div class="tree-month">
                    <div class="tree-month__label <?= ($num==$selected_month&&$sidebar_open)?'active':'' ?>"
                         onclick="toggleMonth(<?= $selected_year ?>,<?= $num ?>)">
                        <span class="tree-arrow" id="ma-<?= $selected_year ?>-<?= $num ?>"><?= ($num==$selected_month&&$sidebar_open)?'▾':'›' ?></span>
                        <span class="folder-icon">📂</span>
                        <span><?= $name ?></span>
                    </div>
                    <div class="tree-month__children" id="mc-<?= $selected_year ?>-<?= $num ?>"
                         style="display:<?= ($num==$selected_month&&$sidebar_open)?'block':'none' ?>">
                        <a href="?page=statistics&sidebar=1&year=<?= $selected_year ?>&month=<?= $num ?>&section=orders"
                           class="tree-item <?= ($selected_section==='orders'&&$num==$selected_month)?'tree-item--active':'' ?>">
                            <img src="<?= $base_url ?>assets/images/orders1_icon.png" class="sidebar-icon"> Orders <span class="tree-count"><?= $annual_by_month[$num]['orders']??0 ?></span>
                        </a>
                        <a href="?page=statistics&sidebar=1&year=<?= $selected_year ?>&month=<?= $num ?>&section=served"
                           class="tree-item <?= ($selected_section==='served'&&$num==$selected_month)?'tree-item--active':'' ?>">
                            <img src="<?= $base_url ?>assets/images/served_icon.png" class="sidebar-icon"> Served <span class="tree-count"><?= $annual_by_month[$num]['served']??0 ?></span>
                        </a>
                        <a href="?page=statistics&sidebar=1&year=<?= $selected_year ?>&month=<?= $num ?>&section=voids"
                           class="tree-item <?= ($selected_section==='voids'&&$num==$selected_month)?'tree-item--active':'' ?>">
                            <img src="<?= $base_url ?>assets/images/voids_icon.png" class="sidebar-icon"> Voids <span class="tree-count"><?= $voids_by_month[$num]??0 ?></span>
                        </a>
                        <a href="?page=statistics&sidebar=1&year=<?= $selected_year ?>&month=<?= $num ?>&section=sales"
                           class="tree-item <?= ($selected_section==='sales'&&$num==$selected_month)?'tree-item--active':'' ?>">
                            <span class="sidebar-sales-icon">₱</span> Sales <span class="tree-count"><?= $sales_days_by_month[$num]??0 ?></span>
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
                <div class="tree-annual">
                    <span class="folder-icon">📁</span>
                    <span>Annual Income</span>
                    <span class="tree-count tree-count--annual">₱<?= number_format($annual_total,0) ?></span>
                </div>
            </div>
        </div>
    </aside>

    <main class="stats-main" id="stats-main">

        <?php if ($sidebar_open && $selected_section): ?>

        <div class="breadcrumb">
            <span class="breadcrumb__folder">📁 <?= $selected_year ?></span>
            <span class="breadcrumb__sep">›</span>
            <span class="breadcrumb__folder"><?= $months_list[$selected_month] ?></span>
            <span class="breadcrumb__sep">›</span>
            <span class="breadcrumb__current"><?= ucfirst($selected_section) ?></span>
        </div>

        <div class="section-view">
            <?php if ($selected_section === 'sales'): ?>
            <div class="section-view__top">
                <h2 class="section-view__title">Sales</h2>
                <div class="section-view__date">
                    <img src="<?= $base_url ?>assets/images/calendar_icon.png" class="date-icon">
                    <?= $months_list[$selected_month] ?> <?= $selected_year ?> · Business days start at 3:00 PM
                </div>
            </div>

            <div class="section-stats sales-summary-stats">
                <div class="section-stat"><div><div class="section-stat__label">Recorded Days</div><div class="section-stat__val"><?= $section_stats['total']??0 ?></div></div></div>
                <div class="section-stat"><div><div class="section-stat__label">Total Sales</div><div class="section-stat__val section-stat__val--green">₱<?= number_format($section_stats['sum']??0,0) ?></div></div></div>
                <div class="section-stat"><div><div class="section-stat__label">Highest Sales Day</div><div class="section-stat__val">₱<?= number_format($section_stats['highest']??0,0) ?></div></div></div>
                <div class="section-stat"><div><div class="section-stat__label">Lowest Sales Day</div><div class="section-stat__val">₱<?= number_format($section_stats['lowest']??0,0) ?></div></div></div>
            </div>

            <div class="section-chart-wrap">
                <h3 class="section-chart-title">Total Sales per Day</h3>
                <canvas id="sectionBarChart" height="80"></canvas>
            </div>

            <div class="orders-list-wrap">
                <div class="orders-list__header"><h3>Daily Sales Archive</h3></div>
                <div class="table-wrap">
                    <table class="orders-table sales-archive-table">
                        <thead><tr><th>Business Day</th><th>Petty Cash</th><th>Cash Sales</th><th>GCash Sales</th><th>Total Sales</th><th>Expense</th><th>Total Money</th></tr></thead>
                        <tbody>
                        <?php if (empty($sales_archive)): ?>
                            <tr><td colspan="7" class="table-empty">No daily cash records for this month yet.</td></tr>
                        <?php else: foreach ($sales_archive as $day): ?>
                            <tr class="sales-day-row" tabindex="0" role="button" aria-label="View sales details for <?= date('F j, Y', strtotime($day['business_date'])) ?>"
                                data-sales-day='<?= htmlspecialchars(json_encode($day), ENT_QUOTES, 'UTF-8') ?>'>
                                <td><span class="sales-day-link"><?= date('M j, Y', strtotime($day['business_date'])) ?></span></td>
                                <td>₱<?= number_format($day['petty_cash'],0) ?></td>
                                <td>₱<?= number_format($day['cash_sales'],0) ?></td>
                                <td>₱<?= number_format($day['gcash_sales'],0) ?></td>
                                <td class="money-green">₱<?= number_format($day['total_sales'],0) ?></td>
                                <td>₱<?= number_format($day['expense'],0) ?></td>
                                <td class="money-green money-strong">₱<?= number_format($day['total_money'],0) ?></td>
                            </tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="sales-detail-modal" id="salesDetailModal" aria-hidden="true">
                <div class="sales-detail-modal__backdrop" data-sales-modal-close></div>
                <div class="sales-detail-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="salesDetailTitle">
                    <div class="sales-detail-modal__header">
                        <div>
                            <div class="sales-detail-modal__eyebrow">Business Day Details</div>
                            <h3 id="salesDetailTitle">Sales Details</h3>
                        </div>
                        <button type="button" class="sales-detail-modal__close" data-sales-modal-close aria-label="Close">&times;</button>
                    </div>
                    <div class="sales-detail-modal__body">
                        <div class="sales-detail-grid">
                            <div><span>Petty Cash</span><strong id="detailPettyCash">₱0</strong></div>
                            <div><span>Cash Sales</span><strong id="detailCashSales">₱0</strong></div>
                            <div><span>GCash Sales</span><strong id="detailGcashSales">₱0</strong></div>
                            <div><span>Total Sales</span><strong class="money-green" id="detailTotalSales">₱0</strong></div>
                            <div><span>Total Expenses</span><strong id="detailExpenseTotal">₱0</strong></div>
                            <div class="sales-detail-grid__total"><span>Total Money</span><strong id="detailTotalMoney">₱0</strong></div>
                        </div>
                        <div class="sales-detail-formula" id="detailFormula">₱0 + ₱0 − ₱0</div>
                        <div class="sales-detail-expenses">
                            <div class="sales-detail-expenses__head"><h4>Expenses</h4><span id="detailExpenseCount">0 entries</span></div>
                            <div id="detailExpenseList" class="sales-detail-expenses__list"></div>
                        </div>
                    </div>
                </div>
            </div>

            <script>
            (() => {
                const modal = document.getElementById('salesDetailModal');
                if (!modal) return;
                const peso = value => '₱' + Math.round(Number(value || 0)).toLocaleString('en-PH');
                const fmtTime = raw => {
                    if (!raw) return '—';
                    const normalized = String(raw).replace(' ', 'T') + (String(raw).includes('Z') || /[+-]\d\d:\d\d$/.test(String(raw)) ? '' : 'Z');
                    const d = new Date(normalized);
                    if (Number.isNaN(d.getTime())) return raw;
                    return new Intl.DateTimeFormat('en-PH', { timeZone: 'Asia/Manila', hour: 'numeric', minute: '2-digit', second: '2-digit', hour12: true }).format(d);
                };
                const open = row => {
                    let day;
                    try { day = JSON.parse(row.dataset.salesDay); } catch (_) { return; }
                    const date = new Date(day.business_date + 'T00:00:00');
                    document.getElementById('salesDetailTitle').textContent = new Intl.DateTimeFormat('en-PH', { month:'long', day:'numeric', year:'numeric' }).format(date);
                    document.getElementById('detailPettyCash').textContent = peso(day.petty_cash);
                    document.getElementById('detailCashSales').textContent = peso(day.cash_sales);
                    document.getElementById('detailGcashSales').textContent = peso(day.gcash_sales);
                    document.getElementById('detailTotalSales').textContent = peso(day.total_sales);
                    document.getElementById('detailExpenseTotal').textContent = peso(day.expense);
                    document.getElementById('detailTotalMoney').textContent = peso(day.total_money);
                    document.getElementById('detailFormula').textContent = `${peso(day.petty_cash)} + ${peso(day.total_sales)} − ${peso(day.expense)} = ${peso(day.total_money)}`;
                    const expenses = Array.isArray(day.expenses) ? day.expenses : [];
                    document.getElementById('detailExpenseCount').textContent = `${expenses.length} ${expenses.length === 1 ? 'entry' : 'entries'}`;
                    const list = document.getElementById('detailExpenseList');
                    list.innerHTML = expenses.length ? expenses.map((expense, index) => `
                        <div class="sales-expense-item">
                            <div><span class="sales-expense-item__number">Expense ${index + 1}</span><span class="sales-expense-item__time">${fmtTime(expense.created_at)}</span></div>
                            <strong>${peso(expense.amount)}</strong>
                        </div>`).join('') : '<div class="sales-expense-empty">No expenses recorded for this business day.</div>';
                    modal.classList.add('is-open');
                    modal.setAttribute('aria-hidden', 'false');
                    document.body.classList.add('modal-open');
                    modal.querySelector('.sales-detail-modal__close').focus();
                };
                const close = () => { modal.classList.remove('is-open'); modal.setAttribute('aria-hidden', 'true'); document.body.classList.remove('modal-open'); };
                document.querySelectorAll('.sales-day-row').forEach(row => {
                    row.addEventListener('click', () => open(row));
                    row.addEventListener('keydown', e => { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); open(row); } });
                });
                modal.querySelectorAll('[data-sales-modal-close]').forEach(el => el.addEventListener('click', close));
                document.addEventListener('keydown', e => { if (e.key === 'Escape' && modal.classList.contains('is-open')) close(); });
            })();
            </script>
            <?php else: ?>
            <div class="section-view__top">
                <h2 class="section-view__title"><?= ucfirst($selected_section) ?></h2>
                <div class="section-view__date">
                    <img src="<?= $base_url ?>assets/images/calendar_icon.png" class="date-icon">
                    <?= $months_list[$selected_month] ?> 1 – <?= date('t',mktime(0,0,0,$selected_month,1,$selected_year)) ?>, <?= $selected_year ?>
                </div>
            </div>

            <div class="section-stats">
                <div class="section-stat">
                    <div class="section-stat__icon section-stat__icon--purple">
                        <img src="<?= $base_url ?>assets/images/totalorder_icon.png" alt="">
                    </div>
                    <div>
                        <div class="section-stat__label">Total <?= ucfirst($selected_section) ?></div>
                        <div class="section-stat__val"><?= $section_stats['total']??0 ?></div>
                    </div>
                </div>
                <div class="section-stat">
                    <div class="section-stat__icon section-stat__icon--gold">
                        <img src="<?= $base_url ?>assets/images/highest_icon.png" alt="">
                    </div>
                    <div>
                        <div class="section-stat__label">Highest Order</div>
                        <div class="section-stat__val"><?= $section_stats['highest']?'₱'.number_format($section_stats['highest'],0):0 ?></div>
                        <?php if (!empty($high_date)): ?><div class="section-stat__sub"><?= date('M j, Y',strtotime($high_date)) ?></div><?php endif; ?>
                    </div>
                </div>
                <div class="section-stat">
                    <div class="section-stat__icon section-stat__icon--red">
                        <img src="<?= $base_url ?>assets/images/lowest_icon.png" alt="">
                    </div>
                    <div>
                        <div class="section-stat__label">Lowest Order</div>
                        <div class="section-stat__val"><?= $section_stats['lowest']?'₱'.number_format($section_stats['lowest'],0):0 ?></div>
                        <?php if (!empty($low_date)): ?><div class="section-stat__sub"><?= date('M j, Y',strtotime($low_date)) ?></div><?php endif; ?>
                    </div>
                </div>
                <div class="section-stat">
                    <div class="section-stat__icon section-stat__icon--green">
                        <img src="<?= $base_url ?>assets/images/totalsales_icon.png" alt="">
                    </div>
                    <div>
                        <div class="section-stat__label">Total Sales</div>
                        <div class="section-stat__val">₱<?= number_format($section_stats['sum']??0,0) ?></div>
                    </div>
                </div>
            </div>

            <div class="section-chart-wrap">
                <h3 class="section-chart-title">Total Sales per Day</h3>
                <canvas id="sectionBarChart" height="80"></canvas>
            </div>

            <div class="orders-list-wrap">
                <div class="orders-list__header">
                    <h3>Orders List</h3>
                    <div class="orders-list__actions">
                        <button class="list-action-btn" onclick="filterTable()">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
                            Filter
                        </button>
                        <button class="list-action-btn" onclick="location.reload()">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg>
                        </button>
                    </div>
                </div>
                <div class="table-wrap">
                    <table class="orders-table" id="orders-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Date &amp; Time</th>
                                <th>Order Type</th>
                                <th>Payment Method</th>
                                <th>Total</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($section_orders)): ?>
                        <tr><td colspan="6" class="table-empty">No records found.</td></tr>
                        <?php else: ?>
                        <?php foreach ($section_orders as $o):
                            $items = [];
                            if (!empty($o['items_data'])) {
                                foreach (explode(';;', $o['items_data']) as $raw) {
                                    $parts = explode('|', $raw, 2);
                                    if (count($parts) < 2) continue;
                                    $xPos = strpos($parts[0], 'x ');
                                    if ($xPos === false) continue;
                                    $items[] = [
                                        'qty'   => (int)substr($parts[0], 0, $xPos),
                                        'name'  => trim(substr($parts[0], $xPos + 2)),
                                        'price' => (float)$parts[1],
                                    ];
                                }
                            }
                            $discount      = (float)($o['discount']           ?? 0);
                            $subtotal      = (float)($o['subtotal']           ?? $o['total']);
                            $refund        = (float)($o['refund_amount']      ?? 0);
                            $gcashExtraAmt = (float)($o['gcash_extra_amount'] ?? 0);

                            $ordered_at_fmt = date('M j, Y g:i A', strtotime($o['created_at']));
                            $served_at_fmt  = !empty($o['served_at']) ? date('M j, Y g:i A', strtotime($o['served_at'])) : '';

                            $display_dt = ($selected_section === 'served' && !empty($o['served_at']))
                                ? $served_at_fmt
                                : $ordered_at_fmt;
                        ?>
                        <tr
                            data-items='<?= htmlspecialchars(json_encode($items), ENT_QUOTES, 'UTF-8') ?>'
                            data-discount="<?= $discount ?>"
                            data-subtotal="<?= $subtotal ?>"
                            data-refund-amount="<?= $refund ?>"
                            data-served-at="<?= htmlspecialchars($served_at_fmt, ENT_QUOTES, 'UTF-8') ?>"
                            data-ordered-at="<?= htmlspecialchars($ordered_at_fmt, ENT_QUOTES, 'UTF-8') ?>"
                            data-gcash-extra-amount="<?= $gcashExtraAmt ?>"
                        >
                            <td><?= (int)$o['beeper_number'] ?></td>
                            <td><?= $display_dt ?></td>
                            <td><?= ['dine-in'=>'Dine in','take-out'=>'Take out','delivery'=>'Delivery','pick-up'=>'Pick up'][$o['order_type']] ?? ucfirst($o['order_type']) ?></td>
                            <td><?= ucfirst($o['payment_method']) ?></td>
                            <td>₱<?= number_format($o['total'],0) ?></td>
                            <td><span class="status-badge status-<?= $o['status'] ?>"><?= ucfirst($o['status']) ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <?php else: ?>

        <div class="dash-header">
            <button class="sidebar-toggle-btn" id="open-sidebar" onclick="openSidebar()">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                    <line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/>
                </svg>
            </button>
            <h1 class="dash-title">Statistics</h1>
            <div class="dash-actions">
                <button class="dash-action-btn dash-action-btn--excel" onclick="openExcelModal()">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                        <polyline points="14 2 14 8 20 8"/>
                        <line x1="8" y1="13" x2="16" y2="13"/>
                        <line x1="8" y1="17" x2="16" y2="17"/>
                    </svg>
                    Excel Report
                </button>
            </div>
        </div>

        <section class="overview-section finance-overview">
            <div class="finance-overview__header">
                <div>
                    <p class="overview-label">Today’s Business Day</p>
                    <div class="finance-date"><?= date('F j, Y', strtotime($today)) ?> · 3:00 PM to next day 2:00 AM</div>
                </div>
                <?php if (session('cash_record_saved')): ?><span class="finance-saved">Petty cash saved</span><?php elseif (session('expense_added')): ?><span class="finance-saved">Expense added</span><?php endif; ?>
            </div>
            <div class="finance-grid">
                <div class="finance-card finance-card--editable finance-card--cash-entry">
                    <form class="finance-entry-form" method="POST" action="{{ route('statistics.daily-cash') }}">
                        @csrf
                        <input type="hidden" name="business_date" value="<?= $today ?>">
                        <label for="petty_cash">Petty Cash</label>
                        <div class="money-input"><span>₱</span><input id="petty_cash" name="petty_cash" type="number" min="0" step="1" value="<?= (int)round($today_cash_record['petty_cash']??0) ?>" required></div>
                        <span class="finance-help">Starting money for this business day</span>
                        <button type="submit" class="finance-save-btn">Save Petty Cash</button>
                    </form>

                    <div class="expense-divider"></div>
                    <form class="finance-entry-form" method="POST" action="{{ route('statistics.expenses.store') }}">
                        @csrf
                        <input type="hidden" name="business_date" value="<?= $today ?>">
                        <label for="expense_amount">Add Expense</label>
                        <div class="money-input"><span>₱</span><input id="expense_amount" name="amount" type="number" min="1" step="1" placeholder="0" required></div>
                        <span class="finance-help">Add expenses one at a time. You can add as many as needed.</span>
                        <button type="submit" class="finance-save-btn">Add Expense</button>
                    </form>
                    <div class="expense-total-row"><span>Today's Expenses</span><strong>₱<?= number_format($today_expense_total,0) ?></strong></div>
                    <?php if (!empty($today_expenses)): ?>
                        <div class="expense-list">
                            <?php foreach ($today_expenses as $expense): ?>
                                <div class="expense-list__item"><span>Expense</span><b>₱<?= number_format($expense['amount'],0) ?></b></div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="finance-card">
                    <span class="finance-card__label">Sales</span>
                    <strong class="finance-card__total">₱<?= number_format($today_data['s'],0) ?></strong>
                    <div class="sales-split"><span>Cash <b>₱<?= number_format($today_data['cash_sales'],0) ?></b></span><span>GCash <b>₱<?= number_format($today_data['gcash_sales'],0) ?></b></span></div>
                    <span class="finance-help">Automatically computed from served orders</span>
                </div>

                <div class="finance-card finance-card--total">
                    <div class="finance-formula">Petty Cash + Sales − Expense</div>
                    <div class="finance-breakdown">₱<?= number_format($today_cash_record['petty_cash']??0,0) ?> + ₱<?= number_format($today_data['s'],0) ?> − ₱<?= number_format($today_expense_total,0) ?></div>
                    <span class="finance-card__label">Total Money</span>
                    <strong class="finance-card__total">₱<?= number_format($today_total_money,0) ?></strong>
                </div>
            </div>
            @if ($errors->any())<div class="finance-error">{{ $errors->first() }}</div>@endif
        </section>

        <div class="dash-row">
            <div class="dash-box">
                <div class="dash-box__header">
                    <img src="<?= $base_url ?>assets/images/best_icon.png" alt="" class="dash-box__header-icon">
                    <h3>Best Selling Menu (<?= $months_list[$selected_month] ?>)</h3>
                </div>
                <div class="rank-list">
                    <?php if (empty($best_items)): ?><p class="no-data">No data yet.</p>
                    <?php else: foreach ($best_items as $idx => $item):
                        $img = isset($img_map[$item['menu_item_id']]) ? $base_url.$img_map[$item['menu_item_id']] : ''; ?>
                    <div class="rank-row">
                        <span class="rank-num"><?= $idx+1 ?></span>
                        <div class="rank-img-wrap"><?php if($img): ?><img src="<?= htmlspecialchars($img) ?>" alt="" class="rank-img"><?php else: ?><div class="rank-img-placeholder"></div><?php endif; ?></div>
                        <div class="rank-info">
                            <span class="rank-name"><?= htmlspecialchars($item['name']) ?></span>
                            <span class="rank-sub"><?= $item['total_qty'] ?> orders</span>
                        </div>
                    </div>
                    <?php endforeach; endif; ?>
                </div>
            </div>

            <div class="dash-box">
                <div class="dash-box__header">
                    <img src="<?= $base_url ?>assets/images/least_icon.png" alt="" class="dash-box__header-icon">
                    <h3>Least Selling Menu (<?= $months_list[$selected_month] ?>)</h3>
                </div>
                <div class="rank-list">
                    <?php if (empty($least_items)): ?><p class="no-data">No data yet.</p>
                    <?php else: foreach ($least_items as $idx => $item):
                        $img = isset($img_map[$item['menu_item_id']]) ? $base_url.$img_map[$item['menu_item_id']] : ''; ?>
                    <div class="rank-row">
                        <span class="rank-num"><?= $idx+1 ?></span>
                        <div class="rank-img-wrap"><?php if($img): ?><img src="<?= htmlspecialchars($img) ?>" alt="" class="rank-img"><?php else: ?><div class="rank-img-placeholder"></div><?php endif; ?></div>
                        <div class="rank-info">
                            <span class="rank-name"><?= htmlspecialchars($item['name']) ?></span>
                            <span class="rank-sub"><?= $item['total_qty'] ?> orders</span>
                        </div>
                    </div>
                    <?php endforeach; endif; ?>
                </div>
            </div>

            <div class="dash-box dash-box--trend">
                <div class="dash-box__header">
                    <h3>Sales Trend</h3>
                    <select class="trend-select" onchange="switchTrend(this.value)">
                        <option value="weekly">Weekly</option>
                        <option value="monthly">Monthly</option>
                    </select>
                </div>
                <canvas id="trendChart" height="160"></canvas>
            </div>
        </div>

        <div class="dash-box dash-box--full">
            <div class="dash-box__header">
                <h3>Sales per Day</h3>
                <div class="spd-controls">
                    <select class="trend-select" id="spd-month" onchange="reloadSalesPerDay()">
                        <?php foreach ($months_list as $num => $name): ?>
                        <option value="<?= $num ?>" <?= $num==$chart_month?'selected':'' ?>><?= $name ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select class="trend-select" id="spd-year" onchange="reloadSalesPerDay()">
                        <?php foreach ($year_range as $yr): ?>
                        <option value="<?= $yr ?>" <?= $yr==$chart_year?'selected':'' ?>><?= $yr ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <canvas id="barChart" height="80"></canvas>
        </div>

        <?php endif; ?>

    </main>
</div>

<script>
    const DAILY_DATA   = <?= $daily_json ?>;
    const WEEKLY_DATA  = <?= $weekly_json ?>;
    const MONTHLY_DATA = <?= $monthly_json ?>;
    const BAR_DATA     = <?= $bar_json ?>;
    const SIDEBAR_OPEN   = <?= $sidebar_open?'true':'false' ?>;
    const SELECTED_YEAR  = <?= $selected_year ?>;
    const SELECTED_MONTH = <?= $selected_month ?>;
    const CHART_YEAR     = <?= $chart_year ?>;
    const CHART_MONTH    = <?= $chart_month ?>;

    const EXCEL_ENDPOINT = '{{ route('statistics.report') }}';

    function reloadSalesPerDay() {
        const m = document.getElementById('spd-month')?.value || CHART_MONTH;
        const y = document.getElementById('spd-year')?.value  || CHART_YEAR;
        const url = new URL(window.location.href);
        url.searchParams.set('chart_month', m);
        url.searchParams.set('chart_year',  y);
        window.location.href = url.toString();
    }
</script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/xlsx-js-style@1.2.0/dist/xlsx.bundle.js"></script>
<script src="<?= $base_url ?>modules/statistics/statistics.js"></script>
<script src="/assets/js/pwa_register.js"></script>

</body>
</html>