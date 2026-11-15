@extends('layouts.site')

@section('title', $post->title)
@section('description', $post->excerpt(160) ?: $post->title)

@push('head')
    <meta property="og:title" content="{{ $post->title }}">
    <meta property="og:type" content="{{ $post->type === 'video' ? 'video.other' : ($post->type === 'audio' ? 'music.song' : 'article') }}">
    <meta property="og:url" content="{{ route('site.post', $post) }}">
    @if ($cover)
        <meta property="og:image" content="{{ $cover }}">
    @endif
@endpush

@php
    $cfg = $post->typeConfig();
    $isArtist = $post->user?->artist_status === 'approved';
    $hasContent = trim(strip_tags((string) $post->content)) !== '';
    $published = jdate($post->published_at->copy()->setTimezone('Asia/Tehran'));
    $shareUrl = route('site.post', $post);
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
            <article class="post-article post-{{ $post->type }}">
                <header class="post-header">
                    <span class="post-type"><x-site.icon :name="$cfg['icon']" /> {{ $cfg['label'] }}</span>
                    <h1 class="post-title">{{ $post->title }}</h1>
                    <div class="post-meta">
                        @if ($post->creator_name)
                            <span><x-site.icon name="user" /> {{ $cfg['creator'] ?? 'صاحب اثر' }}: <strong>{{ $post->creator_name }}</strong></span>
                        @endif
                        <span><x-site.icon name="calendar" /> {{ $published->format('j F Y') }}</span>
                        <span><x-site.icon name="eye" /> {{ number_format($post->views_count) }} بازدید</span>
                    </div>
                </header>

                {{-- ================= محتوای اصلی بر اساس نوع ================= --}}
                @switch($post->type)

                    {{-- لوح: تصویر اصلی اثر با امکان مشاهده در اندازه کامل --}}
                    @case('visual')
                        @php
                            $artwork = $media ? $media['url'] : $post->image_url;
                        @endphp
                        @if ($artwork)
                            <figure class="post-media is-image">
                                <a href="{{ $artwork }}" target="_blank" rel="noopener" title="نمایش در اندازه اصلی">
                                    <img src="{{ $artwork }}" alt="{{ $post->title }}{{ $post->creator_name ? ' — اثر '.$post->creator_name : '' }}">
                                </a>
                                <figcaption>برای دیدن تصویر در اندازه اصلی روی آن کلیک کنید.</figcaption>
                            </figure>
                        @else
                            @include('site.partials.media-missing', ['icon' => 'image', 'text' => 'تصویر این اثر هنوز بارگذاری نشده است.'])
                        @endif
                        @break

                    {{-- شنیداری: پخش‌کننده با کاور، موج صدا، زمان و کنترل صدا --}}
                    @case('audio')
                        <div class="audio-player" data-audio-card>
                            <div class="audio-player-cover">
                                @if ($cover)
                                    <img src="{{ $cover }}" alt="{{ $post->title }}">
                                @else
                                    <span class="placeholder"><x-site.icon name="music" /></span>
                                @endif
                            </div>
                            <div class="audio-player-body">
                                <div class="audio-player-title">{{ $post->title }}</div>
                                @if ($post->creator_name)
                                    <div class="audio-creator">{{ $post->creator_name }}</div>
                                @endif

                                @if ($media)
                                    <div class="audio-player-controls">
                                        <button type="button" class="audio-play" aria-label="پخش و توقف">
                                            <x-site.icon name="play" class="icon i-play" />
                                            <x-site.icon name="pause" class="icon i-pause" />
                                        </button>
                                        @include('site.partials.wave', ['seed' => $post->id, 'bars' => 80])
                                    </div>
                                    <div class="audio-player-time">
                                        <span class="audio-current">0:00</span>
                                        <span class="audio-time">0:00</span>
                                    </div>
                                    <audio src="{{ $media['url'] }}" preload="metadata"></audio>
                                @else
                                    <p class="media-missing-inline"><x-site.icon name="music" /> فایل صوتی این اثر هنوز بارگذاری نشده است.</p>
                                @endif
                            </div>
                        </div>
                        @break

                    {{-- تماشایی: پخش‌کننده ویدیو با پوستر --}}
                    @case('video')
                        @if ($media)
                            <div class="post-media is-video">
                                <video src="{{ $media['url'] }}" controls preload="metadata" playsinline controlslist="nodownload" @if ($cover) poster="{{ $cover }}" @endif>
                                    مرورگر شما از پخش ویدیو پشتیبانی نمی‌کند.
                                </video>
                            </div>
                        @else
                            @include('site.partials.media-missing', ['icon' => 'film', 'text' => 'فایل ویدیوی این اثر هنوز بارگذاری نشده است.', 'background' => $cover])
                        @endif
                        @break

                    {{-- شعر و اخبار: تصویر شاخص (در شعر کوچک‌تر و کنار متن) --}}
                    @default
                        @if ($cover)
                            <figure @class(['post-media is-image', 'is-poster' => $post->type === 'poem'])>
                                <img src="{{ $cover }}" alt="{{ $post->title }}">
                            </figure>
                        @endif
                @endswitch

                {{-- ================= متن ================= --}}
                @if ($hasContent)
                    <section @class(['post-content', 'is-poem' => $post->type === 'poem' || ($post->type === 'audio' && $looksLikePoem)])>
                        @if ($post->type !== 'news')
                            <h2 class="content-label">{{ $post->type === 'audio' && $looksLikePoem ? 'متن شعر' : $cfg['content_label'] }}</h2>
                        @endif
                        {!! \App\Support\HtmlSanitizer::clean($post->content) !!}
                        @if ($post->type === 'poem' && $post->creator_name)
                            <p class="poem-signature">— {{ $post->creator_name }}</p>
                        @endif
                    </section>
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

            {{-- ================= دیدگاه‌ها ================= --}}
            <section class="comments" id="comments">
                <h2><x-site.icon name="message" /> دیدگاه‌ها <small style="color: var(--muted); font-weight: 400">({{ number_format($comments->count()) }})</small></h2>

                @if (session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif

                @forelse ($comments as $comment)
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
                @empty
                    <p style="color: var(--muted)">هنوز دیدگاهی ثبت نشده است. اولین نفر باشید!</p>
                @endforelse

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

        {{-- ================= ستون کناری ================= --}}
        <aside>
            {{-- مشخصات اثر --}}
            <div class="sidebar-box">
                <h3>مشخصات {{ $post->type === 'news' ? 'خبر' : 'اثر' }}</h3>
                <dl class="facts">
                    <div><dt>نوع</dt><dd>{{ $cfg['label'] }}</dd></div>
                    @if ($post->creator_name)
                        <div><dt>{{ $cfg['creator'] ?? 'صاحب اثر' }}</dt><dd>{{ $post->creator_name }}</dd></div>
                    @endif
                    @if ($media)
                        @if (in_array($post->type, ['audio', 'video'], true))
                            <div><dt>مدت</dt><dd data-media-duration>—</dd></div>
                        @endif
                        @if ($media['dimensions'])
                            <div><dt>ابعاد</dt><dd>{{ $media['dimensions'] }}</dd></div>
                        @endif
                        <div><dt>قالب فایل</dt><dd>{{ $media['format'] }}</dd></div>
                        @if ($media['size'])
                            <div><dt>حجم</dt><dd>{{ $media['size'] }}</dd></div>
                        @endif
                    @endif
                    <div><dt>تاریخ انتشار</dt><dd>{{ $published->format('j F Y') }}</dd></div>
                    <div><dt>بازدید</dt><dd>{{ number_format($post->views_count) }}</dd></div>
                    <div><dt>دیدگاه</dt><dd>{{ number_format($comments->count()) }}</dd></div>
                </dl>
            </div>

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

            {{-- اشتراک‌گذاری --}}
            <div class="sidebar-box">
                <h3>اشتراک‌گذاری</h3>
                <div class="share">
                    <a href="https://t.me/share/url?url={{ urlencode($shareUrl) }}&text={{ urlencode($post->title) }}" target="_blank" rel="noopener" title="تلگرام" style="--brand: #229ed9"><x-site.icon name="social-telegram" /></a>
                    <a href="https://wa.me/?text={{ urlencode($post->title.' '.$shareUrl) }}" target="_blank" rel="noopener" title="واتساپ" style="--brand: #25d366"><x-site.icon name="social-whatsapp" /></a>
                    <button type="button" data-copy="{{ $shareUrl }}" title="کپی لینک"><x-site.icon name="link" /> <span>کپی لینک</span></button>
                </div>
            </div>

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
