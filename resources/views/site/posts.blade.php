@extends('layouts.site')

@section('title', $title)

@section('content')
    <div class="container page">
        <div class="page-head">
            <div>
                <h1>{{ $title }}</h1>
                @if ($category?->description)
                    <p>{{ $category->description }}</p>
                @else
                    <p>{{ number_format($posts->total()) }} مورد</p>
                @endif
            </div>
        </div>

        {{-- فیلتر نوع محتوا (فیلترهای دیگر حفظ می‌شوند) --}}
        @php($keep = array_filter(['q' => $q ?: null, 'category' => $category?->slug, 'tag' => $tag?->slug]))
        <nav class="tabs" aria-label="نوع محتوا">
            <a href="{{ route('site.posts', $keep) }}" @class(['tab', 'active' => ! $type])>همه</a>
            @foreach (\App\Models\Post::TYPES as $key => $cfg)
                <a href="{{ route('site.posts', $keep + ['type' => $key]) }}" @class(['tab', 'active' => $type === $key])>{{ $cfg['label'] }}</a>
            @endforeach
        </nav>

        @if ($artists->isNotEmpty())
            <h2 style="font-size: 17px">هنرمندان</h2>
            <div class="grid grid-4" style="margin-bottom: 30px">
                @foreach ($artists as $artist)
                    @include('site.partials.artist-card')
                @endforeach
            </div>
        @endif

        @if ($posts->isEmpty())
            <div class="empty">
                <x-site.icon name="search" />
                <div>موردی پیدا نشد.</div>
            </div>
        @else
            @if ($type === 'audio')
                <div class="grid grid-2">
                    @foreach ($posts as $post)
                        @include('site.partials.audio-card')
                    @endforeach
                </div>
            @else
                <div @class(['grid', 'grid-3' => $type === 'video', 'grid-4' => $type !== 'video', 'grid-visual' => $type === 'visual'])>
                    @foreach ($posts as $post)
                        @include('site.partials.post-card', ['showType' => ! $type])
                    @endforeach
                </div>
            @endif

            {{ $posts->links('site.partials.pagination') }}
        @endif
    </div>
@endsection
