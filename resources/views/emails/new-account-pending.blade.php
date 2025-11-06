<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Account Pending Verification</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
        }
        .header {
            background: linear-gradient(135deg, #199A8E 0%, #16857B 100%);
            padding: 40px 30px;
            text-align: center;
        }
        .header h1 {
            color: #ffffff;
            margin: 0;
            font-size: 28px;
            font-weight: 600;
        }
        .content {
            padding: 40px 30px;
        }
        .alert-box {
            background-color: #FFF4E6;
            border-left: 4px solid #FF9800;
            padding: 20px;
            margin-bottom: 30px;
            border-radius: 4px;
        }
        .alert-box h2 {
            margin: 0 0 10px 0;
            color: #E65100;
            font-size: 20px;
        }
        .alert-box p {
            margin: 0;
            color: #5D4037;
        }
        .user-details {
            background-color: #F8F9FA;
            border-radius: 8px;
            padding: 25px;
            margin: 25px 0;
        }
        .user-details h3 {
            margin: 0 0 15px 0;
            color: #199A8E;
            font-size: 18px;
        }
        .detail-row {
            display: flex;
            padding: 12px 0;
            border-bottom: 1px solid #E0E0E0;
        }
        .detail-row:last-child {
            border-bottom: none;
        }
        .detail-label {
            font-weight: 600;
            color: #666;
            width: 180px;
            flex-shrink: 0;
        }
        .detail-value {
            color: #333;
            word-break: break-word;
        }
        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            background-color: #FF9800;
            color: #ffffff;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .button {
            display: inline-block;
            padding: 14px 32px;
            background-color: #199A8E;
            color: #ffffff;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            margin: 10px 5px;
            transition: background-color 0.3s;
        }
        .button:hover {
            background-color: #16857B;
        }
        .button-secondary {
            background-color: #6C757D;
        }
        .button-secondary:hover {
            background-color: #5A6268;
        }
        .actions {
            text-align: center;
            margin: 30px 0;
        }
        .footer {
            background-color: #F8F9FA;
            padding: 25px 30px;
            text-align: center;
            color: #666;
            font-size: 14px;
        }
        .footer p {
            margin: 5px 0;
        }
        .timestamp {
            color: #999;
            font-size: 13px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>🏥 New Account Registration</h1>
        </div>

        <!-- Content -->
        <div class="content">
            <!-- Alert Box -->
            <div class="alert-box">
                <h2>⚠️ Action Required</h2>
                <p>A new healthcare professional has registered and requires verification before they can access the system.</p>
            </div>

            <!-- User Details -->
            <div class="user-details">
                <h3>👤 Account Information</h3>
                
                <div class="detail-row">
                    <div class="detail-label">Name:</div>
                    <div class="detail-value">{{ $user->first_name }} {{ $user->last_name }}</div>
                </div>
                
                <div class="detail-row">
                    <div class="detail-label">Email:</div>
                    <div class="detail-value">{{ $user->email }}</div>
                </div>
                
                <div class="detail-row">
                    <div class="detail-label">Phone:</div>
                    <div class="detail-value">{{ $user->phone }}</div>
                </div>
                
                <div class="detail-row">
                    <div class="detail-label">Role:</div>
                    <div class="detail-value">
                        <strong>{{ ucfirst($user->role) }}</strong>
                    </div>
                </div>
                
                <div class="detail-row">
                    <div class="detail-label">License Number:</div>
                    <div class="detail-value">{{ $user->license_number ?? 'N/A' }}</div>
                </div>
                
                <div class="detail-row">
                    <div class="detail-label">Specialization:</div>
                    <div class="detail-value">{{ ucwords(str_replace('_', ' ', $user->specialization ?? 'N/A')) }}</div>
                </div>
                
                <div class="detail-row">
                    <div class="detail-label">Registration Date:</div>
                    <div class="detail-value">{{ $user->created_at->format('M d, Y \a\t h:i A') }}</div>
                </div>
                
                <div class="detail-row">
                    <div class="detail-label">Status:</div>
                    <div class="detail-value">
                        <span class="status-badge">Pending Verification</span>
                    </div>
                </div>
            </div>


            <!-- Additional Info -->
            <div style="background-color: #E3F2FD; padding: 20px; border-radius: 8px; margin-top: 25px;">
                <p style="margin: 0; color: #1565C0; font-size: 14px;">
                    <strong>💡 Next Steps:</strong><br>
                    1. Verify the healthcare professional's credentials<br>
                    2. Check the license number validity<br>
                    3. Approve or reject the application<br>
                    4. The user will be automatically notified of your decision via email and SMS
                </p>
            </div>

            <!-- Timestamp -->
            <p class="timestamp">
                This notification was sent on {{ now()->format('l, F j, Y \a\t g:i A') }}
            </p>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p><strong>Healthcare Management System</strong></p>
            <p>Administrator Notification</p>
            <p style="margin-top: 15px; font-size: 12px; color: #999;">
                This is an automated message. Please do not reply to this email.
            </p>
        </div>
    </div>
</body>
</html>