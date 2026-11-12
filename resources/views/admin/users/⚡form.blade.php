<?php

use App\Models\ArtSpecialty;
use App\Models\Provinces;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Layout('layouts::admin')] class extends Component
{
    use WithFileUploads;

    public ?User $user = null;

    public string $first_name = '';
    public string $last_name = '';
    public ?string $birth_date = null; // میلادی (Y-m-d)؛ نمایش به صورت شمسی در تقویم
    public $avatar = null;             // فایل آپلود شده جدید
    public string $phone = '';
    public string $mobile = '';
    public string $email = '';
    public string $username = '';
    public string $city = '';
    public string $virtual_space_number = '';
    public string $instagram_id = '';
    public string $education_degree = '';
    public array $art_specialty = []; // شناسه تخصص‌های هنری
    public array $roles = []; // شناسه نقش‌های انتخاب شده
    public string $password = '';
    public string $password_confirmation = '';

    public function mount(User $user): void
    {
        if (! $user->exists) {
            return;
        }

        $this->user = $user;

        $this->first_name = $user->first_name;
        $this->last_name = $user->last_name;
        $this->birth_date = $user->birth_date?->format('Y-m-d');
        $this->phone = $user->phone ?? '';
        $this->mobile = $user->mobile ?? '';
        $this->email = $user->email ?? '';
        $this->username = $user->username ?? '';
        $this->city = $user->city ?? '';
        $this->virtual_space_number = $user->virtual_space_number ?? '';
        $this->instagram_id = $user->instagram_id ?? '';
        $this->education_degree = $user->education_degree ?? '';
        $this->art_specialty = $user->artSpecialties()->pluck('art_specialties.id')->map(fn ($id) => (string) $id)->all();
        $this->roles = $user->roles()->pluck('roles.id')->map(fn ($id) => (string) $id)->all();
    }

    #[Computed]
    public function provinces()
    {
        return Provinces::orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function allSpecialties()
    {
        return ArtSpecialty::orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function allRoles()
    {
        return Role::orderBy('id')->get(['id', 'name', 'label']);
    }

    protected function rules(): array
    {
        $id = $this->user?->id;

        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'birth_date' => ['nullable', 'date', 'before:today'],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'phone' => ['nullable', 'regex:/^0\d{10}$/'],
            'mobile' => ['nullable', 'regex:/^09\d{9}$/', Rule::unique('users', 'mobile')->ignore($id)],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($id)],
            'username' => ['required', 'min:3', 'max:50', 'regex:/^[A-Za-z0-9_.]+$/', Rule::unique('users', 'username')->ignore($id)],
            'city' => ['nullable', Rule::exists('provinces', 'name')],
            'virtual_space_number' => ['nullable', 'regex:/^09\d{9}$/'],
            'instagram_id' => ['nullable', 'max:30', 'regex:/^[A-Za-z0-9_.]+$/'],
            'education_degree' => ['nullable', Rule::in(User::EDUCATION_DEGREES)],
            'art_specialty' => ['nullable', 'array'],
            'art_specialty.*' => [Rule::exists('art_specialties', 'id')],
            'roles' => ['nullable', 'array'],
            'roles.*' => [Rule::exists('roles', 'id')],
            'password' => [$this->user ? 'nullable' : 'required', 'min:8', 'confirmed'],
        ];
    }

    protected function messages(): array
    {
        return [
            'required' => ':attribute را وارد کنید.',
            'max' => ':attribute نباید بیشتر از :max کاراکتر باشد.',
            'min' => ':attribute باید حداقل :min کاراکتر باشد.',
            'unique' => 'این :attribute قبلا ثبت شده است.',
            'email' => 'فرمت :attribute صحیح نیست.',
            'regex' => 'فرمت :attribute صحیح نیست.',
            'in' => ':attribute انتخاب شده معتبر نیست.',
            'exists' => ':attribute انتخاب شده معتبر نیست.',
            'date' => ':attribute معتبر نیست.',
            'birth_date.before' => 'تاریخ تولد باید قبل از امروز باشد.',
            'mobile.regex' => 'شماره موبایل باید ۱۱ رقم و با ۰۹ شروع شود.',
            'virtual_space_number.regex' => 'شماره فضای مجازی باید ۱۱ رقم و با ۰۹ شروع شود.',
            'phone.regex' => 'شماره تماس ثابت باید ۱۱ رقم و همراه با کد شهر باشد (مثل ۰۲۱۱۲۳۴۵۶۷۸).',
            'username.regex' => 'نام کاربری فقط می‌تواند شامل حروف انگلیسی، عدد، _ و . باشد.',
            'instagram_id.regex' => 'آیدی اینستاگرام فقط می‌تواند شامل حروف انگلیسی، عدد، _ و . باشد.',
            'avatar.image' => 'فایل انتخاب شده باید تصویر باشد.',
            'avatar.mimes' => 'فرمت تصویر باید jpg، png یا webp باشد.',
            'avatar.max' => 'حجم تصویر نباید بیشتر از ۲ مگابایت باشد.',
            'password.min' => 'رمز عبور باید حداقل ۸ کاراکتر باشد.',
            'password.confirmed' => 'رمز عبور و تکرار آن یکسان نیستند.',
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'first_name' => 'نام',
            'last_name' => 'نام خانوادگی',
            'birth_date' => 'تاریخ تولد',
            'avatar' => 'تصویر پروفایل',
            'phone' => 'شماره تماس ثابت',
            'mobile' => 'شماره موبایل',
            'email' => 'ایمیل',
            'username' => 'نام کاربری',
            'city' => 'شهر محل سکونت',
            'virtual_space_number' => 'شماره فضای مجازی',
            'instagram_id' => 'آیدی اینستاگرام',
            'education_degree' => 'مدرک تحصیلی',
            'art_specialty' => 'تخصص هنری',
            'art_specialty.*' => 'تخصص هنری',
            'roles' => 'نقش‌ها',
            'roles.*' => 'نقش',
            'password' => 'رمز عبور',
        ];
    }

    // با انتخاب تصویر، همان لحظه بررسی شود
    public function updatedAvatar(): void
    {
        $this->validateOnly('avatar');
    }

    public function save()
    {
        // ثبت کاربر جدید: مدیریت کاربران / ویرایش: ویرایش کاربران
        if (Gate::denies($this->user ? 'edit-users' : 'manage-users')) {
            $this->dispatch('notify', type: 'error', message: 'شما دسترسی لازم برای این عملیات را ندارید.');
            return null;
        }

        // تبدیل اعداد فارسی به انگلیسی و حذف @ از ابتدای آیدی اینستاگرام
        foreach (['phone', 'mobile', 'virtual_space_number'] as $field) {
            $this->$field = $this->toEnglishDigits(trim($this->$field));
        }
        $this->instagram_id = ltrim(trim($this->instagram_id), '@');

        try {
            $this->validate();
        } catch (ValidationException $e) {
            $this->dispatch('notify', type: 'error', message: 'لطفا خطاهای فرم را بررسی کنید.');
            throw $e;
        }

        $data = [
            'first_name' => trim($this->first_name),
            'last_name' => trim($this->last_name),
            'birth_date' => $this->birth_date ?: null,
            'phone' => $this->phone ?: null,
            'mobile' => $this->mobile ?: null,
            'email' => trim($this->email) ?: null,
            'username' => trim($this->username),
            'city' => $this->city ?: null,
            'virtual_space_number' => $this->virtual_space_number ?: null,
            'instagram_id' => $this->instagram_id ?: null,
            'education_degree' => $this->education_degree ?: null,
        ];

        // رمز عبور فقط در صورت وارد شدن تغییر می‌کند (هش در مدل انجام می‌شود)
        if ($this->password !== '') {
            $data['password'] = $this->password;
        }

        try {
            if ($this->avatar) {
                $data['avatar'] = $this->avatar->store('avatars', 'public');

                if ($this->user?->avatar) {
                    Storage::disk('public')->delete($this->user->avatar);
                }
            }

            if ($this->user) {
                $this->user->update($data);
                $user = $this->user;
                $message = 'اطلاعات کاربر با موفقیت ویرایش شد.';
            } else {
                $user = User::create($data);
                $message = 'کاربر جدید با موفقیت ثبت شد.';
            }

            $user->artSpecialties()->sync($this->art_specialty);

            // فقط کسی که مجوز مدیریت نقش‌ها را دارد می‌تواند نقش کاربر را تغییر دهد
            if (Gate::allows('manage-roles')) {
                $user->roles()->sync($this->roles);

                // دادن یا گرفتن دستی نقش هنرمند، وضعیت هنرمندی را هم هماهنگ می‌کند
                $hasArtistRole = $user->roles()->where('name', 'artist')->exists();

                if ($hasArtistRole && $user->artist_status !== 'approved') {
                    $user->update(['artist_status' => 'approved', 'artist_rejection_reason' => null, 'reviewed_at' => now(), 'reviewed_by' => auth()->id()]);
                } elseif (! $hasArtistRole && $user->artist_status === 'approved') {
                    $user->update(['artist_status' => null]);
                }
            }
        } catch (\Throwable $e) {
            report($e);
            $this->dispatch('notify', type: 'error', message: 'ذخیره اطلاعات با خطا مواجه شد. دوباره تلاش کنید.');
            return null;
        }

        session()->flash('success', $message);

        return $this->redirectRoute('users.index');
    }

    private function toEnglishDigits(string $value): string
    {
        return str_replace(
            ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹', '٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'],
            ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '0', '1', '2', '3', '4', '5', '6', '7', '8', '9'],
            $value
        );
    }
};
?>

