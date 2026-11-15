{{-- موج صدای تزئینی با نوار پیشرفت — $seed (ثابت برای هر مطلب)، $bars (تعداد میله‌ها) --}}
@php
    $bars = $bars ?? 56;
    mt_srand($seed * 7919);
    $wave = '';
    for ($i = 0; $i < $bars; $i++) {
        $envelope = sin(M_PI * ($i + 1) / ($bars + 1));
        $h = max(3, round((0.25 + 0.75 * $envelope) * mt_rand(25, 100) / 100 * 30));
        $wave .= '<rect x="'.($i * 5).'" y="'.((34 - $h) / 2).'" width="2.6" height="'.$h.'" rx="1.3"/>';
    }
    mt_srand();
    $width = $bars * 5;
@endphp
<div class="wave" title="جابه‌جایی در صدا">
    <svg class="wave-base" viewBox="0 0 {{ $width }} 34" preserveAspectRatio="none" fill="currentColor" aria-hidden="true">{!! $wave !!}</svg>
    <svg class="wave-fill" viewBox="0 0 {{ $width }} 34" preserveAspectRatio="none" fill="currentColor" aria-hidden="true">{!! $wave !!}</svg>
</div>
