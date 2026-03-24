<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><title>Application Update</title></head>
<body style="font-family:sans-serif;color:#1a1a1a;max-width:600px;margin:0 auto;padding:24px">
    <h2 style="color:#2563eb">Application Update</h2>
    <p>Hi there,</p>
    <p>Your application for <strong>{{ $application->job?->title ?? 'a position' }}</strong> has been updated.</p>
    @php
        $labels = [
            'submitted'   => ['label' => 'Submitted',   'color' => '#6b7280'],
            'shortlisted' => ['label' => 'Shortlisted', 'color' => '#2563eb'],
            'interview'   => ['label' => 'Interview',   'color' => '#7c3aed'],
            'hired'       => ['label' => 'Hired',       'color' => '#16a34a'],
            'rejected'    => ['label' => 'Not Selected','color' => '#dc2626'],
            'withdrawn'   => ['label' => 'Withdrawn',   'color' => '#6b7280'],
        ];
        $toInfo = $labels[$toStatus] ?? ['label' => ucfirst($toStatus), 'color' => '#1a1a1a'];
    @endphp
    <p style="font-size:18px">Status: <strong style="color:{{ $toInfo['color'] }}">{{ $toInfo['label'] }}</strong></p>
    @if($toStatus === 'shortlisted')
    <p>Congratulations! You have been shortlisted. The employer may reach out to schedule an interview.</p>
    @elseif($toStatus === 'interview')
    <p>You have been selected for an interview. Check your dashboard for interview details.</p>
    @elseif($toStatus === 'hired')
    <p>Congratulations! You have been selected for this position. The employer will be in touch with next steps.</p>
    @elseif($toStatus === 'rejected')
    <p>Thank you for your interest. Unfortunately, the employer has decided to move forward with other candidates at this time.</p>
    @endif
    <p><a href="{{ config('app.frontend_url') }}/candidate/applications/{{ $application->id }}" style="color:#2563eb">View your application</a></p>
    <p style="color:#6b7280;font-size:13px;margin-top:32px">Clinforce &mdash; Healthcare Recruitment Platform</p>
</body>
</html>