<div>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0">{{ $user ? 'ویرایش کاربر: '.$user->first_name.' '.$user->last_name : 'ثبت کاربر جدید' }}</h5>

        <a href="{{ route('users.index') }}" class="btn btn-outline-secondary btn-icon-text">
            <span wire:ignore><i class="btn-icon-prepend" data-feather="arrow-right"></i></span>
            بازگشت به لیست
        </a>
    </div>

    <form wire:submit="save" novalidate>
        <div class="row">
            {{-- تصویر پروفایل --}}
            <div class="col-lg-3 mb-3">
                <div class="card h-100">
                    <div class="card-body text-center">
                        <h6 class="card-title mb-4">تصویر پروفایل</h6>

                        <div class="avatar-upload mx-auto mb-3">
                            <img class="avatar-preview"
                                 src="{{ $avatar && ! $errors->has('avatar') ? $avatar->temporaryUrl() : ($user?->avatar_url ?? asset('assets/admin/images/faces/pro.png')) }}"
                                 alt="تصویر پروفایل">

                            <div class="avatar-loading" wire:loading.flex wire:target="avatar">
                                <div class="spinner-border spinner-border-sm text-light" role="status"></div>
                            </div>

                            <label for="avatar" class="avatar-btn" title="انتخاب تصویر" wire:ignore>
                                <i data-feather="camera"></i>
                            </label>
                            <input type="file" id="avatar" wire:model="avatar" accept="image/png,image/jpeg,image/webp" class="d-none">
                        </div>

                        <p class="text-muted small mb-0">فرمت‌های مجاز: jpg، png، webp<br>حداکثر حجم: ۲ مگابایت</p>

                        @error('avatar')
                        <div class="text-danger small mt-2">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="col-lg-9">
                {{-- اطلاعات شخصی --}}
                <div class="card mb-3">
                    <div class="card-body">
                        <h6 class="card-title mb-4">اطلاعات شخصی</h6>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="first_name" class="form-label">نام <span class="text-danger">*</span></label>
                                <div class="input-group has-validation">
                                    <span class="input-group-text" wire:ignore><i data-feather="user"></i></span>
                                    <input type="text" id="first_name" wire:model="first_name"
                                           class="form-control @error('first_name') is-invalid @enderror" placeholder="نام">
                                    @error('first_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="last_name" class="form-label">نام خانوادگی <span class="text-danger">*</span></label>
                                <div class="input-group has-validation">
                                    <span class="input-group-text" wire:ignore><i data-feather="user"></i></span>
                                    <input type="text" id="last_name" wire:model="last_name"
                                           class="form-control @error('last_name') is-invalid @enderror" placeholder="نام خانوادگی">
                                    @error('last_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="birth_date_picker" class="form-label">تاریخ تولد</label>
                                <div class="input-group" wire:ignore>
                                    <span class="input-group-text"><i data-feather="calendar"></i></span>
                                    <input type="text" id="birth_date_picker" class="form-control bg-body"
                                           placeholder="انتخاب از تقویم" readonly autocomplete="off">
                                    <button type="button" id="birth_date_clear" class="btn btn-outline-secondary" title="پاک کردن تاریخ">
                                        <i data-feather="x"></i>
                                    </button>
                                </div>
                                @error('birth_date') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="education_degree" class="form-label">مدرک تحصیلی</label>
                                <div class="input-group has-validation">
                                    <span class="input-group-text" wire:ignore><i data-feather="award"></i></span>
                                    <select id="education_degree" wire:model="education_degree"
                                            class="form-select @error('education_degree') is-invalid @enderror">
                                        <option value="">انتخاب کنید</option>
                                        @foreach (\App\Models\User::EDUCATION_DEGREES as $degree)
                                            <option value="{{ $degree }}">{{ $degree }}</option>
                                        @endforeach
                                    </select>
                                    @error('education_degree') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="city" class="form-label">شهر محل سکونت</label>
                                <div class="input-group has-validation">
                                    <span class="input-group-text" wire:ignore><i data-feather="map-pin"></i></span>
                                    <select id="city" wire:model="city"
                                            class="form-select @error('city') is-invalid @enderror">
                                        <option value="">انتخاب کنید</option>
                                        @foreach ($this->provinces as $province)
                                            <option value="{{ $province->name }}">{{ $province->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('city') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="art_specialty" class="form-label">تخصص هنری</label>
                                <div wire:ignore>
                                    <select id="art_specialty" class="form-select" multiple>
                                        @foreach ($this->allSpecialties as $specialty)
                                            <option value="{{ $specialty->id }}">{{ $specialty->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                @error('art_specialty') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                @error('art_specialty.*') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </div>
                </div>

                {{-- اطلاعات تماس --}}
                <div class="card mb-3">
                    <div class="card-body">
                        <h6 class="card-title mb-4">اطلاعات تماس</h6>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="mobile" class="form-label">شماره موبایل <span class="text-danger">*</span></label>
                                <div class="input-group has-validation">
                                    <span class="input-group-text" wire:ignore><i data-feather="smartphone"></i></span>
                                    <input type="text" id="mobile" wire:model="mobile" dir="ltr" inputmode="numeric" maxlength="11"
                                           class="form-control text-start @error('mobile') is-invalid @enderror" placeholder="09123456789">
                                    @error('mobile') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="phone" class="form-label">شماره تماس (ثابت)</label>
                                <div class="input-group has-validation">
                                    <span class="input-group-text" wire:ignore><i data-feather="phone"></i></span>
                                    <input type="text" id="phone" wire:model="phone" dir="ltr" inputmode="numeric" maxlength="11"
                                           class="form-control text-start @error('phone') is-invalid @enderror" placeholder="02112345678">
                                    @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">پست الکترونیکی <span class="text-danger">*</span></label>
                                <div class="input-group has-validation">
                                    <span class="input-group-text" wire:ignore><i data-feather="mail"></i></span>
                                    <input type="email" id="email" wire:model="email" dir="ltr"
                                           class="form-control text-start @error('email') is-invalid @enderror" placeholder="example@mail.com">
                                    @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="virtual_space_number" class="form-label">شماره فضای مجازی</label>
                                <div class="input-group has-validation">
                                    <span class="input-group-text" wire:ignore><i data-feather="message-circle"></i></span>
                                    <input type="text" id="virtual_space_number" wire:model="virtual_space_number" dir="ltr" inputmode="numeric" maxlength="11"
                                           class="form-control text-start @error('virtual_space_number') is-invalid @enderror" placeholder="09123456789">
                                    @error('virtual_space_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="instagram_id" class="form-label">آیدی اینستاگرام</label>
                                <div class="input-group has-validation">
                                    <span class="input-group-text" wire:ignore><i data-feather="instagram"></i></span>
                                    <input type="text" id="instagram_id" wire:model="instagram_id" dir="ltr"
                                           class="form-control text-start @error('instagram_id') is-invalid @enderror" placeholder="username">
                                    @error('instagram_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- اطلاعات ورود --}}
                <div class="card mb-3">
                    <div class="card-body">
                        <h6 class="card-title mb-4">اطلاعات ورود</h6>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="username" class="form-label">نام کاربری <span class="text-danger">*</span></label>
                                <div class="input-group has-validation">
                                    <span class="input-group-text" wire:ignore><i data-feather="at-sign"></i></span>
                                    <input type="text" id="username" wire:model="username" dir="ltr" autocomplete="off"
                                           class="form-control text-start @error('username') is-invalid @enderror" placeholder="username">
                                    @error('username') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="password" class="form-label">
                                    رمز عبور
                                    @if ($user)
                                        <small class="text-muted">(برای عدم تغییر، خالی بگذارید)</small>
                                    @else
                                        <span class="text-danger">*</span>
                                    @endif
                                </label>
                                <div class="input-group has-validation">
                                    <span class="input-group-text" wire:ignore><i data-feather="lock"></i></span>
                                    <input type="password" id="password" wire:model="password" dir="ltr" autocomplete="new-password"
                                           class="form-control text-start @error('password') is-invalid @enderror" placeholder="حداقل ۸ کاراکتر">
                                    @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="password_confirmation" class="form-label">تکرار رمز عبور</label>
                                <div class="input-group">
                                    <span class="input-group-text" wire:ignore><i data-feather="lock"></i></span>
                                    <input type="password" id="password_confirmation" wire:model="password_confirmation" dir="ltr" autocomplete="new-password"
                                           class="form-control text-start" placeholder="تکرار رمز عبور">
                                </div>
                            </div>

                            @can('manage-roles')
                            <div class="col-12 mb-3">
                                <label for="roles" class="form-label">نقش‌های کاربر</label>
                                <div wire:ignore>
                                    <select id="roles" class="form-select" multiple>
                                        @foreach ($this->allRoles as $role)
                                            <option value="{{ $role->id }}">{{ $role->label ?? $role->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                @error('roles') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                @error('roles.*') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                            </div>
                            @endcan
                        </div>
                    </div>
                </div>

                <div class="d-flex gap-2 mb-3">
                    <button type="submit" class="btn btn-primary btn-icon-text" wire:loading.attr="disabled" wire:target="save, avatar">
                        <span wire:ignore><i class="btn-icon-prepend" data-feather="save"></i></span>
                        <span wire:loading.remove wire:target="save">{{ $user ? 'ذخیره تغییرات' : 'ثبت کاربر' }}</span>
                        <span wire:loading wire:target="save">در حال ذخیره...</span>
                    </button>
                    <a href="{{ route('users.index') }}" class="btn btn-secondary">انصراف</a>
                </div>
            </div>
        </div>
    </form>
</div>

@assets
<style>
    .avatar-upload {
        position: relative;
        width: 140px;
        height: 140px;
    }

    .avatar-upload .avatar-preview {
        width: 140px;
        height: 140px;
        border-radius: 50%;
        object-fit: cover;
        border: 4px solid var(--bs-border-color);
    }

    .avatar-upload .avatar-loading {
        position: absolute;
        inset: 0;
        border-radius: 50%;
        background: rgba(0, 0, 0, .45);
        align-items: center;
        justify-content: center;
    }

    .avatar-upload .avatar-btn {
        position: absolute;
        bottom: 4px;
        left: 4px;
        width: 38px;
        height: 38px;
        border-radius: 50%;
        background: #6571ff;
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        box-shadow: 0 2px 6px rgba(0, 0, 0, .25);
    }

    .avatar-upload .avatar-btn svg {
        width: 18px;
        height: 18px;
    }

    .select2-container--default .select2-selection--multiple {
        min-height: 38px;
        border-color: var(--bs-border-color);
    }
</style>
@endassets

@script
<script>
    // تقویم شمسی تاریخ تولد (مقدار به صورت میلادی برای سرور ارسال می‌شود)
    const birthInput = $('#birth_date_picker');

    const toGregorian = (unix) => {
        const d = new Date(unix);
        return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
    };

    const birthPicker = birthInput.persianDatepicker({
        initialValue: false,
        format: 'YYYY/MM/DD',
        autoClose: true,
        maxDate: new persianDate().valueOf(),
        toolbox: {calendarSwitch: {enabled: false}},
        onSelect: (unix) => $wire.birth_date = toGregorian(unix),
    });

    if ($wire.birth_date) {
        const [y, m, d] = $wire.birth_date.split('-');
        birthPicker.setDate(new Date(y, m - 1, d).getTime());
    }

    $('#birth_date_clear').on('click', () => {
        birthInput.val('');
        $wire.birth_date = null;
    });

    // انتخاب چندگانه تخصص هنری
    const artSelect = $('#art_specialty');

    artSelect.select2({
        dir: 'rtl',
        width: '100%',
        placeholder: 'تخصص‌های هنری را انتخاب کنید',
        closeOnSelect: false,
        language: {noResults: () => 'موردی یافت نشد'},
    });

    artSelect.val($wire.art_specialty).trigger('change');
    artSelect.on('change', () => $wire.art_specialty = artSelect.val() ?? []);

    // انتخاب چندگانه نقش‌ها (فقط برای کسی که مجوز مدیریت نقش‌ها را دارد نمایش داده می‌شود)
    const roleSelect = $('#roles');

    if (roleSelect.length) roleSelect.select2({
        dir: 'rtl',
        width: '100%',
        placeholder: 'نقش‌های کاربر را انتخاب کنید',
        closeOnSelect: false,
        language: {noResults: () => 'موردی یافت نشد'},
    });

    roleSelect.val($wire.roles).trigger('change');
    roleSelect.on('change', () => $wire.roles = roleSelect.val() ?? []);
</script>
@endscript
