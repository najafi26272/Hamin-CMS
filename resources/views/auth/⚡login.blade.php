<?php

use App\Models\Setting;
use App\Models\User;
use App\Services\OtpException;
use App\Services\OtpService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

// ورود فقط با کد پیامکی (کاوه‌نگار): ۱) شماره موبایل  ۲) کد تأیید
new #[Layout('layouts::site', ['title' => 'ورود'])] class extends Component
{
    public string $mobile = '';
    public string $code = '';
    public bool $remember = false;

    public int $step = 1;
    public int $resendIn = 0;
    public int $resendCount = 0;

    protected function rules(): array
    {
        return [
            'mobile' => ['required', 'string'],
            'code' => $this->step === 2 ? ['required'] : ['nullable'],
        ];
    }

    protected function messages(): array
    {
        return [
            'mobile.required' => 'شماره موبایل را وارد کنید.',
            'code.required' => 'کد تأیید را وارد کنید.',
        ];
    }

    // مرحله ۱: ارسال کد
    public function sendCode(OtpService $otp)
    {
        $this->step = 1;
        $this->mobile = User::normalizeMobile(trim($this->mobile));

        $this->validate();

        if (! preg_match('/^09\d{9}$/', $this->mobile)) {
            $this->addError('mobile', 'شماره موبایل باید ۱۱ رقم باشد و با ۰۹ شروع شود.');

            return;
        }

        $user = User::where('mobile', $this->mobile)->first();

        if (! $user) {
            $this->addError('mobile', 'با این شماره موبایل ثبت‌نام نشده است. ابتدا عضو شوید.');

            return;
        }

        try {
            $otp->send($this->mobile, 'login');
        } catch (OtpException $e) {
            $this->addError('mobile', $e->getMessage());

            return;
        }

        $this->code = '';
        $this->step = 2;
        $this->startTimer();
    }

    public function resend(OtpService $otp)
    {
        if ($this->step !== 2) {
            return;
        }

        try {
            $otp->send($this->mobile, 'login');
        } catch (OtpException $e) {
            $this->addError('code', $e->getMessage());

            return;
        }

        $this->code = '';
        $this->startTimer();
    }

    public function editMobile(): void
    {
        $this->step = 1;
        $this->code = '';
        $this->resetErrorBag();
    }

    // مرحله ۲: بررسی کد و ورود
    public function login(OtpService $otp)
    {
        if ($this->step !== 2) {
            return $this->sendCode($otp);
        }

        $this->mobile = User::normalizeMobile(trim($this->mobile));
        $this->validate();

        try {
            $otp->verify($this->mobile, 'login', $this->code);
        } catch (OtpException $e) {
            $this->addError('code', $e->getMessage());

            return;
        }

        $user = User::where('mobile', $this->mobile)->first();

        if (! $user) {
            $this->addError('code', 'با این شماره موبایل ثبت‌نام نشده است.');

            return;
        }

        // حالت توسعه: فقط مدیر اصلی و نویسندگان
        if (Setting::maintenance() && ! $user->canBypassMaintenance()) {
            throw ValidationException::withMessages([
                'code' => 'سایت در حال توسعه است؛ در این مدت فقط مدیران و نویسندگان می‌توانند وارد شوند.',
            ]);
        }

        if (! $user->mobile_verified_at) {
            $user->forceFill(['mobile_verified_at' => now()])->save();
        }

        Auth::login($user, $this->remember);
        session()->regenerate();

        return $this->redirectIntended(route('dashboard'));
    }

    private function startTimer(): void
    {
        $this->resendIn = OtpService::COOLDOWN;
        $this->resendCount++;
    }
};
?>

<div class="container auth-wrap">
    <div class="auth-card">
        <div class="auth-form">
            <h1>ورود به حساب</h1>
            <p class="lead">
                @if ($step === 1)
                    خوش آمدید! شماره موبایل خود را وارد کنید تا کد ورود برایتان پیامک شود.
                @else
                    کد ۵ رقمی ارسال‌شده به شماره <span dir="ltr">{{ $mobile }}</span> را وارد کنید.
                @endif
            </p>

            <form wire:submit="login" novalidate>
                @if (session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif
                @if (session('error'))
                    <div class="alert alert-danger">{{ session('error') }}</div>
                @elseif (\App\Models\Setting::maintenance())
                    <div class="alert alert-info">سایت در حال توسعه است؛ در این مدت فقط مدیران و نویسندگان می‌توانند وارد شوند.</div>
                @endif

                @if ($step === 1)
                    <div class="field">
                        <label for="mobile">شماره موبایل</label>
                        <input type="tel" id="mobile" wire:model="mobile" dir="ltr" inputmode="numeric" autocomplete="tel" maxlength="14" autofocus
                               class="input input-ltr @error('mobile') is-invalid @enderror" placeholder="09123456789">
                        @error('mobile') <div class="error">{{ $message }}</div> @enderror
                    </div>

                    <button type="button" wire:click="sendCode" class="btn btn-primary btn-block" wire:loading.attr="disabled" wire:target="sendCode">
                        <span wire:loading.remove wire:target="sendCode">دریافت کد ورود</span>
                        <span wire:loading wire:target="sendCode">در حال ارسال پیامک...</span>
                    </button>
                @else
                    <div class="field">
                        <label for="code">کد تأیید</label>
                        <input type="text" id="code" wire:model="code" dir="ltr" inputmode="numeric" autocomplete="one-time-code" maxlength="8" autofocus
                               class="input input-ltr @error('code') is-invalid @enderror" placeholder="12345">
                        @error('code') <div class="error">{{ $message }}</div> @enderror
                    </div>

                    <button type="submit" class="btn btn-primary btn-block" wire:loading.attr="disabled" wire:target="login">
                        <span wire:loading.remove wire:target="login">ورود</span>
                        <span wire:loading wire:target="login">در حال ورود...</span>
                    </button>

                    <div class="field" wire:key="resend-{{ $resendCount }}"
                         x-data="{ s: {{ $resendIn }} }" x-init="setInterval(() => { if (s > 0) s-- }, 1000)">
                        <p class="auth-switch" x-show="s > 0">ارسال دوباره‌ی کد تا <span x-text="s"></span> ثانیه دیگر</p>
                        <p class="auth-switch" x-show="s <= 0" x-cloak>
                            <a href="#" wire:click.prevent="resend">ارسال دوباره‌ی کد</a>
                            ·
                            <a href="#" wire:click.prevent="editMobile">ویرایش شماره</a>
                        </p>
                    </div>
                @endif
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
