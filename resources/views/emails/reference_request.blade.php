<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><title>Reference Request</title></head>
<body style="font-family:sans-serif;color:#1a1a1a;max-width:600px;margin:0 auto;padding:24px">
    <h2 style="color:#2563eb">Reference Request</h2>
    <p>Hi {{ $check->referee_name }},</p>
    <p><strong>{{ $candidateName }}</strong> has listed you as a reference for the position of <strong>{{ $jobTitle }}</strong> at ClinForce.</p>
    <p>We'd greatly appreciate a few minutes of your time to provide a reference. Your feedback helps us make the best hiring decision.</p>

    <table style="width:100%;border-collapse:collapse;margin:16px 0">
        <tr><td style="padding:8px 0;color:#6b7280">Candidate</td><td style="padding:8px 0"><strong>{{ $candidateName }}</strong></td></tr>
        <tr><td style="padding:8px 0;color:#6b7280">Position</td><td style="padding:8px 0">{{ $jobTitle }}</td></tr>
        @if($check->referee_relationship)
        <tr><td style="padding:8px 0;color:#6b7280">Relationship</td><td style="padding:8px 0">{{ $check->referee_relationship }}</td></tr>
        @endif
        <tr><td style="padding:8px 0;color:#6b7280">Deadline</td><td style="padding:8px 0">{{ $check->expires_at?->format('M d, Y') ?? 'No deadline' }}</td></tr>
    </table>

    <div style="margin:24px 0">
        <a href="{{ $respondUrl }}"
           style="display:inline-block;background:#2563eb;color:#fff;padding:12px 32px;text-decoration:none;border-radius:8px;font-weight:bold">
            Provide Reference
        </a>
    </div>

    <p style="color:#6b7280;font-size:13px">The reference form includes questions about the candidate's performance, strengths, and whether you'd rehire them. It takes about 5-10 minutes.</p>
    <p style="color:#6b7280;font-size:13px;margin-top:32px">Clinforce &mdash; Healthcare Recruitment Platform</p>
</body>
</html>
