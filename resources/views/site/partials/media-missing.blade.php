{{-- جای خالی فایل اصلی اثر — $icon، $text، $background (پوستر، اختیاری) --}}
<div class="media-missing" @if (! empty($background)) style="background-image: url('{{ $background }}')" @endif>
    <div class="media-missing-inner">
        <x-site.icon :name="$icon" />
        <span>{{ $text }}</span>
    </div>
</div>
