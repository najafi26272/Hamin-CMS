{{-- کارت صوتی به شکل قرص: دکمه پخش، عنوان و موج صدا — $post --}}
@php
    // ارتفاع میله‌های موج بر اساس شناسه مطلب ساخته می‌شود تا هر کارت شکل ثابت و متفاوتی داشته باشد
    mt_srand($post->id * 7919);
    $bars = [];
    for ($i = 0; $i < 56; $i++) {
        $envelope = sin(M_PI * ($i + 1) / 57);
        $bars[] = max(3, round((0.25 + 0.75 * $envelope) * mt_rand(25, 100) / 100 * 30));
    }
    mt_srand();

    $wave = '';
    foreach ($bars as $i => $h) {
        $wave .= '<rect x="'.($i * 5).'" y="'.((34 - $h) / 2).'" width="2.6" height="'.$h.'" rx="1.3"/>';
    }
@endphp
<div class="audio-card" data-audio-card>
    <button type="button" class="audio-play" aria-label="پخش {{ $post->title }}" @disabled(! $post->media_url)>
        <x-site.icon name="play" class="icon i-play" />
        <x-site.icon name="pause" class="icon i-pause" />
    </button>
    <div class="audio-info">
        <a href="{{ route('site.post', $post) }}" class="audio-title">{{ $post->title }}</a>
        @if ($post->creator_name)
            <span class="audio-creator">{{ $post->creator_name }}</span>
        @endif
        <div class="wave" title="جابه‌جایی در صدا">
            <svg class="wave-base" viewBox="0 0 280 34" preserveAspectRatio="none" fill="currentColor" aria-hidden="true">{!! $wave !!}</svg>
            <svg class="wave-fill" viewBox="0 0 280 34" preserveAspectRatio="none" fill="currentColor" aria-hidden="true">{!! $wave !!}</svg>
        </div>
    </div>
    <span class="audio-time">0:00</span>
    @if ($post->media_url)
        <audio src="{{ $post->media_url }}" preload="metadata"></audio>
    @endif
</div>
