<?php

use App\Models\ArtSpecialty;
use App\Models\Provinces;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

// درخواست هنرمندی: کاربر واردشده اطلاعات تکمیلی را پر می‌کند و مدیر آن را تأیید یا رد می‌کند
new #[Layout('layouts::admin')] class extends Component
{
    use WithFileUploads;

    public ?string $birth_date = null; // میلادی (Y-m-d)؛ نمایش به صورت شمسی در تقویم
    public $avatar = null;
    public string $phone = '';
    public string $mobile = '';
    public string $email = '';
    public string $city = '';
    public string $virtual_space_number = '';
    public string $instagram_id = '';
    public string $education_degree = '';
    public array $specialties = [];    // شناسه تخصص‌های هنری
    public string $username = '';

    public function mount(): void
    {
        $user = auth()->user();

        $this->birth_date = $user->birth_date?->format('Y-m-d');
        $this->phone = (string) $user->phone;
        $this->mobile = (string) $user->mobile;
        $this->email = (string) $user->email;
        $this->city = (string) $user->city;
        $this->virtual_space_number = (string) $user->virtual_space_number;
        $this->instagram_id = (string) $user->instagram_id;
        $this->education_degree = (string) $user->education_degree;
        $this->username = (string) $user->username;
        $this->specialties = $user->artSpecialties()->pluck('art_specialties.id')->map(fn ($id) => (string) $id)->all();
    }

    #[Computed]
    public function user(): User
    {
        return auth()->user();
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

    protected function rules(): array
    {
        $id = $this->user->id;

        return [
            'birth_date' => ['required', 'date', 'before:today'],
            // اگر کاربر قبلا تصویر پروفایل داشته باشد، انتخاب دوباره لازم نیست
            'avatar' => [$this->user->avatar ? 'nullable' : 'required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'phone' => ['nullable', 'regex:/^0\d{10}$/'],
            'mobile' => ['required', 'regex:/^09\d{9}$/', Rule::unique('users', 'mobile')->ignore($id)],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($id)],
            'city' => ['required', Rule::exists('provinces', 'name')],
            'virtual_space_number' => ['required', 'regex:/^09\d{9}$/'],
            'instagram_id' => ['required', 'max:30', 'regex:/^[A-Za-z0-9_.]+$/'],
            'education_degree' => ['required', Rule::in(User::EDUCATION_DEGREES)],
            'specialties' => ['required', 'array', 'min:1'],
            'specialties.*' => [Rule::exists('art_specialties', 'id')],
            'username' => ['required', 'string', 'min:3', 'max:50', 'regex:/^[A-Za-z0-9_]+$/', Rule::unique('users', 'username')->ignore($id)],
        ];
    }

    protected function messages(): array
    {
        return [
            'required' => ':attribute را وارد کنید.',
            'max' => ':attribute نباید بیشتر از :max کاراکتر باشد.',
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
            'instagram_id.regex' => 'آیدی اینستاگرام فقط می‌تواند شامل حروف انگلیسی، عدد، _ و . باشد.',
            'specialties.required' => 'حداقل یک تخصص هنری انتخاب کنید.',
            'specialties.min' => 'حداقل یک تخصص هنری انتخاب کنید.',
            'avatar.required' => 'تصویر پروفایل را انتخاب کنید.',
            'birth_date.required' => 'تاریخ تولد را انتخاب کنید.',
            'city.required' => 'شهر محل سکونت را انتخاب کنید.',
            'education_degree.required' => 'مدرک تحصیلی را انتخاب کنید.',
            'avatar.image' => 'فایل انتخاب شده باید تصویر باشد.',
            'avatar.mimes' => 'فرمت تصویر باید jpg، png یا webp باشد.',
            'avatar.max' => 'حجم تصویر نباید بیشتر از ۲ مگابایت باشد.',
            'username.regex' => 'نام کاربری فقط می‌تواند شامل حروف انگلیسی، عدد و _ باشد.',
            'username.min' => ':attribute باید حداقل :min کاراکتر باشد.',
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'birth_date' => 'تاریخ تولد',
            'avatar' => 'تصویر پروفایل',
            'phone' => 'شماره تماس ثابت',
            'mobile' => 'شماره موبایل',
            'email' => 'ایمیل',
            'city' => 'شهر محل سکونت',
            'virtual_space_number' => 'شماره فضای مجازی',
            'instagram_id' => 'آیدی اینستاگرام',
            'education_degree' => 'مدرک تحصیلی',
            'specialties' => 'تخصص هنری',
            'specialties.*' => 'تخصص هنری',
            'username' => 'نام کاربری',
        ];
    }

    public function updatedAvatar(): void
    {
        $this->validateOnly('avatar');
    }

    public function submit()
    {
        $user = $this->user;

        // هنرمند تأییدشده فرم را نمی‌بیند؛ تغییر اطلاعات او با مدیر است
        if ($user->artist_status === 'approved') {
            return $this->redirectRoute('artist.apply');
        }

        // تبدیل اعداد فارسی به انگلیسی و حذف @ از ابتدای آیدی اینستاگرام
        foreach (['phone', 'mobile', 'virtual_space_number'] as $field) {
            $this->$field = $this->toEnglishDigits(trim($this->$field));
        }
        $this->instagram_id = ltrim(trim($this->instagram_id), '@');

        try {
            $this->email = trim($this->email);
            $this->phone = trim($this->phone);
            $this->username = trim($this->username);
            $this->validate();
        } catch (ValidationException $e) {
            $this->dispatch('notify', type: 'error', message: 'لطفا خطاهای فرم را بررسی کنید.');
            throw $e;
        }

        $oldAvatar = $user->avatar;
        $newAvatar = $this->avatar?->store('avatars', 'public');

        DB::transaction(function () use ($user, $newAvatar) {
            $user->update([
                'birth_date' => $this->birth_date,
                'avatar' => $newAvatar ?? $user->avatar,
                'phone' => filled($this->phone) ? $this->phone : null,
                'email' => filled($this->email) ? $this->email : null,
                'mobile' => $this->mobile,
                'city' => $this->city,
                'username' => $this->username,
                'virtual_space_number' => $this->virtual_space_number,
                'instagram_id' => $this->instagram_id,
                'education_degree' => $this->education_degree,
                // هر ارسال یا ویرایش، درخواست را دوباره به صف بررسی می‌فرستد
                'artist_status' => 'pending',
                'artist_rejection_reason' => null,
                'reviewed_at' => null,
                'reviewed_by' => null,
            ]);

            $user->artSpecialties()->sync($this->specialties);
        });

        // فایل قبلی فقط پس از ذخیره موفق پاک می‌شود
        if ($newAvatar && $oldAvatar) {
            Storage::disk('public')->delete($oldAvatar);
        }

        session()->flash('success', 'درخواست هنرمندی شما ثبت شد و پس از بررسی مدیر نتیجه در همین صفحه نمایش داده می‌شود.');

        return $this->redirectRoute('artist.apply');
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
        <h5 class="mb-0">ثبت‌نام به عنوان هنرمند</h5>
    </div>

    @php($status = $this->user->artist_status)

    @if ($status === 'approved')
        <div class="card">
            <div class="card-body text-center py-5">
                <div class="text-success mb-3" wire:ignore><i data-feather="check-circle" style="width: 48px; height: 48px"></i></div>
                <h5 class="mb-2">شما هنرمند تأییدشده هستید</h5>
                <p class="text-muted mb-4">می‌توانید از بخش «مطالب من» آثار خود را ارسال کنید. برای تغییر اطلاعات پروفایل هنری با مدیر سایت هماهنگ کنید.</p>
                @can('create-post')
                    <a href="{{ route('posts.index') }}" class="btn btn-primary">مطالب من</a>
                @endcan
            </div>
        </div>
    @else
        @if ($status === 'pending')
            <div class="alert alert-warning d-flex gap-2 align-items-start">
                <span wire:ignore><i data-feather="clock"></i></span>
                <div>درخواست هنرمندی شما ثبت شده و در انتظار بررسی مدیر است. تا پیش از بررسی می‌توانید اطلاعات را اصلاح کنید.</div>
            </div>
        @elseif ($status === 'rejected')
            <div class="alert alert-danger d-flex gap-2 align-items-start">
                <span wire:ignore><i data-feather="x-circle"></i></span>
                <div>
                    درخواست هنرمندی شما تأیید نشد.
                    @if ($this->user->artist_rejection_reason)
                        <div class="mt-1"><strong>دلیل:</strong> {{ $this->user->artist_rejection_reason }}</div>
                    @endif
                    <div class="mt-1">می‌توانید اطلاعات را اصلاح کنید و دوباره ارسال کنید.</div>
                </div>
            </div>
        @else
            <div class="alert alert-info">
                با تکمیل این فرم، درخواست شما برای عضویت به عنوان هنرمند برای مدیر ارسال می‌شود. پس از تأیید، امکان ارسال آثار متناسب با تخصص‌تان فعال می‌شود.
            </div>
        @endif

        <div class="card">
            <div class="card-body">
                <form wire:submit="submit" novalidate>
                    <div class="row">
                        {{-- تصویر پروفایل --}}
                        <div class="col-12 mb-4 d-flex align-items-center gap-3">
                            <label for="avatar" class="register-avatar mb-0" title="انتخاب تصویر پروفایل">
                                @if ($avatar && ! $errors->has('avatar'))
                                    <img src="{{ $avatar->temporaryUrl() }}" alt="">
                                @else
                                    <img src="{{ $this->user->avatar_url }}" alt="">
                                @endif
                                <span class="register-avatar-btn" wire:ignore><i data-feather="camera"></i></span>
                                <span class="register-avatar-loading" wire:loading.flex wire:target="avatar">
                                    <span class="spinner-border spinner-border-sm text-light"></span>
                                </span>
                            </label>
                            <input type="file" id="avatar" wire:model="avatar" accept="image/png,image/jpeg,image/webp" class="d-none">
                            <div>
                                <div class="fw-bold">{{ $this->user->first_name }} {{ $this->user->last_name }}</div>
                                <div class="small">تصویر پروفایل <span class="text-danger">*</span></div>
                                <small class="text-muted">jpg، png یا webp تا ۲ مگابایت</small>
                                @error('avatar') <div class="text-danger small">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <h6 class="text-primary mb-3">اطلاعات شخصی</h6>

                        <div class="col-md-6 mb-3">
                            <label for="birth_date_picker" class="form-label">تاریخ تولد <span class="text-danger">*</span></label>
                            <div class="input-group" wire:ignore>
                                <span class="input-group-text"><i data-feather="calendar"></i></span>
                                <input type="text" id="birth_date_picker" class="form-control bg-body" placeholder="انتخاب از تقویم" readonly autocomplete="off">
                                <button type="button" id="birth_date_clear" class="btn btn-outline-secondary" title="پاک کردن تاریخ"><i data-feather="x"></i></button>
                            </div>
                            @error('birth_date') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="education_degree" class="form-label">مدرک تحصیلی <span class="text-danger">*</span></label>
                            <div class="input-group has-validation">
                                <span class="input-group-text" wire:ignore><i data-feather="award"></i></span>
                                <select id="education_degree" wire:model="education_degree" class="form-select @error('education_degree') is-invalid @enderror">
                                    <option value="">انتخاب کنید</option>
                                    @foreach (\App\Models\User::EDUCATION_DEGREES as $degree)
                                        <option value="{{ $degree }}">{{ $degree }}</option>
                                    @endforeach
                                </select>
                                @error('education_degree') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="city" class="form-label">شهر محل سکونت <span class="text-danger">*</span></label>
                            <div class="input-group has-validation">
                                <span class="input-group-text" wire:ignore><i data-feather="map-pin"></i></span>
                                <select id="city" wire:model="city" class="form-select @error('city') is-invalid @enderror">
                                    <option value="">انتخاب کنید</option>
                                    @foreach ($this->provinces as $province)
                                        <option value="{{ $province->name }}">{{ $province->name }}</option>
                                    @endforeach
                                </select>
                                @error('city') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="specialties" class="form-label">تخصص هنری <span class="text-danger">*</span></label>
                            <div wire:ignore>
                                <select id="specialties" class="form-select" multiple>
                                    @foreach ($this->allSpecialties as $specialty)
                                        <option value="{{ $specialty->id }}">{{ $specialty->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            @error('specialties') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                            @error('specialties.*') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="mobile" class="form-label">شماره موبایل <span class="text-danger">*</span></label>
                            <div class="input-group has-validation">
                                <span class="input-group-text" wire:ignore><i data-feather="smartphone"></i></span>
                                <input disabled type="text" id="mobile"  dir="ltr" inputmode="numeric" maxlength="11" class="form-control text-start @error('mobile') is-invalid @enderror" placeholder="09123456789">
                                @error('mobile') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="phone" class="form-label">شماره تماس (ثابت) </label>
                            <div class="input-group has-validation">
                                <span class="input-group-text" wire:ignore><i data-feather="phone"></i></span>
                                <input type="text" id="phone" wire:model="phone" dir="ltr" inputmode="numeric" maxlength="11" class="form-control text-start @error('phone') is-invalid @enderror" placeholder="02112345678">
                                @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">پست الکترونیکی </label>
                            <div class="input-group has-validation">
                                <span class="input-group-text" wire:ignore><i data-feather="mail"></i></span>
                                <input type="email" id="email" wire:model="email" dir="ltr" class="form-control text-start @error('email') is-invalid @enderror" placeholder="example@mail.com">
                                @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="virtual_space_number" class="form-label">شماره فضای مجازی <span class="text-danger">*</span></label>
                            <div class="input-group has-validation">
                                <span class="input-group-text" wire:ignore><i data-feather="message-circle"></i></span>
                                <input type="text" id="virtual_space_number" wire:model="virtual_space_number" dir="ltr" inputmode="numeric" maxlength="11" class="form-control text-start @error('virtual_space_number') is-invalid @enderror" placeholder="09123456789">
                                @error('virtual_space_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="instagram_id" class="form-label">آیدی اینستاگرام <span class="text-danger">*</span></label>
                            <div class="input-group has-validation">
                                <span class="input-group-text" wire:ignore><i data-feather="instagram"></i></span>
                                <input type="text" id="instagram_id" wire:model="instagram_id" dir="ltr" class="form-control text-start @error('instagram_id') is-invalid @enderror" placeholder="username">
                                @error('instagram_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>


                        <div class="col-md-6 mb-3">
                            <label for="username" class="form-label">نام کاربری <span class="text-danger">*</span></label>
                            <div class="input-group has-validation">
                                <span class="input-group-text" wire:ignore><i data-feather="user"></i></span>
                                <input type="text" id="username" wire:model="username" dir="ltr" class="form-control text-start @error('username') is-invalid @enderror" placeholder="username">
                                @error('username') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                    </div>

                    <button type="submit" class="btn btn-primary btn-icon-text mt-2" wire:loading.attr="disabled" wire:target="submit, avatar">
                        <span wire:ignore><i class="btn-icon-prepend" data-feather="send"></i></span>
                        <span wire:loading.remove wire:target="submit">{{ $status ? 'ارسال دوباره درخواست' : 'ارسال درخواست' }}</span>
                        <span wire:loading wire:target="submit">در حال ارسال...</span>
                    </button>
                </form>
            </div>
        </div>
    @endif
</div>


@script
<script>
    // هنرمند تأییدشده فرم را نمی‌بیند
    const birthInput = $('#birth_date_picker');

    if (birthInput.length) {
        // تقویم شمسی تاریخ تولد (مقدار به صورت میلادی برای سرور ارسال می‌شود)
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
        const specialtySelect = $('#specialties');

        specialtySelect.select2({
            dir: 'rtl',
            width: '100%',
            placeholder: 'یک یا چند تخصص انتخاب کنید',
            closeOnSelect: false,
            language: {noResults: () => 'موردی یافت نشد'},
        });

        specialtySelect.val($wire.specialties).trigger('change');
        specialtySelect.on('change', () => $wire.specialties = specialtySelect.val() ?? []);
    }
</script>
@endscript
