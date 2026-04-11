<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- Default Open Graph (fallback) -->
  <meta property="og:type" content="website">
  <meta property="og:title" content="{{ config('app.name', 'ClinForce AI') }}">
  <meta property="og:description" content="Find your next career opportunity.">
  <meta property="og:site_name" content="{{ config('app.name', 'ClinForce AI') }}">
  <meta property="og:locale" content="en_US">

  <!-- Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

  @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>

  <div id="app"></div>
</body>

<script src="https://js.stripe.com/v3/"></script>
<script>
  window.STRIPE_PUBLISHABLE_KEY = "{{ config('services.stripe.key', env('STRIPE_PUBLISHABLE_KEY', '')) }}";
</script>

</html>