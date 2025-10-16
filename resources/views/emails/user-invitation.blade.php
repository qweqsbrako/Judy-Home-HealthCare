<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to {{ $companyName }}</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f8f9fa;
        }
        .email-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
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
            font-size: 28px;
            font-weight: 700;
        }
        .header p {
            margin: 10px 0 0 0;
            opacity: 0.9;
            font-size: 16px;
        }
        .content {
            padding: 40px 30px;
        }
        .greeting {
            font-size: 18px;
            color: #2d3748;
            margin-bottom: 20px;
        }
        .role-badge {
            display: inline-block;
            background: #667eea;
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .credentials-box {
            background: #f7fafc;
            border: 2px dashed #cbd5e0;
            border-radius: 8px;
            padding: 25px;
            margin: 25px 0;
            text-align: center;
        }
        .credentials-box h3 {
            margin: 0 0 15px 0;
            color: #2d3748;
            font-size: 16px;
        }
        .credential-item {
            margin: 10px 0;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-wrap: wrap;
            gap: 10px;
        }
        .credential-label {
            font-weight: 600;
            color: #4a5568;
            min-width: 80px;
        }
        .credential-value {
            background: white;
            border: 1px solid #e2e8f0;
            padding: 8px 12px;
            border-radius: 6px;
            font-family: 'Courier New', monospace;
            font-weight: 600;
            color: #2d3748;
        }
        .login-button {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white !important;
            text-decoration: none;
            padding: 15px 30px;
            border-radius: 8px;
            font-weight: 600;
            margin: 25px 0;
            text-align: center;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
            transition: transform 0.2s ease;
        }
        .login-button:hover {
            transform: translateY(-2px);
        }
        .warning-box {
            background: #fef5e7;
            border-left: 4px solid #f6ad55;
            padding: 15px;
            margin: 25px 0;
            border-radius: 4px;
        }
        .warning-box p {
            margin: 0;
            color: #744210;
            font-size: 14px;
        }
        .steps {
            margin: 25px 0;
        }
        .step {
            display: flex;
            align-items: flex-start;
            margin: 15px 0;
            padding: 15px;
            background: #f7fafc;
            border-radius: 8px;
            border-left: 4px solid #667eea;
        }
        .step-number {
            background: #667eea;
            color: white;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 12px;
            margin-right: 15px;
            flex-shrink: 0;
        }
        .step-content {
            flex: 1;
        }
        .step-title {
            font-weight: 600;
            color: #2d3748;
            margin: 0 0 5px 0;
        }
        .step-description {
            color: #4a5568;
            margin: 0;
            font-size: 14px;
        }
        .footer {
            background: #f7fafc;
            padding: 30px;
            text-align: center;
            border-top: 1px solid #e2e8f0;
        }
        .footer p {
            margin: 5px 0;
            color: #718096;
            font-size: 14px;
        }
        .support-link {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }
        .support-link:hover {
            text-decoration: underline;
        }
        @media (max-width: 600px) {
            body { padding: 10px; }
            .content { padding: 30px 20px; }
            .header { padding: 25px 20px; }
            .credential-item { flex-direction: column; }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <h1>Welcome to {{ $companyName }}</h1>
            <p>Your homecare management account is ready</p>
        </div>
        
        <div class="content">
            <div class="greeting">
                Hello <strong>{{ $user->first_name }} {{ $user->last_name }}</strong>,
            </div>
            
            <p>We're excited to welcome you to {{ $companyName }} as a <span class="role-badge">{{ ucfirst($user->role) }}</span>!</p>
            
            <p>Your account has been created and you can now access the homecare management platform. To get started, please use the temporary login credentials below:</p>
            
            <div class="credentials-box">
                <h3>🔐 Your Login Credentials</h3>
                <div class="credential-item">
                    <span class="credential-label">Email:</span>
                    <span class="credential-value">{{ $user->email }}</span>
                </div>
                <div class="credential-item">
                    <span class="credential-label">Password:</span>
                    <span class="credential-value">{{ $temporaryPassword }}</span>
                </div>
            </div>
            
            <div class="warning-box">
                <p><strong>Important:</strong> This is a temporary password. You'll be required to change it when you first log in for security purposes.</p>
            </div>
            
            <div style="text-align: center;">
                <a href="{{ $loginUrl }}" class="login-button">Login to Your Account</a>
            </div>
            
            <div class="steps">
                <h3 style="color: #2d3748; margin-bottom: 20px;">Getting Started - Next Steps:</h3>
                
                <div class="step">
                    
                    <div class="step-content">
                        <div class="step-title">Login & Change Password</div>
                        <div class="step-description">Use your temporary credentials to log in, then create a secure password of your choice.</div>
                    </div>
                </div>
                
                <div class="step">
                    
                    <div class="step-content">
                        <div class="step-title">Complete Your Profile</div>
                        <div class="step-description">Add any missing information and upload a profile picture to personalize your account.</div>
                    </div>
                </div>
                
                @if(in_array($user->role, ['nurse', 'doctor']))
                <div class="step">
                    
                    <div class="step-content">
                        <div class="step-title">Verification Process</div>
                        <div class="step-description">Your professional credentials will be reviewed by our admin team. You'll receive an email once verified.</div>
                    </div>
                </div>
                @endif
                
                <div class="step">
                    
                    <div class="step-content">
                        <div class="step-title">Explore the Platform</div>
                        <div class="step-description">Familiarize yourself with the features available for your role and start using the system.</div>
                    </div>
                </div>
            </div>
            
            @if($user->role === 'patient')
            <div style="background: #e6fffa; border: 1px solid #81e6d9; border-radius: 8px; padding: 20px; margin: 25px 0;">
                <h4 style="color: #234e52; margin: 0 0 10px 0;">👩‍⚕️ What You Can Do:</h4>
                <ul style="color: #2d3748; margin: 0; padding-left: 20px;">
                    <li>View your care plans and medical records</li>
                    <li>Track your vital signs and health progress</li>
                    <li>Communicate with your assigned healthcare team</li>
                    <li>Schedule appointments and request transportation</li>
                    <li>Make payments and manage billing information</li>
                </ul>
            </div>
            @elseif(in_array($user->role, ['nurse', 'doctor']))
            <div style="background: #f0f9ff; border: 1px solid #7dd3fc; border-radius: 8px; padding: 20px; margin: 25px 0;">
                <h4 style="color: #0c4a6e; margin: 0 0 10px 0;">🏥 Your Professional Tools:</h4>
                <ul style="color: #2d3748; margin: 0; padding-left: 20px;">
                    <li>Manage patient care plans and medical records</li>
                    <li>Record vital signs and track patient progress</li>
                    <li>Clock in/out for shifts and manage schedules</li>
                    <li>Document care notes and incident reports</li>
                    <li>Communicate with patients and care team members</li>
                </ul>
            </div>
            @endif
            
            <p style="margin-top: 30px;">If you have any questions or need assistance, please don't hesitate to reach out to our support team.</p>
        </div>
        
        <div class="footer">
            <p><strong>Need Help?</strong></p>
            <p>Contact our support team: <a href="mailto:{{ $supportEmail }}" class="support-link">{{ $supportEmail }}</a></p>
            <p style="margin-top: 20px; font-size: 12px; color: #a0aec0;">
                This email was sent from {{ $companyName }}. Please do not reply directly to this email.
            </p>
        </div>
    </div>
</body>
</html>