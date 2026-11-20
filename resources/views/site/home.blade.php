@extends('layouts.site')

@section('content')
    <div class="container">

        {{-- اسلایدر --}}
        <section class="hero">
            <div class="slider" data-slider>
                @forelse ($sliders as $slider)
                    <div @class(['slide', 'active' => $loop->first])>
                        @if ($slider->link)
                            <a href="{{ $slider->link }}" aria-label="{{ $slider->title }}">
                                @endif
                                <img src="{{ $slider->image_url }}" alt="{{ $slider->title }}" @unless($loop->first) loading="lazy" @endunless>
                                @if ($slider->title)
                                    <div class="slide-caption">{{ $slider->title }}</div>
                                @endif
                                @if ($slider->link)
                            </a>
                        @endif
                    </div>
                @empty
                    <div class="hero-fallback">
                        <img src="{{ \App\Models\Setting::faviconUrl() }}" alt="">
                        <h1 style="margin: 0; font-size: 22px">{{ \App\Models\Setting::siteTitle() }}</h1>
                        <p>خانه‌ای برای لوح، شنیداری، تماشایی و شعر</p>
                    </div>
                @endforelse

                @if ($sliders->count() > 1)
                    <button type="button" class="slider-arrow prev" aria-label="قبلی"><x-site.icon name="chevron-right" /></button>
                    <button type="button" class="slider-arrow next" aria-label="بعدی"><x-site.icon name="chevron-left" /></button>
                    <div class="slider-dots">
                        @foreach ($sliders as $slider)
                            <button type="button" @class(['active' => $loop->first]) aria-label="اسلاید {{ $loop->iteration }}"></button>
                        @endforeach
                    </div>
                @endif
            </div>
        </section>

        {{-- قالب‌های محتوایی: هر باکس به لیست همان قالب می‌رود --}}
        @php
            $typeBoxes = [
                'visual' => ['icon' => 'image', 'text' => 'عکس، نقاشی و خوشنویسی', 'tone' => 'orange'],
                'audio' => ['icon' => 'headphones', 'text' => 'موسیقی، دکلمه و آثار صوتی', 'tone' => 'teal'],
                'video' => ['icon' => 'film', 'text' => 'فیلم، کلیپ و مستند', 'tone' => 'orange'],
                'poem' => ['icon' => 'feather', 'text' => 'سروده‌ها و متن‌های ادبی', 'tone' => 'teal'],
            ];
        @endphp
        <nav class="type-boxes" aria-label="قالب‌های محتوایی">
            @foreach ($typeBoxes as $type => $box)
                <a href="{{ route('site.posts', ['type' => $type]) }}" class="type-box type-box-{{ $box['tone'] }}">
                    <span class="type-box-icon"><x-site.icon :name="$box['icon']" /></span>
                    <span class="type-box-body">
                    <strong>{{ \App\Models\Post::TYPES[$type]['label'] }}</strong>
                    <small>{{ $box['text'] }}</small>
                </span>
                    <span class="type-box-count">{{ $typeCounts[$type] ?? 0 }} اثر</span>
                    <x-site.icon name="chevron-left" class="type-box-arrow" />
                </a>
            @endforeach
        </nav>

        {{-- لوح --}}
        @if ($visuals->isNotEmpty())
            <section class="section">
                @include('site.partials.section-head', ['title' => 'لوح', 'type' => 'visual'])
                <div class="grid grid-4 grid-visual">
                    @foreach ($visuals as $post)
                        @include('site.partials.post-card')
                    @endforeach
                </div>
            </section>
        @endif

        {{-- شنیداری --}}
        @if ($audios->isNotEmpty())
            <section class="section">
                @include('site.partials.section-head', ['title' => 'شنیداری', 'type' => 'audio'])
                <div class="grid grid-2">
                    @foreach ($audios as $post)
                        @include('site.partials.audio-card')
                    @endforeach
                </div>
            </section>
        @endif

        {{-- تماشایی --}}
        @if ($videos->isNotEmpty())
            <section class="section">
                @include('site.partials.section-head', ['title' => 'تماشایی', 'type' => 'video'])
                <div class="grid grid-2">
                    @foreach ($videos as $post)
                        @include('site.partials.post-card')
                    @endforeach
                </div>
            </section>
        @endif

        {{-- شعر --}}
        @if ($poems->isNotEmpty())
            <section class="section">
                @include('site.partials.section-head', ['title' => 'شعر', 'type' => 'poem'])
                <div class="grid grid-4">
                    @foreach ($poems as $post)
                        @include('site.partials.post-card')
                    @endforeach
                </div>
            </section>
        @endif

        @if ($visuals->isEmpty() && $audios->isEmpty() && $videos->isEmpty() && $poems->isEmpty())
            <div class="section empty">
                <x-site.icon name="feather" />
                <div>هنوز اثری منتشر نشده است.</div>
            </div>
        @endif
    </div>
@endsection
