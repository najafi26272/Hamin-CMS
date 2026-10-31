<?php

use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts::login')] class extends Component
{
    public string $username = '';
    public string $password = '';
    public bool $remember = false;

    protected function rules(): array
    {
        return [
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ];
    }

    protected function messages(): array
    {
        return [
            'username.required' => 'نام کاربری را وارد کنید.',
            'password.required' => 'رمز عبور را وارد کنید.',
        ];
    }

    public function login()
    {
        $this->validate();
        $this->ensureIsNotRateLimited();

        $credentials = [
            'username' => $this->username,
            'password' => $this->password,
        ];

        if (! Auth::attempt($credentials, $this->remember)) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'username' => 'نام کاربری یا رمز عبور اشتباه است.',
            ]);
        }

        RateLimiter::clear($this->throttleKey());
        session()->regenerate();

        return $this->redirectIntended(route('dashboard'));
    }

    protected function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout(request()));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'username' => "تعداد تلاش‌های ناموفق زیاد است. {$seconds} ثانیه دیگر دوباره تلاش کنید.",
        ]);
    }

    protected function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->username).'|'.request()->ip());
    }
};
?>

<form wire:submit="login" class="forms-sample" novalidate>
    <div class="mb-3">
        <label for="username" class="form-label">نام کاربری</label>
        <input type="text"
               wire:model="username"
               class="form-control @error('username') is-invalid @enderror"
               id="username"
               autocomplete="username"
               placeholder="نام کاربری"
               autofocus>
        @error('username')
        <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="mb-3">
        <label for="password" class="form-label">رمز عبور</label>
        <input type="password"
               wire:model="password"
               class="form-control @error('password') is-invalid @enderror"
               id="password"
               autocomplete="current-password"
               placeholder="رمز عبور">
        @error('password')
        <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="form-check mb-3">
        <input type="checkbox"
               wire:model="remember"
               class="form-check-input"
               id="remember">
        <label class="form-check-label" for="remember">
            مرا به خاطر بسپار
        </label>
    </div>

    <div>
        <button type="submit"
                wire:loading.attr="disabled"
                class="btn btn-outline-primary btn-icon-text mb-2 mb-md-0">
            <span wire:ignore>
                <i class="btn-icon-prepend" data-feather="log-in"></i>
            </span>
            <span wire:loading.remove wire:target="login">ورود</span>
            <span wire:loading wire:target="login">در حال ورود...</span>
        </button>
    </div>

    <a href="#" class="d-block mt-3 text-muted">حساب کاربری ندارید؟ ثبت نام کنید</a>
</form>
