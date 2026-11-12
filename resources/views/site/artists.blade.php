@extends('layouts.site')

@section('title', 'هنرمندان')

@section('content')
<div class="container page">
    <div class="page-head">
        <div>
            <h1>هنرمندان</h1>
            <p>هنرمندان عضو مجمع هنری هامین</p>
        </div>
    </div>

    @if ($artists->isEmpty())
        <div class="empty">
            <x-site.icon name="users" />
            <div>هنوز هنرمندی معرفی نشده است.</div>
        </div>
    @else
        <div class="grid grid-4">
            @foreach ($artists as $artist)
                @include('site.partials.artist-card')
            @endforeach
        </div>

        {{ $artists->links('site.partials.pagination') }}
    @endif
</div>
@endsection
