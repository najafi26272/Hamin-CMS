{{-- کارت هنرمند — $artist --}}
<a href="{{ route('site.artist', $artist->username) }}" class="card artist-card">
    <img src="{{ $artist->avatar_url }}" alt="{{ $artist->first_name }} {{ $artist->last_name }}" loading="lazy">
    <div class="name">{{ $artist->first_name }} {{ $artist->last_name }}</div>
    @if ($artist->relationLoaded('artSpecialties') && $artist->artSpecialties->isNotEmpty())
        <div class="skills">{{ $artist->artSpecialties->pluck('name')->join('، ') }}</div>
    @endif
    @isset($artist->posts_count)
        <span class="count">{{ number_format($artist->posts_count) }} اثر</span>
    @endisset
</a>
