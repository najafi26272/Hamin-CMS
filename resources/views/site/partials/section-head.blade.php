{{-- سربرگ قرصی هر بخش صفحه اصلی — $title، $type --}}
<div class="section-head">
    <h2>{{ $title }}</h2>
    <a href="{{ route('site.posts', ['type' => $type]) }}" class="more"><span>مشاهده همه</span> <x-site.icon name="chevron-left" /></a>
</div>
