{{-- کارت رویداد — $event --}}
@php($timings = ['upcoming' => 'پیش رو', 'ongoing' => 'در حال برگزاری', 'finished' => 'پایان یافته'])
<article class="card event-card">
    <a href="{{ route('site.event', $event) }}" class="card-media" aria-label="{{ $event->title }}">
        @if ($event->poster_url)
            <img src="{{ $event->poster_url }}" alt="{{ $event->title }}" loading="lazy">
        @else
            <span class="placeholder"><x-site.icon name="calendar" /></span>
        @endif
        <span class="event-date">{{ $event->jalali('starts_at', 'j F') }}</span>
    </a>
    <div class="card-body">
        <h3 class="card-title"><a href="{{ route('site.event', $event) }}">{{ $event->title }}</a></h3>
        <div class="event-card-foot">
            <span class="timing timing-{{ $event->timing }}">{{ $timings[$event->timing] }}</span>
            @if ($event->can_join)
                <a href="{{ $event->link }}" target="_blank" rel="noopener noreferrer" class="event-join-sm">{{ $event->join_label }}</a>
            @endif
        </div>
    </div>
</article>
