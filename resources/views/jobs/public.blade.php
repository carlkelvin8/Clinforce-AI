<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ $title }} — {{ $siteName }}</title>

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ $url }}">
    <meta property="og:title" content="{{ $title }}">
    <meta property="og:description" content="{{ $description }}">
    <meta property="og:site_name" content="{{ $siteName }}">
    <meta property="og:locale" content="en_US">
    @if(!empty($employer))
    <meta property="article:publisher" content="{{ $employer }}">
    @endif

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $title }}">
    <meta name="twitter:description" content="{{ $description }}">

    <!-- Canonical -->
    <link rel="canonical" href="{{ $url }}">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- JSON-LD Structured Data for Job Posting -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org/",
        "@type": "JobPosting",
        "title": "{{ addslashes($job->title) }}",
        "description": "{{ addslashes(strip_tags($job->description ?? '')) }}",
        "datePosted": "{{ $job->published_at?->toIso8601String() }}",
        @if($employer)
        "hiringOrganization": {
            "@type": "Organization",
            "name": "{{ addslashes($employer) }}"
        },
        @endif
        @if($job->employment_type)
        "employmentType": "{{ strtoupper($job->employment_type) }}",
        @endif
        @if($job->work_mode)
        "jobLocationType": "{{ $job->work_mode === 'remote' ? 'TELECOMMUTE' : 'IN_PERSON' }}",
        @endif
        "applicantLocationRequirements": {
            "@type": "Place",
            "address": {
                "@type": "PostalAddress",
                @if($job->city)
                "addressLocality": "{{ addslashes($job->city) }}",
                @endif
                @if($job->country)
                "addressCountry": "{{ addslashes($job->country) }}"
                @endif
            }
        }
    }
    </script>
</head>
<body>
    <div id="app">
        <!-- Visible fallback for crawlers (shown if JS doesn't run) -->
        <div style="max-width:720px;margin:40px auto;padding:24px;font-family:Inter,sans-serif;color:#1e293b;">
            <h1 style="font-size:24px;margin-bottom:8px;">{{ $title }}</h1>
            @if($employer)
            <p style="color:#64748b;margin-bottom:12px;">{{ $employer }}</p>
            @endif
            @if($location)
            <p style="color:#64748b;margin-bottom:16px;">📍 {{ $location }}</p>
            @endif
            @if($job->salary_min || $job->salary_max)
            <p style="color:#16a34a;margin-bottom:12px;font-weight:600;">
                💰
                @if($job->salary_min && $job->salary_max)
                    {{ $job->salary_currency }} {{ number_format($job->salary_min, 2) }} – {{ number_format($job->salary_max, 2) }}
                @elseif($job->salary_min)
                    From {{ $job->salary_currency }} {{ number_format($job->salary_min, 2) }}
                @else
                    Up to {{ $job->salary_currency }} {{ number_format($job->salary_max, 2) }}
                @endif
                ({{ $job->salary_type === 'hourly' ? 'per hour' : 'annually' }})
            </p>
            @endif
            <div style="line-height:1.7;white-space:pre-wrap;">{{ $description }}</div>
            <a href="{{ $url }}"
               style="display:inline-block;margin-top:20px;padding:10px 20px;background:#2563eb;color:#fff;border-radius:6px;text-decoration:none;">
                View Full Job Details
            </a>
        </div>
    </div>
</body>

<script src="https://js.stripe.com/v3/"></script>
<script>
  window.STRIPE_PUBLISHABLE_KEY = "{{ config('services.stripe.key', env('STRIPE_PUBLISHABLE_KEY', '')) }}";
</script>

</html>
