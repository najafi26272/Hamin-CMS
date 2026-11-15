{{-- آیکن شبکه‌های اجتماعی فعال (از پنل: ظاهر سایت ← شبکه‌های اجتماعی) --}}
@php
    $socialLinks = \App\Models\SocialLink::visible()->get();
@endphp
@if ($socialLinks->isNotEmpty())
    <ul class="social-links" aria-label="شبکه‌های اجتماعی">
        @foreach ($socialLinks as $social)
            <li>
                <a href="{{ $social->url }}" target="_blank" rel="noopener" title="{{ $social->label }}"
                   aria-label="هامین در {{ $social->label }}" style="--brand: {{ $social->color }}">
                    @if ($social->icon_url)
                        <img src="{{ $social->icon_url }}" alt="" loading="lazy">
                    @else
                        <x-site.icon :name="'social-'.$social->platform" />
                    @endif
                </a>
            </li>
        @endforeach
    </ul>
@endif
