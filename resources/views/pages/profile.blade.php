<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile — Twist &amp; Roll</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&family=Unbounded:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= $base_url ?>assets/index.css?v=20261015-xpad1">
    <link rel="stylesheet" href="<?= $base_url ?>modules/homepage/homepage.css?v=20261015-xpad1">
    <link rel="stylesheet" href="<?= $base_url ?>modules/profile/profile.css?v=20261015-xpad1">
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#1C3924">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <link rel="apple-touch-icon" href="/assets/images/icon-192.png">
    <script src="{{ asset('assets/js/csrf.js?v=20261015-xpad1') }}"></script>
    <link rel="stylesheet" href="{{ asset('assets/mobile.css?v=20261015-mobile2') }}">
</head>
<body>

<header class="navbar">
    <a href="{{ route('home') }}" class="navbar__logo-link">
        <img src="<?= $base_url ?>assets/images/logo.png" class="navbar__logo-img" alt="Twist &amp; Roll">
    </a>
    <nav class="navbar__nav">
        <a href="{{ route('home') }}"       class="nav-link">Home</a>
        <a href="{{ route('orders') }}"     class="nav-link">Orders</a>
        <a href="{{ route('served') }}"     class="nav-link">Served</a>
        <?php if ($role === 'admin'): ?>
        <a href="{{ route('statistics') }}" class="nav-link">Statistics</a>
        <?php endif; ?>
        <a href="{{ route('profile') }}"    class="nav-link nav-link--active">Profile</a>
    </nav>
    <div class="navbar__right">
        <div class="navbar__datetime">
            <div id="current-day"  class="navbar__day"></div>
            <div id="current-date" class="navbar__date"></div>
        </div>
        <div class="profile-menu" id="profile-menu">
            <button class="profile-btn" id="profile-btn" aria-label="Profile">
                <?php if (!empty($user['avatar'])): ?>
                    <img src="<?= htmlspecialchars($user['avatar']) ?>" class="profile-icon" alt="Profile" style="object-fit:cover;border-radius:50%;">
                <?php else: ?>
                    <img src="<?= $base_url ?>assets/images/profile.png" class="profile-icon" alt="Profile">
                <?php endif; ?>
            </button>
            <div class="profile-dropdown" id="profile-dropdown">
                <a href="{{ route('profile') }}" class="dropdown-item">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                        <circle cx="12" cy="7" r="4"/>
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
                <div class="dropdown-divider"></div>
                <button class="dropdown-item dropdown-item--danger" id="logout-btn" data-logout-url="{{ route('logout') }}">
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

<div class="profile-page">

    <div class="profile-breadcrumb">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
            <circle cx="12" cy="7" r="4"/>
        </svg>
        <a href="{{ route('home') }}" class="profile-breadcrumb__link">Home</a>
        <span class="profile-breadcrumb__sep">›</span>
        <span class="profile-breadcrumb__current">Profile</span>
    </div>

    <h1 class="profile-page__title">My Profile</h1>
    <p class="profile-page__sub">Manage your account information and password security.</p>

    <div class="toast-container" id="toast-container">
        <?php if ($success_msg): ?>
        <div class="profile-alert profile-alert--success">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                <polyline points="22 4 12 14.01 9 11.01"/>
            </svg>
            <span><?= htmlspecialchars($success_msg) ?></span>
        </div>
        <?php endif; ?>
        <?php if ($error_msg): ?>
        <div class="profile-alert profile-alert--error">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"/>
                <line x1="12" y1="8" x2="12" y2="12"/>
                <line x1="12" y1="16" x2="12.01" y2="16"/>
            </svg>
            <span><?= htmlspecialchars($error_msg) ?></span>
        </div>
        <?php endif; ?>
    </div>

    <div class="profile-layout">

        <!-- ── LEFT: Avatar card ── -->
        <div class="profile-card profile-card--avatar">
            <div class="avatar-wrap">
                <?php if (!empty($user['avatar'])): ?>
                    <img src="<?= htmlspecialchars($user['avatar']) ?>" class="avatar-img" alt="Avatar">
                <?php else: ?>
                    <div class="avatar-placeholder">
                        <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                            <circle cx="12" cy="7" r="4"/>
                        </svg>
                    </div>
                <?php endif; ?>
                <button class="avatar-cam-btn" id="avatar-cam-btn" title="Change photo">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/>
                        <circle cx="12" cy="13" r="4"/>
                    </svg>
                </button>
                <form id="avatar-form" method="POST" enctype="multipart/form-data" style="display:none;">
