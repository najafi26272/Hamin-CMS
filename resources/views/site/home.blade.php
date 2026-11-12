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
                    <img src="{{ asset('assets/front/images/icon.png') }}" alt="">
                    <h1 style="margin: 0; font-size: 22px">مجمع هنری هامین</h1>
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

    {{-- لوح --}}
    @if ($visuals->isNotEmpty())
        <section class="section">
            @include('site.partials.section-head', ['title' => 'لوح', 'type' => 'visual'])
            <div class="grid grid-4">
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
