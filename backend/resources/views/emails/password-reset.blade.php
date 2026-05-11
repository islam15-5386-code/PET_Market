<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Your Password</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #f5f5f5; margin: 0; padding: 40px 20px; color: #333; }
        .container { max-width: 520px; margin: 0 auto; background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,.08); }
        .header { background: #1a1a2e; padding: 32px; text-align: center; }
        .header h1 { color: #fff; margin: 0; font-size: 22px; font-weight: 700; }
        .header p { color: #94a3b8; margin: 6px 0 0; font-size: 14px; }
        .body { padding: 40px 36px; }
        .body p { color: #555; line-height: 1.7; margin: 0 0 20px; }
        .btn { display: inline-block; background: #e8701a; color: #fff !important; text-decoration: none; padding: 14px 32px; border-radius: 8px; font-weight: 600; font-size: 15px; margin: 8px 0 24px; }
        .note { background: #fef9ec; border: 1px solid #fde68a; border-radius: 8px; padding: 14px 18px; font-size: 13px; color: #92400e; }
        .url-fallback { word-break: break-all; font-size: 12px; color: #888; margin-top: 16px; }
        .footer { border-top: 1px solid #eee; padding: 20px 36px; font-size: 12px; color: #999; text-align: center; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🐾 Pet Marketplace</h1>
            <p>Password Reset Request</p>
        </div>
        <div class="body">
            <p>Hi there,</p>
            <p>We received a request to reset your Pet Marketplace password. Click the button below to set a new password:</p>
            <a href="{{ $resetUrl }}" class="btn">Reset My Password</a>
            <div class="note">
                ⏱ This link expires in <strong>{{ $expiresIn }}</strong>. If you didn't request this, you can safely ignore this email.
            </div>
            <p class="url-fallback">If the button doesn't work:<br>{{ $resetUrl }}</p>
        </div>
        <div class="footer">
            &copy; {{ date('Y') }} Pet Marketplace by Betopia Limited.
        </div>
    </div>
</body>
</html>
