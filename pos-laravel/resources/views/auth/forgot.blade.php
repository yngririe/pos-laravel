<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Twist & Roll — Forgot Password</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="forgot_password.css">
    <script src="{{ asset('assets/js/csrf.js') }}"></script>
</head>
<body>

<div class="card">
    <img src="../../assets/images/logo.png" class="card__logo" alt="Twist & Roll">

    <div class="card__title">Forgot Password</div>
    <p class="card__sub">Enter your email and we'll send you a reset link.</p>

    <?php if (!empty($alert)): ?>
        <div class="alert alert--<?= $alert['type'] ?>">
            <?= htmlspecialchars($alert['message']) ?>
        </div>
    <?php endif; ?>

    <form class="form" method="POST" action="{{ route('password.email') }}">
@csrf
        <div class="form-group">
            <label>Email Address</label>
            <input type="email" name="email" placeholder="Enter your email" required>
        </div>
        <button type="submit" class="btn-submit">Send Reset Link</button>
    </form>

    <a href="{{ route('login') }}" class="back-link">← Back to Login</a>
</div>

</body>
</html>