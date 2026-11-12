@extends('layouts.site')

@section('title', $artist->first_name.' '.$artist->last_name)

@section('content')
<div class="container page">
    <ol class="breadcrumb">
        <li><a href="{{ route('site.home') }}">خانه</a></li>
        <li><a href="{{ route('site.artists') }}">هنرمندان</a></li>
        <li aria-current="page">{{ $artist->first_name }} {{ $artist->last_name }}</li>
    </ol>

    <div class="profile-head">
        <img src="{{ $artist->avatar_url }}" alt="">
        <div>
            <h1>{{ $artist->first_name }} {{ $artist->last_name }}</h1>
            <div style="display: flex; flex-wrap: wrap; gap: 6px; justify-content: inherit">
                @foreach ($artist->artSpecialties as $specialty)
                    <span class="chip is-teal">{{ $specialty->name }}</span>
                @endforeach
                @if ($artist->city)
                    <span class="chip"><x-site.icon name="map-pin" /> {{ $artist->city }}</span>
                @endif
                @if ($artist->instagram_id)
                    <a href="https://instagram.com/{{ $artist->instagram_id }}" target="_blank" rel="noopener" class="chip" dir="ltr"><x-site.icon name="instagram" /> {{ $artist->instagram_id }}</a>
                @endif
            </div>
        </div>
    </div>

    <h2 style="font-size: 18px">آثار ({{ number_format($posts->total()) }})</h2>

    @if ($posts->isEmpty())
        <div class="empty">
            <x-site.icon name="feather" />
            <div>هنوز اثری از این هنرمند منتشر نشده است.</div>
        </div>
    @else
        <div class="grid grid-4">
            @foreach ($posts as $post)
                @include('site.partials.post-card', ['showType' => true])
            @endforeach
        </div>

        {{ $posts->links('site.partials.pagination') }}
    @endif
</div>
@endsection
