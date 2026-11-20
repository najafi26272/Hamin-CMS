<?php

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts::admin')] class extends Component
{
    public ?Role $role = null;

    public string $name = '';
    public string $label = '';
    public string $description = '';
    public array $permissions = []; // شناسه مجوزهای انتخاب شده

    public function mount(Role $role): void
    {
        if (! $role->exists) {
            return;
        }

        $this->role = $role;

        $this->name = $role->name;
        $this->label = $role->label ?? '';
        $this->description = $role->description ?? '';
        $this->permissions = $role->permissions()->pluck('permissions.id')->map(fn ($id) => (string) $id)->all();
    }

    #[Computed]
    public function allPermissions()
    {
        return Permission::orderBy('label')->get(['id', 'name', 'label']);
    }

    protected function rules(): array
    {
        return [
            'name' => ['required', 'max:50', 'regex:/^[a-z0-9_-]+$/', Rule::unique('roles', 'name')->ignore($this->role?->id)],
            'label' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:1000'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => [Rule::exists('permissions', 'id')],
        ];
    }

    protected function messages(): array
    {
        return [
            'required' => ':attribute را وارد کنید.',
            'max' => ':attribute نباید بیشتر از :max کاراکتر باشد.',
            'unique' => 'این :attribute قبلا ثبت شده است.',
            'exists' => ':attribute انتخاب شده معتبر نیست.',
            'name.regex' => 'نام فنی فقط می‌تواند شامل حروف کوچک انگلیسی، عدد، - و _ باشد.',
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'name' => 'نام فنی',
            'label' => 'عنوان نقش',
            'description' => 'توضیحات',
            'permissions' => 'مجوزها',
            'permissions.*' => 'مجوز',
        ];
    }

    // نقش مدیر اصلی: نام آن ثابت است و همیشه همه مجوزها را دارد
    #[Computed]
    public function isAdminRole(): bool
    {
        return $this->role?->name === 'admin';
    }

    // نقش‌های سیستمی (مدیر اصلی و هنرمند) نام فنی ثابت دارند چون در کد استفاده می‌شوند
    #[Computed]
    public function isSystemRole(): bool
    {
        return in_array($this->role?->name, ['admin', 'artist']);
    }

    public function save()
    {
        if ($this->isSystemRole) {
            $this->name = $this->role->name;
        }

        if ($this->isAdminRole) {
            $this->permissions = Permission::pluck('id')->map(fn ($id) => (string) $id)->all();
        }

        $this->name = strtolower(trim($this->name));

        try {
            $this->validate();
        } catch (ValidationException $e) {
            $this->dispatch('notify', type: 'error', message: 'لطفا خطاهای فرم را بررسی کنید.');
            throw $e;
        }

        $data = [
            'name' => $this->name,
            'label' => trim($this->label),
            'description' => trim($this->description) ?: null,
        ];

        try {
            if ($this->role) {
                $this->role->update($data);
                $role = $this->role;
                $message = 'نقش با موفقیت ویرایش شد.';
            } else {
                $role = Role::create($data);
                $message = 'نقش جدید با موفقیت ثبت شد.';
            }

            $role->permissions()->sync($this->permissions);
        } catch (\Throwable $e) {
            report($e);
            $this->dispatch('notify', type: 'error', message: 'ذخیره اطلاعات با خطا مواجه شد. دوباره تلاش کنید.');
            return null;
        }

        session()->flash('success', $message);

        return $this->redirectRoute('roles.index');
    }
};
?>

<div>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0">{{ $role ? 'ویرایش نقش: '.($role->label ?? $role->name) : 'ثبت نقش جدید' }}</h5>

        <a href="{{ route('roles.index') }}" class="btn btn-outline-secondary btn-icon-text">
            <span wire:ignore><i class="btn-icon-prepend" data-feather="arrow-right"></i></span>
            بازگشت به لیست
        </a>
    </div>

    <form wire:submit="save" novalidate>
        <div class="card mb-3">
            <div class="card-body">
                <h6 class="card-title mb-4">اطلاعات نقش</h6>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="name" class="form-label">نام فنی (انگلیسی) <span class="text-danger">*</span></label>
                        <div class="input-group has-validation">
                            <span class="input-group-text" wire:ignore><i data-feather="code"></i></span>
                            <input type="text" id="name" wire:model="name" dir="ltr" autocomplete="off" @readonly($this->isSystemRole)
                                   class="form-control text-start @error('name') is-invalid @enderror" placeholder="editor">
                            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="label" class="form-label">عنوان نقش (نمایشی) <span class="text-danger">*</span></label>
                        <div class="input-group has-validation">
                            <span class="input-group-text" wire:ignore><i data-feather="tag"></i></span>
                            <input type="text" id="label" wire:model="label"
                                   class="form-control @error('label') is-invalid @enderror" placeholder="مثلا: ویراستار">
                            @error('label') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="col-12 mb-3">
                        <label for="permissions" class="form-label">مجوزها</label>
                        @if ($this->isAdminRole)
                            <div class="alert alert-info mb-0">مدیر اصلی به همه بخش‌ها دسترسی دارد و مجوزهای آن قابل تغییر نیست.</div>
                        @else
                        <div wire:ignore>
                            <select id="permissions" class="form-select" multiple>
                                @foreach ($this->allPermissions as $permission)
                                    <option value="{{ $permission->id }}">{{ $permission->label ?? $permission->name }} ({{ $permission->name }})</option>
                                @endforeach
                            </select>
                        </div>
                        @error('permissions') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        @error('permissions.*') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        @endif
                    </div>

                    <div class="col-12 mb-3">
                        <label for="description" class="form-label">توضیحات</label>
                        <div class="input-group has-validation">
                            <span class="input-group-text align-items-start pt-2" wire:ignore><i data-feather="file-text"></i></span>
                            <textarea id="description" wire:model="description" rows="4"
                                      class="form-control @error('description') is-invalid @enderror" placeholder="توضیح کوتاه درباره این نقش"></textarea>
                            @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex gap-2 mb-3">
            <button type="submit" class="btn btn-primary btn-icon-text" wire:loading.attr="disabled" wire:target="save">
                <span wire:ignore><i class="btn-icon-prepend" data-feather="save"></i></span>
                <span wire:loading.remove wire:target="save">{{ $role ? 'ذخیره تغییرات' : 'ثبت نقش' }}</span>
                <span wire:loading wire:target="save">در حال ذخیره...</span>
            </button>
            <a href="{{ route('roles.index') }}" class="btn btn-secondary">انصراف</a>
        </div>
    </form>
</div>


@script
<script>
    // انتخاب چندگانه مجوزها
    const permissionSelect = $('#permissions');

    if (permissionSelect.length) permissionSelect.select2({
        dir: 'rtl',
        width: '100%',
        placeholder: 'مجوزهای این نقش را انتخاب کنید',
        closeOnSelect: false,
        language: {noResults: () => 'موردی یافت نشد'},
    });

    permissionSelect.val($wire.permissions).trigger('change');
    permissionSelect.on('change', () => $wire.permissions = permissionSelect.val() ?? []);
</script>
@endscript
