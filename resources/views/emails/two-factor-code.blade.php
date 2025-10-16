{{-- resources/views/emails/two-factor-code.blade.php --}}

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Security Code - {{ config('app.name') }}</title>
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
        .content {
            padding: 40px 30px;
        }
        .greeting {
            font-size: 18px;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 20px;
        }
        .verification-code {
            background: #f8f9fa;
            border: 2px dashed #667eea;
            border-radius: 10px;
            padding: 30px;
            text-align: center;
            margin: 30px 0;
        }
        .verification-code .label {
            font-size: 14px;
            color: #6c757d;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .verification-code .code {
            font-size: 48px;
            font-weight: bold;
            color: #667eea;
            letter-spacing: 8px;
            font-family: 'Courier New', monospace;
        }
        .security-notice {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 5px;
            padding: 20px;
            margin: 25px 0;
        }
        .security-tips {
            background: #d1ecf1;
            border: 1px solid #bee5eb;
            border-radius: 5px;
            padding: 20px;
            margin: 25px 0;
        }
        .footer {
            background: #f8f9fa;
            padding: 30px;
            text-align: center;
            border-top: 1px solid #dee2e6;
        }
        .shield-icon {
            width: 24px;
            height: 24px;
            fill: #ffc107;
            vertical-align: middle;
            margin-right: 8px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔐 Security Verification</h1>
            <p>{{ config('app.name') }} - Two-Factor Authentication</p>
        </div>
        
        <div class="content">
            <div class="greeting">Hello {{ $user_name }},</div>
            
            <p>You are receiving this email because a two-factor authentication code was requested for your account.</p>

            <div class="verification-code">
                <div class="label">Your Verification Code</div>
                <div class="code">{{ $verification_code }}</div>
            </div>

            <div class="security-notice">
                <h3>
                    <svg class="shield-icon" viewBox="0 0 24 24">
                        <path d="M12 1L3 5v6c0 5.55 3.84 9.739 9 11 5.16-1.261 9-5.45 9-11V5l-9-4z"/>
                    </svg>
                    Important Security Information
                </h3>
                <ul>
                    <li>This code will <strong>expire in 5 minutes</strong> for your security</li>
                    <li>Enter this code in the app to complete your login</li>
                    <li><strong>Do not share this code with anyone</strong></li>
                    <li>If you did not request this code, please secure your account immediately</li>
                </ul>
            </div>

            <div class="security-tips">
                <h3>🛡️ Security Tips</h3>
                <ul>
                    <li>Never share your verification codes with anyone</li>
                    <li>Our support team will <strong>never ask</strong> for your verification code</li>
                    <li>If you suspect unauthorized access, change your password immediately</li>
                    <li>Always log out from shared or public computers</li>
                </ul>
            </div>

            <p style="margin-top: 30px; padding: 15px; background: #f8f9fa; border-radius: 5px; font-size: 14px;">
                <strong>Didn't request this code?</strong><br>
                If you did not request this verification code, please ignore this email and ensure your account is secure.
                Consider changing your password if you suspect unauthorized access.
            </p>
        </div>

        <div class="footer">
            <div style="margin-bottom: 15px;">
                <strong>{{ config('app.name') }} Security Team</strong><br>
                Keeping your healthcare data safe and secure
            </div>
            
            <div style="font-size: 14px; color: #6c757d;">
                <p>Need help? Contact support: 📧 support@judyhomecare.com | 📞 (555) 123-4567</p>
                
                <p style="margin-top: 20px; font-size: 12px;">
                    This security code was sent to {{ $user_name }} for account verification.
                    Method: {{ ucfirst($method ?? 'email') }}
                </p>
            </div>
        </div>
    </div>
</body>
</html>