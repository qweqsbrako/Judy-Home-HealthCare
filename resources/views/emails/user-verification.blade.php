<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Verified</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 0;
            background-color: #f8f9fa;
        }
        .email-container {
            background-color: #ffffff;
            margin: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }
        .content {
            padding: 30px;
        }
        .success-icon {
            width: 60px;
            height: 60px;
            background: #10b981;
            border-radius: 50%;
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        h2 {
            color: #1f2937;
            font-size: 20px;
            margin-bottom: 10px;
            text-align: center;
        }
        .user-info {
            background-color: #f3f4f6;
            padding: 20px;
            border-radius: 6px;
            margin: 20px 0;
        }
        .user-info h3 {
            margin: 0 0 10px 0;
            color: #374151;
            font-size: 16px;
        }
        .user-info p {
            margin: 5px 0;
            color: #6b7280;
        }
        .cta-button {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white !important;
            text-decoration: none;
            padding: 12px 30px;
            border-radius: 6px;
            font-weight: 600;
            text-align: center;
            margin: 20px auto;
            display: block;
            width: fit-content;
        }
        .cta-button:hover {
            opacity: 0.9;
        }
        .footer {
            background-color: #f8f9fa;
            padding: 20px 30px;
            text-align: center;
            font-size: 14px;
            color: #6b7280;
            border-top: 1px solid #e5e7eb;
        }
        .contact-info {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #e5e7eb;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <h1>Judy Home Care Portal</h1>
        </div>
        
        <div class="content">
            <div class="success-icon">
                <i class="fas fa-check" style="color: white !important; font-size: 24px;"></i>
            </div>

            
            <h2>Account Verified Successfully!</h2>
            
            <p>Dear {{ $user->first_name }} {{ $user->last_name }},</p>
            
            <p>Congratulations! Your {{ $role }} account has been successfully verified and approved. You can now access the Judy Home Care Portal and begin using our platform.</p>
            
            <div class="user-info">
                <h3>Account Details</h3>
                <p><strong>Name:</strong> {{ $user->first_name }} {{ $user->last_name }}</p>
                <p><strong>Email:</strong> {{ $user->email }}</p>
                <p><strong>Role:</strong> {{ $role }}</p>
                @if($user->license_number)
                <p><strong>License Number:</strong> {{ $user->license_number }}</p>
                @endif
                <p><strong>Verification Date:</strong> {{ now()->format('F j, Y \a\t g:i A') }}</p>
            </div>
            
            @if($verificationNotes)
            <div class="user-info">
                <h3>Verification Notes</h3>
                <p>{{ $verificationNotes }}</p>
            </div>
            @endif
            
            <p>You can now log in to your account using the credentials you created during registration.</p>
            
            <a href="{{ $loginUrl }}" class="cta-button">
                Access Your Account
            </a>
            
            <p><strong>Next Steps:</strong></p>
            <ul>
                @if($user->role === 'patient')
                <li>Complete your medical profile</li>
                <li>Browse available care services</li>
                <li>Schedule your first consultation</li>
                @elseif($user->role === 'nurse')
                <li>Complete your professional profile</li>
                <li>Review available care assignments</li>
                <li>Update your availability schedule</li>
                @elseif($user->role === 'doctor')
                <li>Set up your practice profile</li>
                <li>Configure patient consultation preferences</li>
                <li>Review pending care plans</li>
                @endif
            </ul>
            
            <p>If you have any questions or need assistance getting started, please don't hesitate to contact our support team.</p>
        </div>
        
        <div class="footer">
            <p><strong>Judy Home Care Portal</strong><br>
            Quality Healthcare at Home</p>
            
            <div class="contact-info">
                <p>📧 Email: <a href="mailto:{{ $supportEmail }}">{{ $supportEmail }}</a></p>
                <p>🌐 Web: <a href="{{ config('app.url') }}">{{ config('app.url') }}</a></p>
                <p>This email was sent automatically. Please do not reply to this email.</p>
            </div>
        </div>
    </div>
</body>
</html>