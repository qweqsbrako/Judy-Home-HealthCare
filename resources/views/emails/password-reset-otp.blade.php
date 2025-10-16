<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset Verification Code</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 0;
            background-color: #f4f4f7;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 30px 20px;
            text-align: center;
        }
        .header h1 {
            color: #ffffff;
            margin: 0;
            font-size: 24px;
        }
        .content {
            padding: 40px 30px;
        }
        .greeting {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 20px;
            color: #1f2937;
        }
        .message {
            margin-bottom: 20px;
            color: #4b5563;
            font-size: 15px;
        }
        .otp-container {
            background-color: #f9fafb;
            border: 2px dashed #d1d5db;
            border-radius: 12px;
            padding: 30px;
            text-align: center;
            margin: 30px 0;
        }
        .otp-label {
            font-size: 14px;
            color: #6b7280;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .otp-code {
            font-size: 36px;
            font-weight: 700;
            color: #667eea;
            letter-spacing: 8px;
            font-family: 'Courier New', monospace;
        }
        .expiry-info {
            background-color: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 15px 20px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .expiry-info p {
            margin: 0;
            color: #92400e;
            font-size: 14px;
        }
        .warning {
            background-color: #fee2e2;
            border-left: 4px solid #ef4444;
            padding: 15px 20px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .warning p {
            margin: 0;
            color: #991b1b;
            font-size: 14px;
        }
        .footer {
            background-color: #f9fafb;
            padding: 30px;
            text-align: center;
            border-top: 1px solid #e5e7eb;
        }
        .footer p {
            margin: 5px 0;
            font-size: 13px;
            color: #6b7280;
        }
        .app-name {
            color: #667eea;
            font-weight: 600;
        }
        @media only screen and (max-width: 600px) {
            .content {
                padding: 30px 20px;
            }
            .otp-code {
                font-size: 28px;
                letter-spacing: 4px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Password Reset Verification</h1>
        </div>
        
        <div class="content">
            <div class="greeting">Hello {{ $user_name }}!</div>
            
            <div class="message">
                <p>We received a request to reset your password. To proceed with resetting your password, please use the verification code below:</p>
            </div>
            
            <div class="otp-container">
                <div class="otp-label">Your Verification Code</div>
                <div class="otp-code">{{ $otp }}</div>
            </div>
            
            <div class="expiry-info">
                <p><strong>⏱️ Valid for {{ $expires_in }} minutes</strong></p>
                <p>This code will expire at {{ $expires_at }}</p>
            </div>
            
            <div class="message">
                <p>Enter this code in the app to verify your identity and set a new password.</p>
            </div>
            
            <div class="warning">
                <p><strong>⚠️ Security Notice:</strong> If you didn't request this password reset, please ignore this email. Your account remains secure.</p>
            </div>
            
            <div class="message">
                <p>For your security:</p>
                <ul style="color: #6b7280; font-size: 14px;">
                    <li>Never share this code with anyone</li>
                    <li>Our team will never ask for this code</li>
                    <li>If you're suspicious, contact support immediately</li>
                </ul>
            </div>
        </div>
        
        <div class="footer">
            <p>This is an automated email from <span class="app-name">{{ $app_name }}</span></p>
            <p>© {{ date('Y') }} {{ $app_name }}. All rights reserved.</p>
        </div>
    </div>
</body>
</html>