@csrf
                    <input type="hidden" name="action" value="upload_avatar">
                    <input type="file" id="avatar-input" name="avatar" accept="image/*">
                </form>
            </div>

            <div class="avatar-name"><?= htmlspecialchars($user['full_name']) ?></div>

            <button class="profile-btn-primary" id="open-edit-btn">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                </svg>
                Edit Profile
            </button>
            <button class="profile-btn-secondary" id="open-pw-btn">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                    <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                </svg>
                Change Password
            </button>

        </div>

        <!-- ── RIGHT: Panels ── -->
        <div class="profile-right">

            <!-- ── Account Information ── -->
            <div class="profile-card" id="account-card">
                <div class="profile-card__header">
                    <div class="profile-card__header-left">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                            <circle cx="12" cy="7" r="4"/>
                        </svg>
                        <span>Account Information</span>
                    </div>
                    <button class="profile-edit-btn" id="inline-edit-btn">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                        </svg>
                        Edit
                    </button>
                </div>

                <!-- VIEW MODE -->
                <div id="account-view">
                    <div class="profile-fields">
                        <div class="profile-field">
                            <label class="profile-field__label">Full Name</label>
                            <div class="profile-field__value"><?= htmlspecialchars($user['full_name']) ?></div>
                        </div>
                        <div class="profile-field">
                            <label class="profile-field__label">Phone Number</label>
                            <div class="profile-field__value"><?= htmlspecialchars($user['phone'] ?? '') ?></div>
                        </div>
                        <div class="profile-field">
                            <label class="profile-field__label">Username</label>
                            <div class="profile-field__value"><?= htmlspecialchars($user['username']) ?></div>
                        </div>
                        <div class="profile-field">
                            <label class="profile-field__label">Email</label>
                            <div class="profile-field__value"><?= htmlspecialchars($user['email']) ?></div>
                        </div>
                        <div class="profile-field">
                            <label class="profile-field__label">Status</label>
                            <div class="profile-field__value">
                                <span class="profile-status profile-status--<?= strtolower($user['status'] ?? 'active') ?>">
                                    ● <?= ucfirst($user['status'] ?? 'Active') ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- EDIT MODE -->
                <form id="account-edit" method="POST" style="display:none;">
@csrf
                    <input type="hidden" name="action" value="edit_profile">
                    <div class="profile-fields">
                        <div class="profile-field">
                            <label class="profile-field__label">Full Name</label>
                            <input class="profile-input" type="text" name="full_name" value="<?= htmlspecialchars($user['full_name']) ?>" required>
                        </div>
                        <div class="profile-field">
                            <label class="profile-field__label">Phone Number</label>
                            <input class="profile-input" type="text" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                        </div>
                        <div class="profile-field">
                            <label class="profile-field__label">Username</label>
                            <input class="profile-input" type="text" name="username" value="<?= htmlspecialchars($user['username']) ?>" required>
                        </div>
                        <div class="profile-field">
                            <label class="profile-field__label">Email</label>
                            <input class="profile-input" type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
                        </div>
                        <div class="profile-field">
                            <label class="profile-field__label">Status</label>
                            <div class="profile-field__value">
                                <span class="profile-status profile-status--<?= strtolower($user['status'] ?? 'active') ?>">
                                    ● <?= ucfirst($user['status'] ?? 'Active') ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="profile-form-actions">
                        <button type="button" class="profile-btn-secondary" id="cancel-edit-btn">Cancel</button>
                        <button type="submit" class="profile-btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>

            <!-- ── Security Settings ── -->
            <div class="profile-card" id="security-card">
                <div class="profile-card__header">
                    <div class="profile-card__header-left">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                        </svg>
                        <span>Security Settings</span>
                    </div>
                </div>

                <form id="password-form" method="POST">
