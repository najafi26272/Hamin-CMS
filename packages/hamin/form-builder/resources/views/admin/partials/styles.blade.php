{{-- استایل‌های پنل فرم‌ساز (بر پایه Bootstrap 5) --}}
@once
<style>
    .fb-field { overflow: hidden; }
    .fb-field-head { display: flex; align-items: center; gap: 10px; padding: 10px 14px; cursor: pointer; flex-wrap: wrap; }
    .fb-field-head:hover { background: rgba(101, 113, 255, .05); }
    .fb-field.is-open { box-shadow: 0 0 0 2px rgba(101, 113, 255, .35); }
    .fb-field.is-profile .fb-field-head { background: rgba(13, 202, 240, .06); }
    .fb-field-title { font-weight: 600; }
    .fb-palette { position: sticky; top: 80px; }
    .fb-palette-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 6px; }
    .fb-palette-grid .btn { text-align: right; }
    .fb-ext-list { display: flex; flex-wrap: wrap; gap: 6px; }
    .fb-ext { display: inline-flex; align-items: center; gap: 4px; padding: 3px 10px; border: 1px solid var(--bs-border-color); border-radius: 20px; font-size: 13px; cursor: pointer; }
    .fb-ext:has(input:checked) { background: rgba(101, 113, 255, .12); border-color: rgba(101, 113, 255, .5); }
    .fb-sub-value { white-space: pre-line; }
</style>
@endonce
