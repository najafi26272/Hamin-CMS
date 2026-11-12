{{--
    لایوت سایت عمومی
    - صفحه‌های کنترلر: @extends('layouts.site') و @section('content')
    - کامپوننت‌های Livewire (ورود و ثبت‌نام): #[Layout('layouts::site')] و محتوا در $slot
--}}
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#19786f">
    @php
        $pageTitle = trim($__env->yieldContent('title')) ?: ($title ?? null);
    @endphp
    <title>{{ $pageTitle ? $pageTitle.' | ' : '' }}مجمع هنری هامین</title>
    <meta name="description" content="@yield('description', 'مجمع هنری هامین؛ لوح، شنیداری، تماشایی، شعر و رویدادهای هنری')">
    <link rel="icon" type="image/png" href="{{ asset('assets/front/images/icon.png') }}">
    <link rel="preload" href="{{ asset('assets/front/fonts/Vazirmatn-Variable.woff2') }}" as="font" type="font/woff2" crossorigin>
    <link rel="stylesheet" href="{{ asset('assets/front/css/site.css') }}?v={{ filemtime(public_path('assets/front/css/site.css')) }}">
    @stack('head')
</head>
<body>

@php
    // موضوعات منو: دسته‌بندی‌های اصلی و زیرمجموعه‌ها
    $menuCategories = \App\Models\Category::whereNull('parent_id')->with(['children' => fn ($q) => $q->orderBy('name')])->orderBy('name')->get();
@endphp

<header class="container">
    <div class="site-top">
        <a href="{{ route('site.home') }}" class="site-logo" aria-label="مجمع هنری هامین - صفحه اصلی">
            <img src="{{ asset('assets/front/images/logo.png') }}" alt="مجمع هنری هامین" width="452" height="108">
        </a>

        <div class="top-actions">
            @auth
                <a href="{{ route('dashboard') }}" class="panel-link" title="پنل کاربری">
                    <img src="{{ auth()->user()->avatar_url }}" alt="">
                    <span>{{ auth()->user()->first_name }}</span>
                </a>
                <a href="{{ route('logout') }}" class="login" style="font-size: 14px">خروج</a>
            @else
                <a href="{{ route('login') }}" class="login">ورود</a>
                <a href="{{ route('register') }}" class="register">عضویت</a>
            @endauth
        </div>
    </div>

    <nav class="site-nav" aria-label="منوی اصلی">
        <button type="button" class="nav-toggle" aria-label="منو" aria-expanded="false"><x-site.icon name="menu" /></button>

        <ul class="nav-menu">
            <li class="has-dropdown">
                <button type="button" aria-expanded="false">موضوعات <x-site.icon name="chevron-down" class="chevron" /></button>
                <ul class="dropdown">
                    @foreach (\App\Models\Post::TYPES as $key => $type)
                        @continue($key === 'news')
                        <li><a href="{{ route('site.posts', ['type' => $key]) }}">{{ $type['label'] }}</a></li>
                    @endforeach
                    @foreach ($menuCategories as $category)
                        <li><a href="{{ route('site.posts', ['category' => $category->slug]) }}">{{ $category->name }}</a></li>
                        @foreach ($category->children as $child)
                            <li class="sub"><a href="{{ route('site.posts', ['category' => $child->slug]) }}">{{ $child->name }}</a></li>
                        @endforeach
                    @endforeach
                </ul>
            </li>
            <li><a href="{{ route('site.artists') }}" @class(['active' => request()->routeIs('site.artist*')])>هنرمندان</a></li>
            <li><a href="{{ route('site.posts', ['type' => 'news']) }}" @class(['active' => request()->routeIs('site.posts') && request('type') === 'news'])>اخبار</a></li>
            <li><a href="{{ route('site.posts') }}" @class(['active' => request()->routeIs('site.posts') && ! request()->hasAny(['type', 'q', 'category', 'tag'])])>وبلاگ</a></li>
            <li><a href="{{ route('site.events') }}" @class(['active' => request()->routeIs('site.event*')])>پویش <span class="divider">|</span> رویداد</a></li>
        </ul>

        <form action="{{ route('site.posts') }}" method="get" class="nav-search" role="search">
            <label for="site-search" class="sr-only">جستجو</label>
            <input type="search" id="site-search" name="q" value="{{ request()->routeIs('site.posts') ? request('q') : '' }}" placeholder="جستجوی اثر، هنرمند و ...">
            <button type="submit" aria-label="جستجو"><x-site.icon name="search" /></button>
        </form>
    </nav>
</header>

<main>
    @yield('content')
    {{ $slot ?? '' }}
</main>

<footer class="site-footer">
    <div class="container footer-inner">
        <a href="{{ route('site.home') }}"><img src="{{ asset('assets/front/images/logo.png') }}" alt="مجمع هنری هامین" width="167" height="40"></a>
        <ul class="footer-links">
            @foreach (\App\Models\Post::TYPES as $key => $type)
                <li><a href="{{ route('site.posts', ['type' => $key]) }}">{{ $type['label'] }}</a></li>
            @endforeach
            <li><a href="{{ route('site.artists') }}">هنرمندان</a></li>
            <li><a href="{{ route('site.events') }}">رویدادها</a></li>
        </ul>
    </div>
    <div class="footer-bottom">
        <div class="container">© {{ jdate()->format('Y') }} مجمع هنری هامین — همه حقوق محفوظ است.</div>
    </div>
</footer>

<script src="{{ asset('assets/front/js/site.js') }}?v={{ filemtime(public_path('assets/front/js/site.js')) }}" defer></script>
@stack('scripts')
</body>
</html>
