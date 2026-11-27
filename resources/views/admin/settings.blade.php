<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings — Twist &amp; Roll</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&family=Unbounded:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= $base_url ?>assets/index.css?v=20261015-xpad1">
    <link rel="stylesheet" href="<?= $base_url ?>modules/homepage/homepage.css?v=20261015-xpad1">
    <link rel="stylesheet" href="<?= $base_url ?>modules/settings/settings.css?v=20261015-xpad1">
    <link rel="stylesheet" href="{{ asset('assets/mobile.css?v=20261015-mobile2') }}">
</head>
<body>
<header class="navbar">
    <div class="navbar__logo-wrap">
        <a href="{{ route('home') }}" class="navbar__logo-link"><img src="<?= $base_url ?>assets/images/logo.png" class="navbar__logo-img" alt="Twist & Roll"></a>
        <span class="branch-badge"><?= htmlspecialchars($branch_name) ?></span>
    </div>
    <nav class="navbar__nav">
        <a href="{{ route('home') }}" class="nav-link">Home</a>
        <a href="{{ route('orders') }}" class="nav-link">Orders</a>
        <a href="{{ route('served') }}" class="nav-link">Served</a>
        <a href="{{ route('statistics') }}" class="nav-link">Statistics</a>
    </nav>
    <div class="navbar__right">
        <div class="navbar__datetime"><div id="current-day" class="navbar__day"></div><div id="current-date" class="navbar__date"></div></div>
        <div class="profile-menu" id="profile-menu">
            <button class="profile-btn" id="profile-btn" aria-label="Profile">
                <?php if (!empty($nav_user['avatar'])): ?><img src="<?= htmlspecialchars($nav_user['avatar']) ?>" class="profile-icon" alt="Profile" style="object-fit:cover;border-radius:50%;">
                <?php else: ?><img src="<?= $base_url ?>assets/images/profile.png" class="profile-icon" alt="Profile"><?php endif; ?>
            </button>
            <div class="profile-dropdown" id="profile-dropdown">
                <a href="{{ route('profile') }}" class="dropdown-item">Profile</a>
                <a href="{{ route('settings') }}" class="dropdown-item">Settings</a>
                <div class="dropdown-divider"></div>
                <button class="dropdown-item dropdown-item--danger" id="logout-btn" data-logout-url="{{ route('logout') }}">Logout</button>
            </div>
        </div>
    </div>
</header>

<main class="settings-page">
    <div class="settings-heading">
        <div><div class="settings-kicker">ADMIN SETTINGS</div><h1>Settings</h1><p>Customize the menu and receipt without editing code.</p></div>
        <div class="hours-card"><span>Business hours</span><strong>3:00 PM – 2:00 AM</strong><small>Business day starts at 3:00 PM</small></div>
    </div>

    @if($success_msg)<div class="settings-alert success">{{ $success_msg }}</div>@endif
    @if($error_msg)<div class="settings-alert error">{{ $error_msg }}</div>@endif
    @if($errors->any())<div class="settings-alert error">{{ $errors->first() }}</div>@endif

    <div class="settings-tabs" role="tablist">
        <button class="settings-tab active" data-tab="menu-settings" type="button">Menu Settings</button>
        <button class="settings-tab" data-tab="receipt-settings" type="button">Receipt Settings</button>
    </div>

    <section class="settings-panel active" id="menu-settings">
        <div class="panel-title-row"><div><h2>Menu Settings</h2><p>Add, edit, hide, or delete products shown on the POS.</p></div><button type="button" class="btn-primary" id="add-menu-btn">+ Add Menu Item</button></div>
        <div class="menu-settings-grid">
            @foreach($menuItems as $item)
            <article class="menu-setting-card {{ !$item->is_active ? 'is-hidden' : '' }}">
                <div class="menu-setting-image">
                    @if($item->image)<img src="{{ asset($item->image) }}" alt="{{ $item->name }}">@else<span>No image</span>@endif
                    @if(!$item->is_active)<span class="hidden-badge">Hidden</span>@endif
                </div>
                <div class="menu-setting-body">
                    <div class="menu-setting-category">{{ $item->category }}</div>
                    <h3>{{ $item->name }}</h3>
                    <div class="menu-setting-price">₱{{ number_format($item->price, 0) }} @if($item->discounted_price !== null)<small>Discounted: ₱{{ number_format($item->discounted_price, 0) }}</small>@endif</div>
                    <div class="menu-setting-actions">
                        <button type="button" class="btn-edit edit-menu-btn" data-item='@json($item)'>Edit</button>
                        <form method="POST" action="{{ route('settings.menu.destroy', $item) }}" class="delete-menu-form" data-item-name="{{ $item->name }}">@csrf @method('DELETE')<button class="btn-delete" type="submit">Delete</button></form>
                    </div>
                </div>
            </article>
            @endforeach
        </div>
        @if($menuItems->isEmpty())<div class="empty-settings">No menu items yet. Add your first item.</div>@endif
    </section>

    <section class="settings-panel" id="receipt-settings">
        <div class="panel-title-row"><div><h2>Receipt Settings</h2><p>Choose the store details and information printed on receipts.</p></div></div>
        <div class="receipt-settings-layout">
            <div class="settings-card">
                <h3>Store logo</h3>
                <div class="logo-row"><div class="logo-preview"><img src="{{ asset($receipt['logo_path'] ?: 'assets/images/logo.png') }}" alt="Store logo"></div>
                    <form method="POST" action="{{ route('settings.logo') }}" enctype="multipart/form-data" class="logo-upload-form">@csrf<input type="file" name="logo" accept="image/jpeg,image/png,image/webp" required><button class="btn-secondary" type="submit">Upload Logo</button></form></div>
            </div>
            <form method="POST" action="{{ route('settings.receipt.save') }}" class="settings-card receipt-form" id="receipt-settings-form">@csrf
                <div class="field-grid">
                    <label><span>Store Name</span><input name="store_name" value="{{ $receipt['store_name'] ?? 'Twist & Roll' }}"></label>
                    <label><span>Contact</span><input name="store_contact" value="{{ $receipt['store_contact'] ?? '' }}"></label>
                    <label class="wide"><span>Address</span><input name="store_address" value="{{ $receipt['store_address'] ?? '' }}"></label>
                    <label><span>Facebook Page</span><input name="fb_page_url" value="{{ $receipt['fb_page_url'] ?? '' }}"></label>
                    <label><span>TIN</span><input name="store_tin" value="{{ $receipt['store_tin'] ?? '' }}"></label>
                    <label class="wide"><span>Receipt Header</span><input name="receipt_header" value="{{ $receipt['receipt_header'] ?? '' }}" placeholder="Optional message above order details"></label>
                    <label class="wide"><span>Receipt Footer</span><input name="receipt_footer" value="{{ $receipt['receipt_footer'] ?? '' }}" placeholder="Thank you for dining with us!"></label>
                </div>
                <div class="receipt-options">
                    @foreach(['show_discount'=>'Show discount','show_cashier'=>'Show cashier','show_order_type'=>'Show order type','show_payment_type'=>'Show payment type','show_beeper'=>'Show beeper number'] as $key=>$label)
                    <label class="check-row"><input type="checkbox" name="{{ $key }}" value="1" {{ !empty($receipt[$key]) ? 'checked' : '' }}><span>{{ $label }}</span></label>
                    @endforeach
                </div>
                <div class="form-actions"><button class="btn-primary" type="submit">Save Receipt Settings</button></div>
            </form>
        </div>
    </section>
