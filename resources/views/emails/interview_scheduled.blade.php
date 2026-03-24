<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><title>Interview Scheduled</title></head>
<body style="font-family:sans-serif;color:#1a1a1a;max-width:600px;margin:0 auto;padding:24px">
    <h2 style="color:#2563eb">Interview Scheduled</h2>
    <p>Hi there,</p>
    <p>An interview has been scheduled for the position <strong>{{ $interview->application?->job?->title ?? 'a position' }}</strong>.</p>
    <table style="width:100%;border-collapse:collapse;margin:16px 0">
        <tr><td style="padding:8px 0;color:#6b7280">Date &amp; Time</td><td style="padding:8px 0"><strong>{{ $interview->scheduled_start?->format('M d, Y g:i A') }}</strong></td></tr>
        <tr><td style="padding:8px 0;color:#6b7280">Duration</td><td style="padding:8px 0">{{ $interview->scheduled_start && $interview->scheduled_end ? $interview->scheduled_start->diffInMinutes($interview->scheduled_end) . ' minutes' : 'TBD' }}</td></tr>
        <tr><td style="padding:8px 0;color:#6b7280">Mode</td><td style="padding:8px 0">{{ ucfirst(str_replace('_', ' ', $interview->mode ?? 'TBD')) }}</td></tr>
        @if($interview->meeting_link)
        <tr><td style="padding:8px 0;color:#6b7280">Meeting Link</td><td style="padding:8px 0"><a href="{{ $interview->meeting_link }}" style="color:#2563eb">Join Meeting</a></td></tr>
        @endif
        @if($interview->location_text)
        <tr><td style="padding:8px 0;color:#6b7280">Location</td><td style="padding:8px 0">{{ $interview->location_text }}</td></tr>
        @endif
    </table>
    <p>Please be available at the scheduled time. If you need to reschedule, contact the employer directly.</p>
    <p style="color:#6b7280;font-size:13px;margin-top:32px">Clinforce &mdash; Healthcare Recruitment Platform</p>
</body>
</html>
