<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><title>Invoice #{{ $invoice->id }}</title></head>
<body style="font-family:sans-serif;color:#1a1a1a;max-width:600px;margin:0 auto;padding:24px">
    <h2 style="color:#2563eb">Invoice #{{ $invoice->id }}</h2>
    <p>Hi there,</p>
    <p>Your payment has been received. Here is your invoice summary:</p>
    <table style="width:100%;border-collapse:collapse;margin:16px 0">
        <tr><td style="padding:8px 0;color:#6b7280">Invoice #</td><td style="padding:8px 0"><strong>{{ $invoice->id }}</strong></td></tr>
        <tr><td style="padding:8px 0;color:#6b7280">Amount</td><td style="padding:8px 0"><strong>{{ $invoice->currency_code }} {{ number_format($invoice->amount_cents / 100, 2) }}</strong></td></tr>
        <tr><td style="padding:8px 0;color:#6b7280">Status</td><td style="padding:8px 0"><strong>{{ ucfirst($invoice->status) }}</strong></td></tr>
        <tr><td style="padding:8px 0;color:#6b7280">Date</td><td style="padding:8px 0">{{ $invoice->issued_at?->format('M d, Y') }}</td></tr>
        @if($invoice->provider_ref)
        <tr><td style="padding:8px 0;color:#6b7280">Reference</td><td style="padding:8px 0" style="font-size:12px;color:#6b7280">{{ $invoice->provider_ref }}</td></tr>
        @endif
    </table>
    <p>View your full invoice history in your <a href="{{ config('app.frontend_url') }}/employer/billing" style="color:#2563eb">billing dashboard</a>.</p>
    <p style="color:#6b7280;font-size:13px;margin-top:32px">Clinforce &mdash; Healthcare Recruitment Platform</p>
</body>
</html>
