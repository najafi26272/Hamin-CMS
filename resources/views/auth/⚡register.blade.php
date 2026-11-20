<?php

use App\Models\User;
use App\Services\OtpException;
use App\Services\OtpService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

// ثبت‌نام دو مرحله‌ای: ۱) اطلاعات  ۲) تأیید شماره موبایل با کد پیامکی (کاوه‌نگار)
// کاربر فقط بعد از تأیید کد ساخته می‌شود.
new #[Layout('layouts::site', ['title' => 'عضویت'])] class extends Component
{
    public string $first_name = '';
    public string $last_name = '';
    public string $mobile = '';

    public int $step = 1;
    public string $code = '';
    public int $resendIn = 0;
    public int $resendCount = 0;

    protected function rules(): array
    {
        $rules = [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'mobile' => ['required', 'regex:/^09\d{9}$/', Rule::unique('users', 'mobile')],
        ];

        if ($this->step === 2) {
            $rules['code'] = ['required'];
        }

        return $rules;
    }

    protected function messages(): array
    {
        return [
            'required' => ':attribute را وارد کنید.',
            'max' => ':attribute نباید بیشتر از :max کاراکتر باشد.',
            'mobile.unique' => 'با این شماره موبایل قبلا ثبت‌نام شده است. از صفحه ورود وارد شوید.',
            'mobile.regex' => 'شماره موبایل باید ۱۱ رقم باشد و با ۰۹ شروع شود.',
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'first_name' => 'نام',
            'last_name' => 'نام خانوادگی',
            'mobile' => 'شماره موبایل',
            'code' => 'کد تأیید',
        ];
    }

    // مرحله ۱: اعتبارسنجی اطلاعات و ارسال کد
    public function sendCode(OtpService $otp)
    {
        $this->step = 1;
        $this->mobile = User::normalizeMobile(trim($this->mobile));

        $this->validate();

        try {
            $otp->send($this->mobile, 'register');
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
            $otp->send($this->mobile, 'register');
        } catch (OtpException $e) {
            $this->addError('code', $e->getMessage());

            return;
        }

        $this->code = '';
        $this->startTimer();
    }

    // برگشت به مرحله ۱ برای اصلاح شماره
    public function editMobile(): void
    {
        $this->step = 1;
        $this->code = '';
        $this->resetErrorBag();
    }

    // مرحله ۲: بررسی کد و ساخت کاربر
    public function register(OtpService $otp)
    {
        if ($this->step !== 2) {
            return $this->sendCode($otp);
        }

        $this->mobile = User::normalizeMobile(trim($this->mobile));
        $this->validate();

        try {
            $otp->verify($this->mobile, 'register', $this->code);
        } catch (OtpException $e) {
            $this->addError('code', $e->getMessage());

            return;
        }

        $user = User::create([
            'first_name' => trim($this->first_name),
            'last_name' => trim($this->last_name),
            'mobile' => $this->mobile,
            'mobile_verified_at' => now(),
            // ورود فقط با پیامک است؛ یک رمز تصادفی (هش‌شده با cast مدل) برای پر بودن ستون password
            'password' => Str::random(40),
        ]);

        Auth::login($user);
        session()->regenerate();
        session()->flash('success', 'ثبت‌نام شما با موفقیت انجام شد. خوش آمدید!');

        return $this->redirectRoute('dashboard');
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
            <h1>عضویت در هامین</h1>
            <p class="lead">
                @if ($step === 1)
                    هنرمندان پس از عضویت می‌توانند از پنل کاربری درخواست هنرمندی بدهند.
                @else
                    کد ۵ رقمی ارسال‌شده به شماره <span dir="ltr">{{ $mobile }}</span> را وارد کنید.
                @endif
            </p>

            <form wire:submit="register" novalidate>
                @if ($step === 1)
                    <div class="form-row">
                        <div class="field">
                            <label for="first_name">نام</label>
                            <input type="text" id="first_name" wire:model="first_name" autocomplete="given-name" autofocus
                                   class="input @error('first_name') is-invalid @enderror">
                            @error('first_name') <div class="error">{{ $message }}</div> @enderror
                        </div>

                        <div class="field">
                            <label for="last_name">نام خانوادگی</label>
                            <input type="text" id="last_name" wire:model="last_name" autocomplete="family-name"
                                   class="input @error('last_name') is-invalid @enderror">
                            @error('last_name') <div class="error">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="field">
                        <label for="mobile">شماره موبایل</label>
                        <input type="tel" id="mobile" wire:model="mobile" dir="ltr" inputmode="numeric" autocomplete="tel" maxlength="14"
                               class="input input-ltr @error('mobile') is-invalid @enderror" placeholder="09123456789">
                        @error('mobile') <div class="error">{{ $message }}</div> @enderror
                    </div>

                    <button type="button" wire:click="sendCode" class="btn btn-primary btn-block" wire:loading.attr="disabled" wire:target="sendCode">
                        <span wire:loading.remove wire:target="sendCode">دریافت کد تأیید</span>
                        <span wire:loading wire:target="sendCode">در حال ارسال پیامک...</span>
                    </button>
                @else
                    <div class="field">
                        <label for="code">کد تأیید</label>
                        <input type="text" id="code" wire:model="code" dir="ltr" inputmode="numeric" autocomplete="one-time-code" maxlength="8" autofocus
                               class="input input-ltr @error('code') is-invalid @enderror" placeholder="12345">
                        @error('code') <div class="error">{{ $message }}</div> @enderror
                    </div>

                    <button type="submit" class="btn btn-primary btn-block" wire:loading.attr="disabled" wire:target="register">
                        <span wire:loading.remove wire:target="register">تأیید و عضویت</span>
                        <span wire:loading wire:target="register">در حال ثبت...</span>
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

            <p class="auth-switch">قبلا عضو شده‌اید؟ <a href="{{ route('login') }}">وارد شوید</a></p>
        </div>

        <div class="auth-side">
            <h2>به هامین خوش آمدید</h2>
            <p>آثار هنرمندان را دنبال کنید، دیدگاه بنویسید و اگر هنرمندید، آثارتان را با ما به اشتراک بگذارید.</p>
        </div>
    </div>
</div>
