<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><title>Verification Request {{ ucfirst($verificationRequest->status) }}</title></head>
<body style="font-family:sans-serif;color:#1a1a1a;max-width:600px;margin:0 auto;padding:24px">
    @if($verificationRequest->status === 'approved')
    <h2 style="color:#16a34a">Verification Approved</h2>
    <p>Hi there,</p>
    <p>Your account has been <strong>verified</strong> on Clinforce. Your profile will now display a verified badge, increasing trust with applicants.</p>
    @else
    <h2 style="color:#dc2626">Verification Not Approved</h2>
    <p>Hi there,</p>
    <p>Unfortunately, your verification request was <strong>not approved</strong> at this time.</p>
    @if($verificationRequest->notes)
    <p><strong>Reason:</strong> {{ $verificationRequest->notes }}</p>
    @endif
    <p>You may submit a new verification request with updated information from your dashboard.</p>
    @endif
    <p><a href="{{ config('app.frontend_url') }}/employer/settings" style="color:#2563eb">Go to your dashboard</a></p>
    <p style="color:#6b7280;font-size:13px;margin-top:32px">Clinforce &mdash; Healthcare Recruitment Platform</p>
</body>
</html>