@csrf
                    <input type="hidden" name="action" value="change_password">
                    <div class="profile-fields profile-fields--3col">
                        <div class="profile-field">
                            <label class="profile-field__label">Current Password</label>
                            <div class="profile-pw-wrap">
                                <input class="profile-input" type="password" name="current_password" id="pw-current" placeholder="Enter current password">
                                <button type="button" class="pw-toggle" data-target="pw-current">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                </button>
                            </div>
                        </div>
                        <div class="profile-field">
                            <label class="profile-field__label">New Password</label>
                            <div class="profile-pw-wrap">
                                <input class="profile-input" type="password" name="new_password" id="pw-new" placeholder="Enter new password" autocomplete="new-password">
                                <button type="button" class="pw-toggle" data-target="pw-new">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                </button>
                            </div>
                            <div class="pw-requirements" id="pw-requirements">
                                <div class="pw-req" id="req-length"><span class="pw-req__icon">✗</span><span>At least 8 characters</span></div>
                                <div class="pw-req" id="req-upper"><span class="pw-req__icon">✗</span><span>At least 1 uppercase letter (A–Z)</span></div>
                                <div class="pw-req" id="req-number"><span class="pw-req__icon">✗</span><span>At least 1 number (0–9)</span></div>
                                <div class="pw-req" id="req-special"><span class="pw-req__icon">✗</span><span>At least 1 special character (!@#$...)</span></div>
                            </div>
                        </div>
                        <div class="profile-field">
                            <label class="profile-field__label">Confirm New Password</label>
                            <div class="profile-pw-wrap">
                                <input class="profile-input" type="password" name="confirm_password" id="pw-confirm" placeholder="Confirm new password" autocomplete="new-password">
                                <button type="button" class="pw-toggle" data-target="pw-confirm">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                </button>
                            </div>
                            <div class="pw-match" id="pw-match" style="display:none;"></div>
                        </div>
                    </div>
                    <div class="profile-form-actions profile-form-actions--right">
                        <button type="submit" class="profile-btn-gold" id="pw-submit-btn">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                                <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                            </svg>
                            Update Password
                        </button>
                    </div>
                </form>
            </div>

            <!-- ── Danger Zone ── -->
            <div class="profile-card profile-card--danger">
                <div class="profile-card__header">
                    <div class="profile-card__header-left">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                        </svg>
                        <span>Danger Zone</span>
                    </div>
                </div>
                <div class="danger-row">
                    <p class="danger-desc">Logout from your account on this device.</p>
                    <button class="profile-btn-danger" id="danger-logout-btn" data-logout-url="{{ route('logout') }}">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                            <polyline points="16 17 21 12 16 7"/>
                            <line x1="21" y1="12" x2="9" y2="12"/>
                        </svg>
                        Logout
                    </button>
                </div>
            </div>

        </div><!-- /.profile-right -->
    </div><!-- /.profile-layout -->
</div><!-- /.profile-page -->

<script src="<?= $base_url ?>modules/profile/profile.js?v=20261015-xpad1"></script>
<script src="/assets/js/pwa_register.js?v=20261015-xpad1"></script>
<?php if ($role === 'cashier'): ?>
<script>window.ORDER_NOTIFICATIONS_URL = @json(route('orders.notifications')); window.ORDERS_PAGE_URL = @json(route('orders'));</script>
<script src="{{ asset('assets/js/order-notifications.js?v=20261015-bell1') }}"></script>
<?php endif; ?>
</body>
</html>