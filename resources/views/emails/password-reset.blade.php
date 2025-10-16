{{-- resources/views/emails/password-reset.blade.php --}}

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Your Password - {{ config('app.name') }}</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            background-color: #f4f4f4;
            padding: 20px;
        }
        .container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);;
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }
        .header .subtitle {
            margin: 10px 0 0 0;
            opacity: 0.9;
            font-size: 16px;
        }
        .content {
            padding: 40px 30px;
        }
        .greeting {
            font-size: 18px;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 20px;
        }
        .message {
            margin-bottom: 20px;
            line-height: 1.6;
        }
        .security-notice {
            background: #e8f4fd;
            border-left: 4px solid #3498db;
            padding: 15px 20px;
            margin: 25px 0;
            border-radius: 5px;
        }
        .security-notice h3 {
            margin: 0 0 10px 0;
            color: #2980b9;
            font-size: 16px;
        }
        .security-notice ul {
            margin: 0;
            padding-left: 20px;
        }
        .security-notice li {
            margin-bottom: 5px;
        }
        .cta-button {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);;
            color: white !important;
            padding: 15px 30px;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 16px;
            margin: 20px 0;
            transition: transform 0.2s;
        }
        .cta-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        .fallback-url {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            font-size: 14px;
            word-break: break-all;
        }
        .expiration-notice {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 5px;
            padding: 15px;
            margin: 20px 0;
            text-align: center;
        }
        .expiration-notice .time {
            font-weight: bold;
            color: #856404;
            font-size: 18px;
        }
        .footer {
            background: #f8f9fa;
            padding: 30px;
            text-align: center;
            border-top: 1px solid #dee2e6;
        }
        .footer .company-info {
            margin-bottom: 15px;
        }
        .footer .company-name {
            font-weight: 600;
            color: #2c3e50;
        }
        .footer .support-info {
            font-size: 14px;
            color: #6c757d;
            margin-top: 15px;
        }
        .logo {
            width: 60px;
            height: 60px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 15px;
        }
        .medical-icon {
            width: 30px;
            height: 30px;
            fill: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">
                <svg class="medical-icon" viewBox="0 0 24 24">
                    <path d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
            </div>
            <h1>{{ config('app.name') }}</h1>
            <p class="subtitle">Professional HomeCare Portal</p>
        </div>
        
        <div class="content">
            <div class="greeting">Hello {{ $user_name }},</div>
            
            <div class="message">
                <p>You are receiving this email because we received a password reset request for your {{ config('app.name') }} account.</p>
            </div>

            <div class="security-notice">
                <h3>🔒 Important Security Information</h3>
                <ul>
                    <li>This link will expire at <strong>{{ $expires_at }}</strong> for your security</li>
                    <li>If you did not request a password reset, please ignore this email</li>
                    <li>Your account remains secure and no changes have been made</li>
                </ul>
            </div>

            <div style="text-align: center;">
                <a href="{{ $reset_url }}" class="cta-button">
                    🔑 Reset Password
                </a>
            </div>

            <div class="expiration-notice">
                ⏰ This link expires in <span class="time">15 minutes</span>
            </div>

            <div class="message">
                <p><strong>Having trouble clicking the button?</strong><br>
                Copy and paste the URL below into your web browser:</p>
            </div>

            <div class="fallback-url">
                {{ $reset_url }}
            </div>

            <div class="security-notice">
                <h3>🛡️ Security Notice</h3>
                <ul>
                    <li>For your protection, this reset link can only be used once</li>
                    <li>Never share your reset link with anyone</li>
                    <li>Our support team will never ask for your password or reset link</li>
                    <li>If you continue to have problems, please contact our support team</li>
                </ul>
            </div>
        </div>

        <div class="footer">
            <div class="company-info">
                <div class="company-name">{{ config('app.name') }}</div>
                <div>Compassionate Care at Home</div>
            </div>
            
            <div class="support-info">
                <p>Need help? Contact our support team:<br>
                📧 support@judyhomecare.com | 📞 (555) 123-4567</p>
                
                <p style="margin-top: 20px; font-size: 12px; color: #adb5bd;">
                    This email was sent to {{ $user_name }} because a password reset was requested for your account.
                    If you did not request this, please ignore this email.
                </p>
            </div>
        </div>
    </div>
</body>
</html>