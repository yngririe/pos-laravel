<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Twist &amp; Roll — Create New Branch</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=DM+Serif+Display&display=swap" rel="stylesheet">
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
body {
    font-family: 'DM Sans', sans-serif;
    background: #FEFCE0;
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
}
.card {
    background: #FEFCE0;
    border: 1px solid #e0ddce;
    border-radius: 40px;
    padding: 40px 36px;
    width: 100%;
    max-width: 400px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.05), 0 0 50px rgba(216,195,111,0.5);
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 20px;
}
.logo { max-width: 160px; height: auto; }
.card-title { font-size: 20px; font-weight: 700; color: #1C3924; text-align: center; margin-bottom: -6px; }
.card-sub { font-size: 13px; color: #7A7A5A; text-align: center; margin-top: -10px; }
.form { width: 100%; display: flex; flex-direction: column; gap: 13px; }
.form-group { display: flex; flex-direction: column; gap: 5px; }
label { font-size: 13px; color: #7A7A5A; font-weight: 500; }
input[type="text"], input[type="email"], input[type="password"] {
    width: 100%; padding: 10px 14px; border: 1px solid #dbd8c8; border-radius: 8px;
    font-size: 14px; color: #2C2C1A; background: #F3F2D7; outline: none;
    font-family: 'DM Sans', sans-serif; transition: border-color 0.15s;
}
input:focus { border-color: #C8A84B; }
.btn-submit {
    width: 100%; padding: 12px; background: #1C3924; color: #fff; border: none;
    border-radius: 20px; font-size: 14px; font-weight: 600; cursor: pointer;
    font-family: 'DM Sans', sans-serif; transition: background 0.15s; margin-top: 4px;
}
.btn-submit:hover { background: #245a42; }
.alert { width: 100%; padding: 10px 14px; border-radius: 8px; font-size: 13px; font-weight: 500; }
.alert--error   { background: #fde8e8; color: #C0392B; border: 1px solid #f5b7b1; }
.alert--success { background: #e8f4ec; color: #1c6b38; border: 1px solid #b0ddbf; }
.warn { font-size: 12px; color: #7A7A5A; text-align: center; }
</style>
    <script src="{{ asset('assets/js/csrf.js') }}"></script>
</head>
<body>

<div class="card">
    <img src="{{ asset('assets/images/logo.png') }}" class="logo" alt="Twist &amp; Roll">
    <div class="card-title">Create New Branch</div>
    <div class="card-sub">Sets up a new branch with its own owner account. Requires the setup key.</div>

    @if($disabled)
    <div class="alert alert--error">This page is disabled. Set BRANCH_SETUP_KEY in your .env file to enable it.</div>
    @endif

    <?php if ($error): ?>
    <div class="alert alert--error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
    <div class="alert alert--success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <form class="form" method="POST" action="{{ route('branches.store') }}">
@csrf
        <div class="form-group">
            <label>Setup Key *</label>
            <input type="password" name="setup_key" placeholder="Enter the setup key" required>
        </div>
        <div class="form-group">
            <label>Branch Name *</label>
            <input type="text" name="branch_name" placeholder="e.g. Twist & Roll — Main Branch" required>
        </div>
        <div class="form-group">
            <label>Owner Full Name *</label>
            <input type="text" name="full_name" placeholder="e.g. Juan dela Cruz" required>
        </div>
        <div class="form-group">
            <label>Username *</label>
            <input type="text" name="username" placeholder="e.g. admin" required>
        </div>
        <div class="form-group">
            <label>Email *</label>
            <input type="email" name="email" placeholder="e.g. owner@twistandroll.com" required>
        </div>
        <div class="form-group">
            <label>Password *</label>
            <input type="password" name="password" placeholder="Create a strong password" required>
        </div>
        <div class="form-group">
            <label>Confirm Password *</label>
            <input type="password" name="confirm_password" placeholder="Re-enter password" required>
        </div>
        <button type="submit" class="btn-submit">Create Branch Admin Account</button>
    </form>
</div>
</body>
</html>
