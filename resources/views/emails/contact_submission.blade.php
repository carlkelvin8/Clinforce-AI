<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Contact Form Submission</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            line-height: 1.6;
            color: #334155;
            background-color: #f8fafc;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 40px auto;
            background: #ffffff;
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05);
        }
        .header {
            background: linear-gradient(135deg, #2563eb 0%, #4f46e5 100%);
            padding: 40px;
            text-align: center;
        }
        .logo {
            background: rgba(255, 255, 255, 0.2);
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: inline-block;
            line-height: 48px;
            color: #ffffff;
            font-weight: 900;
            font-size: 20px;
            margin-bottom: 16px;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        .header h1 {
            color: #ffffff;
            margin: 0;
            font-size: 24px;
            font-weight: 800;
            letter-spacing: -0.025em;
        }
        .content {
            padding: 40px;
        }
        .info-grid {
            margin-bottom: 32px;
            background: #f1f5f9;
            border-radius: 16px;
            padding: 24px;
        }
        .info-item {
            margin-bottom: 16px;
        }
        .info-item:last-child {
            margin-bottom: 0;
        }
        .label {
            display: block;
            font-size: 11px;
            font-weight: 800;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            margin-bottom: 4px;
        }
        .value {
            font-size: 16px;
            font-weight: 600;
            color: #0f172a;
        }
        .message-box {
            background: #ffffff;
            border: 2px solid #f1f5f9;
            border-radius: 16px;
            padding: 24px;
            margin-top: 24px;
        }
        .message-content {
            font-size: 16px;
            color: #475569;
            white-space: pre-wrap;
        }
        .footer {
            padding: 32px;
            text-align: center;
            font-size: 13px;
            color: #94a3b8;
            border-top: 1px solid #f1f5f9;
        }
        .footer p {
            margin: 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">AC</div>
            <h1>New Inquiry Received</h1>
        </div>
        <div class="content">
            <p style="margin-top: 0; font-weight: 500; color: #64748b;">You have a new submission from the Clinforce contact form.</p>
            
            <div class="info-grid">
                <div class="info-item">
                    <span class="label">Sender Name</span>
                    <div class="value">{{ $contact->name }}</div>
                </div>
                <div class="info-item">
                    <span class="label">Email Address</span>
                    <div class="value">{{ $contact->email }}</div>
                </div>
                <div class="info-item">
                    <span class="label">Subject</span>
                    <div class="value">{{ $contact->subject }}</div>
                </div>
            </div>

            <div class="message-box">
                <span class="label">Message Body</span>
                <div class="message-content">{{ $contact->message }}</div>
            </div>
        </div>
        <div class="footer">
            <p>© {{ date('Y') }} AI Clinforce Partners. Internal Notification System.</p>
        </div>
    </div>
</body>
</html>
