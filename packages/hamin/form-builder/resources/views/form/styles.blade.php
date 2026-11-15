{{-- استایل فرم عمومی؛ مستقل از فریم‌ورک CSS. با متغیرها هماهنگ با سایت شود --}}
@once
<style>
    /* :where یعنی specificity صفر؛ هر تعریف .fb-form در CSS سایت این مقادیر را جایگزین می‌کند */
    :where(.fb-form) {
        --fb-accent: #19786f;
        --fb-accent-soft: color-mix(in srgb, var(--fb-accent) 12%, transparent);
        --fb-text: inherit;
        --fb-muted: #6b7280;
        --fb-border: #e5e1da;
        --fb-bg: #fff;
        --fb-field-bg: #fff;
        --fb-locked-bg: #f5f3ef;
        --fb-danger: #d64545;
        --fb-radius: 12px;
        color: var(--fb-text);
    }
    .fb-form [x-cloak] { display: none !important; }
    .fb-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 18px 16px; }
    .fb-field { grid-column: span 2; min-width: 0; }
    .fb-field.fb-half { grid-column: span 1; }
    .fb-label { display: flex; flex-wrap: wrap; align-items: center; gap: 6px; font-size: 14px; font-weight: 600; margin-bottom: 7px; }
    .fb-required { color: var(--fb-danger); }
    .fb-lock { display: inline-flex; align-items: center; gap: 3px; font-size: 11.5px; font-weight: 500; color: var(--fb-muted); background: var(--fb-locked-bg); padding: 1px 8px; border-radius: 99px; }
    .fb-lock svg { width: 12px; height: 12px; }
    .fb-lock-open { background: var(--fb-accent-soft); color: var(--fb-accent); }

    .fb-input {
        width: 100%; box-sizing: border-box; padding: 11px 14px; border: 1.5px solid var(--fb-border); border-radius: var(--fb-radius);
        background: var(--fb-field-bg); font: inherit; font-size: 14.5px; color: inherit; outline: none; transition: border-color .15s, box-shadow .15s;
    }
    .fb-input:focus { border-color: var(--fb-accent); box-shadow: 0 0 0 3px var(--fb-accent-soft); }
    .fb-input[readonly] { background: var(--fb-locked-bg); color: var(--fb-muted); cursor: not-allowed; }
    .fb-input[readonly]:focus { box-shadow: none; border-color: var(--fb-border); }
    textarea.fb-input { resize: vertical; line-height: 1.9; }
    .fb-ltr { direction: ltr; text-align: left; }
    .fb-select { appearance: none; background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%236b7280' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: left 12px center; padding-left: 36px; }
    .fb-has-error .fb-input, .fb-has-error .fb-dropzone { border-color: var(--fb-danger); }
    .fb-hint { font-size: 12.5px; color: var(--fb-muted); margin-top: 5px; }
    .fb-error { font-size: 12.5px; color: var(--fb-danger); margin-top: 5px; }

    /* گزینه‌ها */
    .fb-options { display: flex; flex-direction: column; gap: 8px; }
    .fb-options.fb-inline { flex-direction: row; flex-wrap: wrap; gap: 8px 18px; }
    .fb-option { display: inline-flex; align-items: center; gap: 8px; cursor: pointer; font-size: 14.5px; }
    .fb-option input { width: 18px; height: 18px; accent-color: var(--fb-accent); margin: 0; flex-shrink: 0; }
    .fb-single { padding: 12px 14px; border: 1.5px solid var(--fb-border); border-radius: var(--fb-radius); width: 100%; box-sizing: border-box; }

    /* انتخاب چندگانه */
    .fb-multiselect { position: relative; }
    .fb-multiselect-toggle { display: flex; align-items: center; gap: 8px; text-align: right; cursor: pointer; min-height: 46px; }
    .fb-chips { display: flex; flex-wrap: wrap; gap: 4px; flex: 1; }
    .fb-chip { background: var(--fb-accent-soft); color: var(--fb-accent); border-radius: 99px; padding: 1px 10px; font-size: 13px; }
    .fb-placeholder { flex: 1; color: var(--fb-muted); }
    .fb-caret { width: 16px; height: 16px; color: var(--fb-muted); flex-shrink: 0; }
    .fb-multiselect-menu {
        position: absolute; z-index: 30; inset-inline: 0; top: calc(100% + 6px); max-height: 260px; overflow: auto;
        background: var(--fb-bg); border: 1px solid var(--fb-border); border-radius: var(--fb-radius); padding: 6px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, .1);
    }
    .fb-multiselect-menu .fb-option { display: flex; padding: 8px 10px; border-radius: 8px; }
    .fb-multiselect-menu .fb-option:hover { background: var(--fb-accent-soft); }

    /* آپلود فایل */
    .fb-file-input { position: absolute; width: 1px; height: 1px; opacity: 0; pointer-events: none; }
    .fb-dropzone {
        display: flex; flex-direction: column; align-items: center; gap: 4px; padding: 20px 14px; text-align: center; cursor: pointer;
        border: 1.5px dashed var(--fb-border); border-radius: var(--fb-radius); background: var(--fb-field-bg); transition: .15s;
    }
    .fb-dropzone:hover { border-color: var(--fb-accent); background: var(--fb-accent-soft); }
    .fb-dropzone svg { width: 28px; height: 28px; color: var(--fb-accent); }
    .fb-dropzone span { font-weight: 600; font-size: 14px; }
    .fb-dropzone small { font-size: 12px; color: var(--fb-muted); }
    .fb-progress { height: 6px; background: var(--fb-locked-bg); border-radius: 99px; overflow: hidden; margin-top: 8px; }
    .fb-progress span { display: block; height: 100%; background: var(--fb-accent); transition: width .2s; }
    .fb-file-list { list-style: none; padding: 0; margin: 8px 0 0; display: grid; gap: 6px; }
    .fb-file-list li { display: flex; align-items: center; gap: 10px; padding: 8px 12px; background: var(--fb-locked-bg); border-radius: 10px; font-size: 13.5px; }
    .fb-file-name { flex: 1; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; direction: ltr; text-align: right; }
    .fb-file-list .fb-hint { margin: 0; }
    .fb-file-remove { border: 0; background: none; color: var(--fb-danger); cursor: pointer; font-size: 14px; padding: 2px 6px; }

    /* دکمه‌ها و پیام‌ها */
    .fb-actions { display: flex; align-items: center; gap: 14px; margin-top: 24px; }
    .fb-btn {
        display: inline-flex; align-items: center; justify-content: center; gap: 6px; padding: 11px 28px; border: 1.5px solid var(--fb-accent);
        border-radius: 99px; background: var(--fb-accent); color: #fff; font: inherit; font-weight: 700; font-size: 15px; cursor: pointer; text-decoration: none; transition: .15s;
    }
    .fb-btn:hover { filter: brightness(.93); color: #fff; }
    .fb-btn[disabled] { opacity: .6; cursor: wait; }
    .fb-btn-outline { background: transparent; color: var(--fb-accent); }
    .fb-btn-outline:hover { background: var(--fb-accent-soft); color: var(--fb-accent); }
    .fb-alert { padding: 12px 16px; border-radius: var(--fb-radius); font-size: 14px; margin-bottom: 16px; }
    .fb-alert-danger { background: color-mix(in srgb, var(--fb-danger) 10%, transparent); color: var(--fb-danger); }
    .fb-alert-warning { background: #fff6e5; color: #8a5a00; }
    .fb-notice, .fb-success { text-align: center; padding: 34px 20px; }
    .fb-notice p, .fb-success p { margin: 0 0 16px; font-size: 15.5px; }
    .fb-success svg { width: 54px; height: 54px; color: var(--fb-accent); margin-bottom: 10px; }

    @media (max-width: 640px) {
        .fb-grid { grid-template-columns: minmax(0, 1fr); }
        .fb-field, .fb-field.fb-half { grid-column: span 1; }
        .fb-btn { width: 100%; }
        .fb-actions { flex-direction: column; align-items: stretch; }
    }
</style>
@endonce
