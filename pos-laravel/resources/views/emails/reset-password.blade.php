<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; background: #f9f9f9; margin: 0; padding: 0; }
        .wrapper { max-width: 600px; margin: 40px auto; background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.08); }
        .header { background: #FEFCE0; padding: 0; text-align: center; border-bottom: 1px solid #e8e4cc; overflow: hidden; }
        .header img { width: 100% !important; max-width: 100% !important; display: block; }
        .body { padding: 36px 40px; color: #2C2C1A; }
        .body p { font-size: 15px; line-height: 1.7; margin-bottom: 16px; color: #3a3a2a; }
        .btn-wrap { text-align: center; margin: 28px 0; }
        .btn { display: inline-block; padding: 14px 36px; background: #1C3924; color: #ffffff !important; text-decoration: none; border-radius: 24px; font-size: 15px; font-weight: 600; }
        .link-fallback { font-size: 13px; color: #7A7A5A; word-break: break-all; }
        .link-fallback a { color: #C8A84B; }
        .footer { background: #1C3924; padding: 20px; text-align: center; }
        .footer p { color: #a8c5b0; font-size: 12px; margin: 4px 0; }
        .divider { border: none; border-top: 1px solid #e8e4cc; margin: 24px 0; }
        .note { background: #f5f5f0; border-radius: 8px; padding: 14px 18px; font-size: 13px; color: #7A7A5A; margin-top: 8px; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="header">
            <img src="https://i.ibb.co/mrcYP2y8/POS-banner.png" alt="Twist &amp; Roll" style="width:100%; height:auto; display:block;">
        </div>
        <div class="body">
            <p>Hi <strong>{{ $fullName }}</strong>,</p>
            <p>You requested to reset your password for your <strong>Twist &amp; Roll</strong> account.</p>
            <p>Click the button below to reset your password. The link is valid for 1 hour.</p>

            <div class="btn-wrap">
                <a href="{{ $resetLink }}" class="btn">Reset your password</a>
            </div>

            <p class="link-fallback">If the button does not work, copy and paste this link into your browser:<br>
                <a href="{{ $resetLink }}">{{ $resetLink }}</a>
            </p>

            <p>If you did not request this, you can ignore this email.</p>

            <hr class="divider">

            <div class="note">
                <p>This is a system generated message. Do not reply.</p>
                <p>This message is intended to <strong>{{ $email }}</strong> upon request.</p>
            </div>
        </div>
        <div class="footer">
            <p>&copy; 2026 Twist &amp; Roll. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
