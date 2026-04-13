<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><title>Reference Received</title></head>
<body style="font-family:sans-serif;color:#1a1a1a;max-width:600px;margin:0 auto;padding:24px">
    <h2 style="color:#059669">Reference Received</h2>
    <p>Hi there,</p>
    <p>A reference has been submitted for <strong>{{ $candidateName }}</strong>.</p>

    <table style="width:100%;border-collapse:collapse;margin:16px 0">
        <tr><td style="padding:8px 0;color:#6b7280">Referee</td><td style="padding:8px 0"><strong>{{ $check->referee_name }}</strong></td></tr>
        @if($check->referee_title)
        <tr><td style="padding:8px 0;color:#6b7280">Title</td><td style="padding:8px 0">{{ $check->referee_title }}{{ $check->referee_company ? ' at ' . $check->referee_company : '' }}</td></tr>
        @endif
        @if($check->rating)
        <tr><td style="padding:8px 0;color:#6b7280">Rating</td><td style="padding:8px 0">
            @for($i = 1; $i <= 5; $i++)
                {{ $i <= round($check->rating) ? '★' : '☆' }}
            @endfor
            ({{ $check->rating }}/5)
        </td></tr>
        @endif
        @if($check->would_rehire !== null)
        <tr><td style="padding:8px 0;color:#6b7280">Would Rehire</td><td style="padding:8px 0">{{ $check->would_rehire ? '✅ Yes' : '❌ No' }}</td></tr>
        @endif
        <tr><td style="padding:8px 0;color:#6b7280">Submitted</td><td style="padding:8px 0">{{ $check->completed_at?->format('M d, Y g:i A') }}</td></tr>
    </table>

    @if($check->comments)
    <div style="background:#f9fafb;padding:16px;border-radius:8px;margin:16px 0">
        <strong>Comments:</strong>
        <p style="margin:8px 0 0">{{ $check->comments }}</p>
    </div>
    @endif

    <p style="color:#6b7280;font-size:13px;margin-top:32px">View all references in your ClinForce dashboard.</p>
    <p style="color:#6b7280;font-size:13px">Clinforce &mdash; Healthcare Recruitment Platform</p>
</body>
</html>
