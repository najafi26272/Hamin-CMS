{{-- کارت صوتی به شکل قرص: دکمه پخش، عنوان و موج صدا — $post --}}
<div class="audio-card" data-audio-card>
    <button type="button" class="audio-play" aria-label="پخش {{ $post->title }}" @disabled(! $post->media_url) @unless($post->media_url) title="فایل صوتی بارگذاری نشده" @endunless>
        <x-site.icon name="play" class="icon i-play" />
        <x-site.icon name="pause" class="icon i-pause" />
    </button>
    <div class="audio-info">
        <a href="{{ route('site.post', $post) }}" class="audio-title">{{ $post->title }}</a>
        @if ($post->creator_name)
            <span class="audio-creator">{{ $post->creator_name }}</span>
        @endif
        @include('site.partials.wave', ['seed' => $post->id, 'bars' => 56])
    </div>
    <span class="audio-time">0:00</span>
    @if ($post->media_url)
        <audio src="{{ $post->media_url }}" preload="metadata"></audio>
    @endif
</div>
