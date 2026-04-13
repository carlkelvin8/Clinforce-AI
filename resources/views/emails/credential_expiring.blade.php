<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><title>Credential Expiring</title></head>
<body style="font-family:sans-serif;color:#1a1a1a;max-width:600px;margin:0 auto;padding:24px">
    <h2 style="color:#d97706">⚠️ Credential Expiring Soon</h2>
    <p>Hi there,</p>
    <p>The following credential for <strong>{{ $candidateName }}</strong> will expire in <strong>{{ $daysUntilExpiry }} days</strong>:</p>

    <table style="width:100%;border-collapse:collapse;margin:16px 0;background:#fef3c7;border-radius:8px;padding:16px">
        <tr><td style="padding:8px 0;color:#6b7280">Credential</td><td style="padding:8px 0"><strong>{{ $credential->credential_type }}</strong></td></tr>
        <tr><td style="padding:8px 0;color:#6b7280">License Number</td><td style="padding:8px 0">{{ $credential->license_number }}</td></tr>
        @if($credential->issuing_authority)
        <tr><td style="padding:8px 0;color:#6b7280">Issued By</td><td style="padding:8px 0">{{ $credential->issuing_authority }}</td></tr>
        @endif
        <tr><td style="padding:8px 0;color:#6b7280">Expires</td><td style="padding:8px 0"><strong>{{ $credential->expiry_date?->format('M d, Y') }}</strong></td></tr>
    </table>

    <p>Please ensure this credential is renewed before it expires to maintain compliance.</p>

    @if($credential->verification_url)
    <p><a href="{{ $credential->verification_url }}" style="color:#2563eb">Verify this credential online →</a></p>
    @endif

    <p style="color:#6b7280;font-size:13px;margin-top:32px">Clinforce &mdash; Healthcare Recruitment Platform</p>
</body>
</html>
