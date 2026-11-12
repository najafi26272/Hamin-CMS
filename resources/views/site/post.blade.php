@extends('layouts.site')

@section('title', $post->title)
@section('description', $post->excerpt(160) ?: $post->title)

@push('head')
    <meta property="og:title" content="{{ $post->title }}">
    <meta property="og:type" content="article">
    @if ($post->image_url)
        <meta property="og:image" content="{{ $post->image_url }}">
    @endif
@endpush

@php
    $cfg = $post->typeConfig();
    $isArtist = $post->user?->artist_status === 'approved';
@endphp

@section('content')
<div class="container page">
    <ol class="breadcrumb">
        <li><a href="{{ route('site.home') }}">خانه</a></li>
        <li><a href="{{ route('site.posts', ['type' => $post->type]) }}">{{ $cfg['label'] }}</a></li>
        <li aria-current="page">{{ \Illuminate\Support\Str::limit($post->title, 50) }}</li>
    </ol>

    <div class="post-layout">
        <div>
            <article class="post-article">
                <header class="post-header">
                    <span class="post-type"><x-site.icon :name="$cfg['icon']" /> {{ $cfg['label'] }}</span>
                    <h1 class="post-title">{{ $post->title }}</h1>
                    <div class="post-meta">
                        @if ($post->creator_name)
                            <span><x-site.icon name="user" /> {{ $cfg['creator'] ?? 'صاحب اثر' }}: {{ $post->creator_name }}</span>
                        @endif
                        <span><x-site.icon name="calendar" /> {{ jdate($post->published_at->copy()->setTimezone('Asia/Tehran'))->format('j F Y') }}</span>
                        <span><x-site.icon name="eye" /> {{ number_format($post->views_count) }} بازدید</span>
                    </div>
                </header>

                {{-- محتوای اصلی بر اساس نوع --}}
                @switch($post->type)
                    @case('visual')
                        @if ($post->media_url || $post->image_url)
                            <div class="post-media is-image">
                                <a href="{{ $post->media_url ?? $post->image_url }}" target="_blank" rel="noopener">
                                    <img src="{{ $post->media_url ?? $post->image_url }}" alt="{{ $post->title }}">
                                </a>
                            </div>
                        @endif
                        @break

                    @case('video')
                        @if ($post->media_url)
                            <div class="post-media">
                                <video src="{{ $post->media_url }}" controls preload="metadata" playsinline @if ($post->image_url) poster="{{ $post->image_url }}" @endif></video>
                            </div>
                        @endif
                        @break

                    @case('audio')
                        <div class="post-audio">
                            <div class="cover">
                                @if ($post->image_url)
                                    <img src="{{ $post->image_url }}" alt="{{ $post->title }}">
                                @else
                                    <span class="placeholder"><x-site.icon name="music" /></span>
                                @endif
                            </div>
                            <div>
                                <strong>{{ $post->title }}</strong>
                                @if ($post->creator_name)
                                    <div class="audio-creator" style="margin-bottom: 10px">{{ $post->creator_name }}</div>
                                @endif
                                @if ($post->media_url)
                                    <audio src="{{ $post->media_url }}" controls preload="metadata"></audio>
                                @endif
                            </div>
                        </div>
                        @break

                    @default
                        @if ($post->image_url)
                            <div class="post-media is-image">
                                <img src="{{ $post->image_url }}" alt="{{ $post->title }}">
                            </div>
                        @endif
                @endswitch

                @if (trim(strip_tags((string) $post->content)) !== '')
                    <div @class(['post-content', 'is-poem' => $post->type === 'poem'])>
                        {!! \App\Support\HtmlSanitizer::clean($post->content) !!}
                    </div>
                @else
                    <div style="height: 26px"></div>
                @endif

                @if ($post->categories->isNotEmpty() || $post->tags->isNotEmpty())
                    <footer class="post-footer">
                        @foreach ($post->categories as $category)
                            <a href="{{ route('site.posts', ['category' => $category->slug]) }}" class="chip is-teal"><x-site.icon name="folder" /> {{ $category->name }}</a>
                        @endforeach
                        @foreach ($post->tags as $tag)
                            <a href="{{ route('site.posts', ['tag' => $tag->slug]) }}" class="chip"># {{ $tag->name }}</a>
                        @endforeach
                    </footer>
                @endif
            </article>

            {{-- دیدگاه‌ها --}}
            <section class="comments" id="comments">
                <h2><x-site.icon name="message" /> دیدگاه‌ها <small style="color: var(--muted); font-weight: 400">({{ $comments->count() }})</small></h2>

                @if (session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif

                @foreach ($comments as $comment)
                    <div class="comment">
                        <div class="comment-head">
                            <strong>{{ $comment->name }}</strong>
                            <span>{{ jdate($comment->created_at->copy()->setTimezone('Asia/Tehran'))->ago() }}</span>
                        </div>
                        <p>{{ $comment->body }}</p>
                        @foreach ($comment->replies as $reply)
                            <div class="reply">
                                <div class="comment-head">
                                    <strong>{{ $reply->name }} @if ($reply->isAdminReply())<span class="badge-admin">هامین</span>@endif</strong>
                                    <span>{{ jdate($reply->created_at->copy()->setTimezone('Asia/Tehran'))->ago() }}</span>
                                </div>
                                <p>{{ $reply->body }}</p>
                            </div>
                        @endforeach
                    </div>
                @endforeach

                <form action="{{ route('site.comments.store', $post) }}" method="post" class="form-box" style="margin-top: 18px">
                    @csrf
                    <h3 style="font-size: 16px">دیدگاه خود را بنویسید</h3>
                    {{-- فیلد مخفی ضد ربات --}}
                    <input type="text" name="website" tabindex="-1" autocomplete="off" class="sr-only" aria-hidden="true">

                    <div class="form-row">
                        <div class="field">
                            <label for="c-name">نام</label>
                            <input type="text" id="c-name" name="name" class="input @error('name') is-invalid @enderror"
                                   value="{{ old('name', auth()->check() ? auth()->user()->first_name.' '.auth()->user()->last_name : '') }}" required maxlength="100">
                            @error('name') <div class="error">{{ $message }}</div> @enderror
                        </div>
                        <div class="field">
                            <label for="c-contact">ایمیل یا شماره تماس <small style="color: var(--muted)">(نمایش داده نمی‌شود)</small></label>
                            <input type="text" id="c-contact" name="contact" class="input input-ltr @error('contact') is-invalid @enderror"
                                   value="{{ old('contact', auth()->user()?->email ?? auth()->user()?->mobile) }}" required maxlength="150">
                            @error('contact') <div class="error">{{ $message }}</div> @enderror
                        </div>
                    </div>
                    <div class="field">
                        <label for="c-body">متن دیدگاه</label>
                        <textarea id="c-body" name="body" class="input @error('body') is-invalid @enderror" required maxlength="2000">{{ old('body') }}</textarea>
                        @error('body') <div class="error">{{ $message }}</div> @enderror
                    </div>
                    <button type="submit" class="btn btn-primary"><x-site.icon name="send" /> ارسال دیدگاه</button>
                </form>
            </section>
        </div>

        <aside>
            @if ($post->user && $isArtist)
                <div class="sidebar-box">
                    <h3>هنرمند</h3>
                    <a href="{{ route('site.artist', $post->user->username) }}" class="author-box">
                        <img src="{{ $post->user->avatar_url }}" alt="">
                        <div>
                            <div class="name">{{ $post->user->first_name }} {{ $post->user->last_name }}</div>
                            <div class="sub">{{ $post->user->artSpecialties->pluck('name')->join('، ') }}</div>
                        </div>
                    </a>
                </div>
            @endif

            @if ($related->isNotEmpty())
                <div class="sidebar-box">
                    <h3>بیشتر از {{ $cfg['label'] }}</h3>
                    <ul class="mini-list">
                        @foreach ($related as $item)
                            <li>
                                <a href="{{ route('site.post', $item) }}" class="mini-item">
                                    <span class="thumb">
                                        @if ($item->image_url || ($item->type === 'visual' && $item->media_url))
                                            <img src="{{ $item->image_url ?? $item->media_url }}" alt="" loading="lazy">
                                        @else
                                            <span class="placeholder"><x-site.icon :name="$item->typeConfig()['icon']" /></span>
                                        @endif
                                    </span>
                                    <span>
                                        <span class="title">{{ $item->title }}</span><br>
                                        <span class="sub">{{ $item->creator_name ?: jdate($item->published_at->copy()->setTimezone('Asia/Tehran'))->format('j F Y') }}</span>
                                    </span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </aside>
    </div>
</div>
@endsection