</main>

<div class="modal-backdrop" id="menu-modal" aria-hidden="true">
    <div class="menu-modal" role="dialog" aria-modal="true" aria-labelledby="menu-modal-title">
        <div class="modal-head"><div><h2 id="menu-modal-title">Add Menu Item</h2><p>Changes appear on the cashier menu immediately.</p></div><button type="button" class="modal-close" id="menu-modal-close">×</button></div>
        <form method="POST" action="{{ route('settings.menu.store') }}" enctype="multipart/form-data" id="menu-form">@csrf<div id="method-slot"></div>
            <div class="field-grid">
                <label class="wide"><span>Product Name</span><input name="name" id="menu-name" required maxlength="100"></label>
                <label><span>Category</span><input name="category" id="menu-category" required maxlength="50" placeholder="e.g. Sushi"></label>
                <label><span>Regular Price</span><input name="price" id="menu-price" type="number" min="0" step="0.01" required></label>
                <label><span>Discounted Price</span><input name="discounted_price" id="menu-discount" type="number" min="0" step="0.01" placeholder="Optional"></label>
                <label class="wide"><span>Menu Image</span><input name="image" id="menu-image" type="file" accept="image/jpeg,image/png,image/webp"><small>JPG, PNG or WebP, up to 3 MB.</small></label>
                <label class="check-row wide"><input type="checkbox" name="is_active" id="menu-active" value="1" checked><span>Available on cashier menu</span></label>
            </div>
            <div class="form-actions"><button type="button" class="btn-secondary" id="menu-cancel">Cancel</button><button type="submit" class="btn-primary">Save Menu Item</button></div>
        </form>
    </div>
</div>

<div class="confirm-backdrop" id="confirm-modal" aria-hidden="true">
    <div class="confirm-dialog" id="confirm-dialog" role="alertdialog" aria-modal="true" aria-labelledby="confirm-title" aria-describedby="confirm-message">
        <div class="confirm-icon" id="confirm-icon" aria-hidden="true">✓</div>
        <h2 id="confirm-title">Confirm changes</h2>
        <p id="confirm-message">Please confirm before continuing.</p>
        <div class="confirm-summary" id="confirm-summary"></div>
        <div class="confirm-actions">
            <button type="button" class="confirm-cancel" id="confirm-cancel">Cancel</button>
            <button type="button" class="confirm-proceed" id="confirm-proceed">Confirm</button>
        </div>
    </div>
</div>
<script>window.SETTINGS_MENU_STORE_URL = @json(route('settings.menu.store'));</script>
<script src="<?= $base_url ?>modules/settings/settings.js?v=20261015-xpad1"></script>
</body>
</html>
