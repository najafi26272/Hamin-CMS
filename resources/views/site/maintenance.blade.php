{{-- صفحه «در حال توسعه» (پنل ← تنظیمات سایت ← حالت توسعه). مستقل از لایوت سایت تا منو و محتوا نمایش داده نشود --}}
@php
    $siteTitle = \App\Models\Setting::siteTitle();
@endphp
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <meta name="theme-color" content="#19786f">
    <title>در حال توسعه | {{ $siteTitle }}</title>
    <meta name="description" content="{{ \App\Models\Setting::siteDescription() }}">
    <link rel="icon" href="{{ \App\Models\Setting::faviconUrl() }}">
    <link rel="stylesheet" href="{{ asset('assets/front/css/site.css') }}?v={{ filemtime(public_path('assets/front/css/site.css')) }}">
</head>
<body>
<main class="maintenance-page">
    <div class="maintenance-card">
        <img src="{{ \App\Models\Setting::logoUrl() }}" alt="{{ $siteTitle }}" class="logo">

        <div class="maintenance-icon"><x-site.icon name="settings" /></div>

        <h1>در حال توسعه هستیم</h1>
        <p>{{ \App\Models\Setting::maintenanceMessage() }}</p>

        @include('site.partials.social-links')

        <a href="{{ route('login') }}" class="maintenance-login">ورود مدیران</a>
    </div>
</main>
</body>
</html>
