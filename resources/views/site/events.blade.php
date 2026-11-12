@extends('layouts.site')

@section('title', 'پویش و رویداد')

@section('content')
<div class="container page">
    <div class="page-head">
        <div>
            <h1>پویش و رویداد</h1>
            <p>نمایشگاه‌ها، جشنواره‌ها و برنامه‌های مجمع هنری هامین</p>
        </div>
    </div>

    <nav class="tabs">
        <a href="{{ route('site.events') }}" @class(['tab', 'active' => ! $past])>پیش رو و در حال برگزاری</a>
        <a href="{{ route('site.events', ['show' => 'past']) }}" @class(['tab', 'active' => $past])>برگزار شده</a>
    </nav>

    @if ($events->isEmpty())
        <div class="empty">
            <x-site.icon name="calendar" />
            <div>{{ $past ? 'رویداد برگزارشده‌ای وجود ندارد.' : 'در حال حاضر رویداد پیش رویی نداریم.' }}</div>
        </div>
    @else
        <div class="grid grid-4">
            @foreach ($events as $event)
                @include('site.partials.event-card')
            @endforeach
        </div>

        {{ $events->links('site.partials.pagination') }}
    @endif
</div>
@endsection
