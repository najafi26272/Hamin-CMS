{{-- صفحه‌بندی ساده سایت: ->links('site.partials.pagination') --}}
@if ($paginator->hasPages())
    <nav aria-label="صفحه‌بندی">
        <ul class="pagination">
            @if ($paginator->onFirstPage())
                <li class="disabled"><span><x-site.icon name="chevron-right" /></span></li>
            @else
                <li><a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="قبلی"><x-site.icon name="chevron-right" /></a></li>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <li class="disabled"><span>{{ $element }}</span></li>
                @endif
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <li class="active" aria-current="page"><span>{{ $page }}</span></li>
                        @else
                            <li><a href="{{ $url }}">{{ $page }}</a></li>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <li><a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="بعدی"><x-site.icon name="chevron-left" /></a></li>
            @else
                <li class="disabled"><span><x-site.icon name="chevron-left" /></span></li>
            @endif
        </ul>
    </nav>
@endif
