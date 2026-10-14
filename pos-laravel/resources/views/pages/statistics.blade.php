<!DOCTYPE html>
<html lang="en" data-theme="<?= $dark_mode ? 'dark' : 'light' ?>">
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
                <h3 class="section-chart-title">Orders per Day</h3>
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
                            $gcashRef      = $o['gcash_reference']            ?? '';
                            $gcashExtra    = $o['gcash_reference_extra']      ?? '';
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
                            data-gcash-ref="<?= htmlspecialchars($gcashRef, ENT_QUOTES, 'UTF-8') ?>"
                            data-gcash-ref-extra="<?= htmlspecialchars($gcashExtra, ENT_QUOTES, 'UTF-8') ?>"
                            data-gcash-extra-amount="<?= $gcashExtraAmt ?>"
                        >
                            <td><?= (int)$o['beeper_number'] ?></td>
                            <td><?= $display_dt ?></td>
                            <td><?= $o['order_type']==='dine-in'?'Dine in':'Take out' ?></td>
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

        <section class="overview-section">
            <p class="overview-label">Daily Sales Overview</p>
            <div class="overview-cards">
                <div class="overview-card">
                    <div class="ov-icon-wrap"><img src="<?= $base_url ?>assets/images/sales_icon.png" alt="" class="ov-icon-img"></div>
                    <div class="ov-text">
                        <div class="ov-label">Total Sales per Day</div>
                        <div class="ov-value">₱<?= number_format($today_data['s'],0) ?></div>
                        <div class="ov-sub"><?= date('M j, Y', strtotime($today)) ?> (Business Day)</div>
                    </div>
                </div>
                <div class="overview-card">
                    <div class="ov-icon-wrap"><img src="<?= $base_url ?>assets/images/orders_icon.png" alt="" class="ov-icon-img"></div>
                    <div class="ov-text">
                        <div class="ov-label">Number of Orders per Day</div>
                        <div class="ov-value"><?= $today_data['c'] ?></div>
                        <div class="ov-sub"><?= date('M j, Y', strtotime($today)) ?> (Business Day)</div>
                    </div>
                </div>
                <div class="overview-card">
                    <div class="ov-icon-wrap"><img src="<?= $base_url ?>assets/images/monthsales_icon.png" alt="" class="ov-icon-img"></div>
                    <div class="ov-text">
                        <div class="ov-label">Total Sales this Month</div>
                        <div class="ov-value">₱<?= number_format($monthly['total_sales'],0) ?></div>
                        <div class="ov-sub"><?= $months_list[$selected_month] ?> <?= $selected_year ?></div>
                    </div>
                </div>
            </div>
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