<?php

use App\Models\Setting;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts::site', ['title' => 'ورود'])] class extends Component
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

        // حالت توسعه: فقط مدیر اصلی و نویسندگان
        if (Setting::maintenance() && ! Auth::user()->canBypassMaintenance()) {
            Auth::logout();

            throw ValidationException::withMessages([
                'username' => 'سایت در حال توسعه است؛ در این مدت فقط مدیران و نویسندگان می‌توانند وارد شوند.',
            ]);
        }

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

<div class="container auth-wrap">
    <div class="auth-card">
        <div class="auth-form">
            <h1>ورود به حساب</h1>
            <p class="lead">خوش آمدید! نام کاربری و رمز عبور خود را وارد کنید.</p>

            <form wire:submit="login" novalidate>
                @if (session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif
                @if (session('error'))
                    <div class="alert alert-danger">{{ session('error') }}</div>
                @elseif (\App\Models\Setting::maintenance())
                    <div class="alert alert-info">سایت در حال توسعه است؛ در این مدت فقط مدیران و نویسندگان می‌توانند وارد شوند.</div>
                @endif

                <div class="field">
                    <label for="username">نام کاربری</label>
                    <input type="text" id="username" wire:model="username" dir="ltr" autocomplete="username" autofocus
                           class="input input-ltr @error('username') is-invalid @enderror" placeholder="username">
                    @error('username') <div class="error">{{ $message }}</div> @enderror
                </div>

                <div class="field">
                    <label for="password">رمز عبور</label>
                    <input type="password" id="password" wire:model="password" dir="ltr" autocomplete="current-password"
                           class="input input-ltr @error('password') is-invalid @enderror" placeholder="••••••••">
                    @error('password') <div class="error">{{ $message }}</div> @enderror
                </div>

                <div class="field">
                    <label class="check"><input type="checkbox" wire:model="remember"> مرا به خاطر بسپار</label>
                </div>

                <button type="submit" class="btn btn-primary btn-block" wire:loading.attr="disabled" wire:target="login">
                    <span wire:loading.remove wire:target="login">ورود</span>
                    <span wire:loading wire:target="login">در حال ورود...</span>
                </button>
            </form>

            @unless (\App\Models\Setting::maintenance())
                <p class="auth-switch">حساب کاربری ندارید؟ <a href="{{ route('register') }}">عضو شوید</a></p>
            @endunless
        </div>

        <div class="auth-side">
            <h2>{{ \App\Models\Setting::siteTitle() }}</h2>
            <p>آثار خود را در لوح، شنیداری، تماشایی و شعر با دیگران به اشتراک بگذارید.</p>
        </div>
    </div>
</div>
