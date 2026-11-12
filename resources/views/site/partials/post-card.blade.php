{{-- کارت مطلب: لوح، شعر، اخبار و ... — $post، $showType (اختیاری) --}}
@php($cfg = $post->typeConfig())
<article @class(['card', 'card-poem' => $post->type === 'poem', 'card-video' => $post->type === 'video'])>
    <a href="{{ route('site.post', $post) }}" class="card-media" aria-label="{{ $post->title }}">
        @if ($post->image_url || ($post->type === 'visual' && $post->media_url))
            <img src="{{ $post->image_url ?? $post->media_url }}" alt="{{ $post->title }}" loading="lazy">
        @else
            <span class="placeholder"><x-site.icon :name="$cfg['icon']" /></span>
        @endif
        @if ($post->type === 'video')
            <span class="play-badge"><x-site.icon name="play" /></span>
        @endif
        @if ($showType ?? false)
            <span class="card-type">{{ $cfg['label'] }}</span>
        @endif
    </a>
    <div class="card-body">
        <h3 class="card-title"><a href="{{ route('site.post', $post) }}">{{ $post->title }}</a></h3>
        <div class="card-meta">
            @if ($post->creator_name)
                <span>{{ $post->creator_name }}</span>
            @endif
            <span>{{ jdate($post->published_at->copy()->setTimezone('Asia/Tehran'))->ago() }}</span>
        </div>
        @if ($post->type === 'poem')
            <p class="poem-excerpt">{{ $post->excerpt(90) }}</p>
        @endif
    </div>
</article>
