{{--
    نمایش فرم در سایت. رنگ‌ها با متغیرهای CSS قابل تغییرند، مثلا:
    .fb-form { --fb-accent: #ff6600; --fb-radius: 12px; }
--}}
<div class="fb-form" @if ($form->is_active || $preview) id="form-{{ $form->slug }}" @endif>
    @include('form-builder::form.styles')

    @if ($preview && ! $form->is_active)
        <div class="fb-alert fb-alert-warning">پیش‌نمایش: این فرم غیرفعال است و فقط مدیران آن را می‌بینند.</div>
    @endif

    @if ($submitted)
        <div class="fb-success">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            <p>{{ $form->success_text }}</p>
            @if ($form->allow_multiple)
                <button type="button" class="fb-btn fb-btn-outline" wire:click="again">ثبت پاسخ دیگر</button>
            @endif
        </div>
    @elseif ($needsLogin)
        <div class="fb-notice">
            <p>برای تکمیل این فرم ابتدا وارد حساب کاربری خود شوید.</p>
            @if (Route::has(config('form-builder.public.login_route')))
                <a href="{{ route(config('form-builder.public.login_route')) }}?redirect={{ urlencode(request()->fullUrl()) }}" class="fb-btn">ورود به حساب</a>
            @endif
        </div>
    @elseif ($denial)
        <div class="fb-notice"><p>{{ $denial }}</p></div>
    @else
        @if ($missing)
            <div class="fb-alert fb-alert-warning">
                این اطلاعات در پروفایل شما ثبت نشده و برای این فرم لازم است: <strong>{{ implode('، ', $missing) }}</strong>.
                @if (config('form-builder.public.profile_url'))
                    <a href="{{ url(config('form-builder.public.profile_url')) }}">تکمیل پروفایل</a>
                @endif
            </div>
        @endif

        <form wire:submit="submit" novalidate>
            @error('form') <div class="fb-alert fb-alert-danger">{{ $message }}</div> @enderror
            @if ($errors->any() && ! $errors->has('form'))
                <div class="fb-alert fb-alert-danger">لطفا موارد مشخص‌شده را بررسی کنید.</div>
            @endif

            <div class="fb-grid">
                @foreach ($form->fields as $field)
                    @include('form-builder::form.field', ['field' => $field])
                @endforeach
            </div>

            <div class="fb-actions">
                <button type="submit" class="fb-btn" wire:loading.attr="disabled" wire:target="submit, files">
                    <span wire:loading.remove wire:target="submit">{{ $form->submit_text }}</span>
                    <span wire:loading wire:target="submit">در حال ثبت...</span>
                </button>
                <span class="fb-hint" wire:loading wire:target="files">در حال آپلود فایل...</span>
            </div>
        </form>
    @endif
</div>
