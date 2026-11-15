{{--
    محتوای صفحه عمومی فرم. برای ظاهر دلخواه این فایل را publish و ویرایش کنید:
    php artisan vendor:publish --tag=form-builder-views
--}}
<div class="fb-page">
    <div class="fb-page-card">
        <h1 class="fb-page-title">{{ $form->title }}</h1>
        @if ($form->description)
            <div class="fb-page-desc">{!! nl2br(e($form->description)) !!}</div>
        @endif

        <livewire:form-builder.form :slug="$form->slug" :preview="$preview" />
    </div>
</div>

@once
<style>
    .fb-page { max-width: 780px; margin: 32px auto; padding: 0 16px; }
    .fb-page-card { background: var(--fb-page-bg, #fff); border: 1px solid var(--fb-page-border, #e7e2da); border-radius: var(--fb-page-radius, 20px); padding: 30px; }
    .fb-page-title { font-size: clamp(20px, 3vw, 26px); margin: 0 0 8px; }
    .fb-page-desc { color: var(--fb-page-muted, #6b7280); line-height: 2; margin-bottom: 24px; }
    @media (max-width: 600px) { .fb-page-card { padding: 20px 16px; } }
</style>
@endonce
