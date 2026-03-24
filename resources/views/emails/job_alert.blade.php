<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><title>Job Alert</title></head>
<body style="font-family:Arial,sans-serif;color:#1e293b;padding:32px;background:#f8fafc;">
<div style="max-width:560px;margin:0 auto;background:#fff;border-radius:12px;padding:32px;border:1px solid #e2e8f0;">
  <h2 style="color:#2563eb;margin-top:0;">New job match for you</h2>
  <h3 style="margin:0 0 8px;">{{ $job->title }}</h3>
  <p style="color:#64748b;margin:0 0 16px;">
    {{ $job->city ? $job->city . ', ' : '' }}{{ $job->country ?? '' }}
    &nbsp;·&nbsp;{{ str_replace('_', ' ', ucfirst($job->employment_type ?? '')) }}
  </p>
  <p style="color:#475569;">{{ Str::limit($job->description, 200) }}</p>
  <a href="{{ url('/candidate/jobs/' . $job->id) }}"
     style="display:inline-block;margin-top:16px;padding:12px 24px;background:#2563eb;color:#fff;border-radius:8px;text-decoration:none;font-weight:600;">
    View Job
  </a>
  <hr style="margin:24px 0;border:none;border-top:1px solid #e2e8f0;">
  <p style="font-size:12px;color:#94a3b8;">
    You received this because you set up a job alert. 
    <a href="{{ url('/candidate/settings') }}" style="color:#2563eb;">Manage alerts</a>
  </p>
</div>
</body>
</html>
