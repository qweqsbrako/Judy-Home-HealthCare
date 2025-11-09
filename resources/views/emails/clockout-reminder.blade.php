<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clock Out Reminder</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #f59e0b;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 5px 5px 0 0;
        }
        .content {
            background-color: #f9fafb;
            padding: 30px;
            border: 1px solid #e5e7eb;
            border-top: none;
        }
        .info-box {
            background-color: #fff;
            border-left: 4px solid #f59e0b;
            padding: 15px;
            margin: 20px 0;
        }
        .info-row {
            margin: 10px 0;
        }
        .label {
            font-weight: bold;
            color: #6b7280;
        }
        .value {
            color: #111827;
        }
        .warning {
            background-color: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .cta-button {
            display: inline-block;
            background-color: #f59e0b;
            color: white;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
            text-align: center;
        }
        .footer {
            text-align: center;
            padding: 20px;
            color: #6b7280;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1 style="margin: 0;">⏰ Clock Out Reminder</h1>
    </div>
    
    <div class="content">
        <p>Hi {{ $nurse_name }},</p>
        
        <div class="warning">
            <strong>⚠️ Important Notice:</strong> You have not clocked out from your shift yet.
        </div>
        
        <p>Our records show that you were scheduled to end your shift, but we haven't received your clock-out yet.</p>
        
        <div class="info-box">
            <h3 style="margin-top: 0; color: #111827;">Shift Details</h3>
            
            <div class="info-row">
                <span class="label">Patient:</span>
                <span class="value">{{ $patient_name }}</span>
            </div>
            
            <div class="info-row">
                <span class="label">Date:</span>
                <span class="value">{{ $shift_date }}</span>
            </div>
            
            <div class="info-row">
                <span class="label">Clock In Time:</span>
                <span class="value">{{ $clock_in_time }}</span>
            </div>
            
            <div class="info-row">
                <span class="label">Scheduled End Time:</span>
                <span class="value">{{ $scheduled_end_time }}</span>
            </div>
            
            <div class="info-row">
                <span class="label">Time Overdue:</span>
                <span class="value" style="color: #dc2626; font-weight: bold;">{{ $minutes_overdue }} minutes</span>
            </div>
        </div>
        
        <h3 style="color: #111827;">What to do:</h3>
        <ul style="line-height: 2;">
            <li>If you have finished your shift, please <strong>clock out immediately</strong> using the mobile app</li>
            <li>If you are still working, you can continue - just remember to clock out when done</li>
            <li>If you forgot to clock out, please clock out now and add notes if needed</li>
        </ul>
        
        <div style="text-align: center;">
            <a href="#" class="cta-button">Open Mobile App to Clock Out</a>
        </div>
        
        <p style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #e5e7eb; color: #6b7280; font-size: 14px;">
            <strong>Note:</strong> Accurate time tracking helps us maintain proper records and ensures you are compensated correctly for your work. If you're experiencing any issues with clocking out, please contact your supervisor.
        </p>
    </div>
    
    <div class="footer">
        <p>This is an automated reminder from the Home Care Management System.</p>
        <p>If you have already clocked out, please disregard this message.</p>
    </div>
</body>
</html>