<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Twist & Roll — Forgot Password</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('modules/forgot-password/forgot_password.css?v=20261015-authfix2') }}">
    <style>
        /* Safe fallback: keeps auth pages usable even if the external stylesheet is delayed. */
        html, body { min-height: 100%; }
        body {
            margin: 0;
            min-height: 100vh;
            min-height: 100dvh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #FEFCE0;
            font-family: 'DM Sans', sans-serif;
            padding: 24px;
        }
        .card__logo { display: block; width: auto; max-width: 160px; height: auto; }
    </style>
    <script src="{{ asset('assets/js/csrf.js?v=20261015-ui2') }}"></script>
    <link rel="stylesheet" href="{{ asset('assets/mobile.css?v=20261015-mobile2') }}">
</head>
<body>

<div class="card">
    <img src="{{ asset('assets/images/logo.png?v=20261015-authfix2') }}" class="card__logo" alt="Twist & Roll">

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