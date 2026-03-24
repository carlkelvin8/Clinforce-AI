<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><title>Subscription Confirmed</title></head>
<body style="font-family:sans-serif;color:#1a1a1a;max-width:600px;margin:0 auto;padding:24px">
    <h2 style="color:#2563eb">Subscription Confirmed</h2>
    <p>Hi there,</p>
    <p>Your subscription to <strong>{{ $subscription->plan?->name ?? 'Clinforce' }}</strong> is now active.</p>
    <table style="width:100%;border-collapse:collapse;margin:16px 0">
        <tr><td style="padding:8px 0;color:#6b7280">Plan</td><td style="padding:8px 0"><strong>{{ $subscription->plan?->name }}</strong></td></tr>
        <tr><td style="padding:8px 0;color:#6b7280">Amount</td><td style="padding:8px 0"><strong>{{ $subscription->currency_code }} {{ number_format($subscription->amount_cents / 100, 2) }}</strong></td></tr>
        <tr><td style="padding:8px 0;color:#6b7280">Start</td><td style="padding:8px 0">{{ $subscription->start_at?->format('M d, Y') }}</td></tr>
        <tr><td style="padding:8px 0;color:#6b7280">Expires</td><td style="padding:8px 0">{{ $subscription->end_at?->format('M d, Y') }}</td></tr>
    </table>
    <p>You can manage your subscription from your <a href="{{ config('app.frontend_url') }}/employer/billing" style="color:#2563eb">billing dashboard</a>.</p>
    <p style="color:#6b7280;font-size:13px;margin-top:32px">Clinforce &mdash; Healthcare Recruitment Platform</p>
</body>
</html>
