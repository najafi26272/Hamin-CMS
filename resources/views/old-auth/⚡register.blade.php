<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

// ثبت‌نام عمومی: فقط نام، نام خانوادگی، شماره موبایل و رمز (ورود هم با موبایل و رمز است).
// کاربر بلافاصله وارد پنل می‌شود و در صورت تمایل از داخل پنل درخواست هنرمندی می‌دهد.
new #[Layout('layouts::site', ['title' => 'عضویت'])] class extends Component
{
    public string $first_name = '';
    public string $last_name = '';
    public string $mobile = '';
    public string $password = '';
    public string $password_confirmation = '';

    protected function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'mobile' => ['required', 'regex:/^09\d{9}$/', Rule::unique('users', 'mobile')],
            'password' => ['required', 'min:8', 'confirmed'],
        ];
    }

    protected function messages(): array
    {
        return [
            'required' => ':attribute را وارد کنید.',
            'max' => ':attribute نباید بیشتر از :max کاراکتر باشد.',
            'min' => ':attribute باید حداقل :min کاراکتر باشد.',
            'mobile.unique' => 'با این شماره موبایل قبلا ثبت‌نام شده است. از صفحه ورود وارد شوید.',
            'mobile.regex' => 'شماره موبایل باید ۱۱ رقم باشد و با ۰۹ شروع شود.',
            'password.min' => 'رمز عبور باید حداقل ۸ کاراکتر باشد.',
            'password.confirmed' => 'رمز عبور و تکرار آن یکسان نیستند.',
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'first_name' => 'نام',
            'last_name' => 'نام خانوادگی',
            'mobile' => 'شماره موبایل',
            'password' => 'رمز عبور',
        ];
    }

    public function register()
    {
        $this->mobile = User::normalizeMobile(trim($this->mobile));

        $this->validate();

        // کاربر عادی: بدون نقش و بدون درخواست هنرمندی (artist_status = null)
        $user = User::create([
            'first_name' => trim($this->first_name),
            'last_name' => trim($this->last_name),
            'mobile' => $this->mobile,
            // نام کاربری تصادفی (در آدرس صفحه هنرمند استفاده می‌شود و مدیر می‌تواند آن را تغییر دهد)
            'username' => $this->uniqueUsername(),
            'password' => $this->password,
        ]);

        Auth::login($user);
        session()->regenerate();
        session()->flash('success', 'ثبت‌نام شما با موفقیت انجام شد. خوش آمدید!');

        return $this->redirectRoute('dashboard');
    }

    private function uniqueUsername(): string
    {
        do {
            $username = 'user_'.Str::lower(Str::random(8));
        } while (User::where('username', $username)->exists());

        return $username;
    }
};
?>

<div class="container auth-wrap">
    <div class="auth-card">
        <div class="auth-form">
            <h1>عضویت در هامین</h1>
            <p class="lead">هنرمندان پس از عضویت می‌توانند از پنل کاربری درخواست هنرمندی بدهند.</p>

            <form wire:submit="register" novalidate>
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

                <div class="form-row">
                    <div class="field">
                        <label for="password">رمز عبور</label>
                        <input type="password" id="password" wire:model="password" dir="ltr" autocomplete="new-password"
                               class="input input-ltr @error('password') is-invalid @enderror" placeholder="حداقل ۸ کاراکتر">
                        @error('password') <div class="error">{{ $message }}</div> @enderror
                    </div>

                    <div class="field">
                        <label for="password_confirmation">تکرار رمز عبور</label>
                        <input type="password" id="password_confirmation" wire:model="password_confirmation" dir="ltr" autocomplete="new-password"
                               class="input input-ltr">
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-block" wire:loading.attr="disabled" wire:target="register">
                    <span wire:loading.remove wire:target="register">عضویت</span>
                    <span wire:loading wire:target="register">در حال ثبت...</span>
                </button>
            </form>

            <p class="auth-switch">قبلا عضو شده‌اید؟ <a href="{{ route('login') }}">وارد شوید</a></p>
        </div>

        <div class="auth-side">
            <h2>به هامین خوش آمدید</h2>
            <p>آثار هنرمندان را دنبال کنید، دیدگاه بنویسید و اگر هنرمندید، آثارتان را با ما به اشتراک بگذارید.</p>
        </div>
    </div>
</div>
