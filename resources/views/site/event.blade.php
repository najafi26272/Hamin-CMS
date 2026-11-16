@extends('layouts.site')

@section('title', $event->title)
@section('description', \Illuminate\Support\Str::limit(trim(strip_tags((string) $event->description)), 160) ?: $event->title)

@php($timings = ['upcoming' => 'پیش رو', 'ongoing' => 'در حال برگزاری', 'finished' => 'پایان یافته'])

@section('content')
<div class="container page">
    <ol class="breadcrumb">
        <li><a href="{{ route('site.home') }}">خانه</a></li>
        <li><a href="{{ route('site.events') }}">پویش و رویداد</a></li>
        <li aria-current="page">{{ \Illuminate\Support\Str::limit($event->title, 50) }}</li>
    </ol>

    <div class="event-layout">
        <div class="event-poster">
            @if ($event->poster_url)
                <img src="{{ $event->poster_url }}" alt="{{ $event->title }}">
            @else
                <span class="placeholder"><x-site.icon name="calendar" /></span>
            @endif
        </div>

        <article class="post-article">
            <header class="post-header">
                <span class="timing timing-{{ $event->timing }}">{{ $timings[$event->timing] }}</span>
                <h1 class="post-title" style="margin-top: 10px">{{ $event->title }}</h1>
                <ul class="event-facts">
                    <li><x-site.icon name="calendar" /> شروع: {{ $event->jalali('starts_at', 'l j F Y — H:i') }}</li>
                    <li><x-site.icon name="clock" /> پایان: {{ $event->jalali('ends_at', 'l j F Y — H:i') }}</li>
                </ul>

                @if ($event->can_join)
                    <a href="{{ $event->link }}" target="_blank" rel="noopener noreferrer" class="btn btn-primary event-join">
                        <x-site.icon name="log-in" /> {{ $event->join_label }}
                    </a>
                @endif
            </header>

            @if (trim(strip_tags((string) $event->description)) !== '')
                <div class="post-content">
                    {!! \App\Support\HtmlSanitizer::clean($event->description) !!}
                </div>
            @else
                <div style="height: 26px"></div>
            @endif
        </article>
    </div>

    @if ($others->isNotEmpty())
        <section class="section">
            <div class="section-head">
                <h2>رویدادهای دیگر</h2>
                <a href="{{ route('site.events') }}" class="more"><span>مشاهده همه</span> <x-site.icon name="chevron-left" /></a>
            </div>
            <div class="grid grid-4">
                @foreach ($others as $event)
                    @include('site.partials.event-card')
                @endforeach
            </div>
        </section>
    @endif
</div>
@endsection
