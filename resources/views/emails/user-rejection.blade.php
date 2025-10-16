<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration Decision</title>
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
        h2 {
            color: #1f2937;
            font-size: 20px;
            margin-bottom: 20px;
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
        .rejection-reason {
            background-color: #fef2f2;
            border-left: 4px solid #ef4444;
            padding: 15px 20px;
            margin: 20px 0;
            border-radius: 0 6px 6px 0;
        }
        .rejection-reason h3 {
            color: #dc2626;
            margin: 0 0 10px 0;
            font-size: 16px;
        }
        .rejection-reason p {
            margin: 0;
            color: #7f1d1d;
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
            <h2>Registration Decision</h2>
            
            <p>Dear {{ $user->first_name }} {{ $user->last_name }},</p>
            
            <p>Thank you for your interest in joining the Judy Home Care Portal as a {{ $role }}. After careful review of your application, we regret to inform you that we are unable to approve your registration at this time.</p>
            
            <div class="user-info">
                <h3>Application Details</h3>
                <p><strong>Name:</strong> {{ $user->first_name }} {{ $user->last_name }}</p>
                <p><strong>Email:</strong> {{ $user->email }}</p>
                <p><strong>Role Applied:</strong> {{ $role }}</p>
                <p><strong>Application Date:</strong> {{ $user->created_at->format('F j, Y') }}</p>
                <p><strong>Review Date:</strong> {{ now()->format('F j, Y') }}</p>
            </div>
            
            <div class="rejection-reason">
                <h3>Reason</h3>
                <p>{{ $rejectionReason }}</p>
            </div>
            
            <p>We appreciate the time you took to complete your application and wish you well in your future endeavors.</p>
            
            <p>If you have any questions regarding this decision, you may contact our support team.</p>
        </div>
        
        <div class="footer">
            <p><strong>Judy Home Care Portal</strong><br>
            Quality Healthcare at Home</p>
            
            <div class="contact-info">
                <p>Email: <a href="mailto:{{ $supportEmail }}">{{ $supportEmail }}</a></p>
                @if($supportPhone)
                <p>Phone: {{ $supportPhone }}</p>
                @endif
                <p>This email was sent automatically. Please do not reply to this email.</p>
            </div>
        </div>
    </div>
</body>
</html>