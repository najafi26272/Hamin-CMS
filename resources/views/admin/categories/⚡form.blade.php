<?php

use App\Models\Category;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts::admin')] class extends Component
{
    public ?Category $category = null;

    public string $name = '';
    public string $slug = '';
    public string $parent_id = '';
    public string $description = '';

    // اگر کاربر slug را دستی تغییر دهد، دیگر از روی نام ساخته نمی‌شود
    public bool $slugTouched = false;

    public function mount(Category $category): void
    {
        if (! $category->exists) {
            return;
        }

        $this->category = $category;

        $this->name = $category->name;
        $this->slug = $category->slug;
        $this->parent_id = (string) ($category->parent_id ?? '');
        $this->description = $category->description ?? '';

        // در ویرایش، slug قبلی حفظ می‌شود تا آدرس‌ها تغییر نکنند
        $this->slugTouched = true;
    }

    public function updatedName(): void
    {
        if (! $this->slugTouched) {
            $this->slug = Category::uniqueSlug($this->name, $this->category?->id);
        }
    }

    public function updatedSlug(): void
    {
        $this->slugTouched = true;
    }

    // دکمه «ساخت از روی نام»
    public function generateSlug(): void
    {
        $this->slug = Category::uniqueSlug($this->name, $this->category?->id);
        $this->slugTouched = false;
        $this->resetValidation('slug');
    }

    // دسته‌بندی‌هایی که نمی‌توانند والد باشند: خودش و زیرمجموعه‌هایش
    #[Computed]
    public function excludedIds(): array
    {
        return $this->category ? [$this->category->id, ...$this->category->descendantIds()] : [];
    }

    // لیست والدها به صورت درختی: «هنر»، «— نقاشی»، «— — آبرنگ»
    #[Computed]
    public function parentOptions(): array
    {
        return Category::treeOptions($this->excludedIds);
    }

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:190'],
            'slug' => ['required', 'max:190', 'regex:/^[\p{L}\p{N}_]+$/u', Rule::unique('categories', 'slug')->ignore($this->category?->id)],
            'parent_id' => ['nullable', Rule::exists('categories', 'id'), Rule::notIn($this->excludedIds)],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }

    protected function messages(): array
    {
        return [
            'required' => ':attribute را وارد کنید.',
            'max' => ':attribute نباید بیشتر از :max کاراکتر باشد.',
            'unique' => 'این :attribute قبلا ثبت شده است.',
            'exists' => ':attribute انتخاب شده معتبر نیست.',
            'slug.regex' => 'slug فقط می‌تواند شامل حروف، عدد و _ باشد.',
            'parent_id.not_in' => 'دسته‌بندی نمی‌تواند والد خودش یا زیرمجموعه‌اش باشد.',
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'name' => 'نام دسته‌بندی',
            'slug' => 'slug',
            'parent_id' => 'دسته‌بندی والد',
            'description' => 'توضیحات',
        ];
    }

    public function save()
    {
        // slug دستی هم با همان قوانین استاندارد اصلاح می‌شود
        $this->slug = Category::makeSlug($this->slug);

        try {
            $this->validate();
        } catch (ValidationException $e) {
            $this->dispatch('notify', type: 'error', message: 'لطفا خطاهای فرم را بررسی کنید.');
            throw $e;
        }

        $data = [
            'name' => trim($this->name),
            'slug' => $this->slug,
            'parent_id' => $this->parent_id ?: null,
            'description' => trim($this->description) ?: null,
        ];

        try {
            if ($this->category) {
                $this->category->update($data);
                $message = 'دسته‌بندی با موفقیت ویرایش شد.';
            } else {
                Category::create($data);
                $message = 'دسته‌بندی جدید با موفقیت ثبت شد.';
            }
        } catch (\Throwable $e) {
            report($e);
            $this->dispatch('notify', type: 'error', message: 'ذخیره اطلاعات با خطا مواجه شد. دوباره تلاش کنید.');
            return null;
        }

        session()->flash('success', $message);

        return $this->redirectRoute('categories.index');
    }
};
?>

<div>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0">{{ $category ? 'ویرایش دسته‌بندی: '.$category->name : 'ثبت دسته‌بندی جدید' }}</h5>

        <a href="{{ route('categories.index') }}" class="btn btn-outline-secondary btn-icon-text">
            <span wire:ignore><i class="btn-icon-prepend" data-feather="arrow-right"></i></span>
            بازگشت به لیست
        </a>
    </div>

    <form wire:submit="save" novalidate>
        <div class="card mb-3">
            <div class="card-body">
                <h6 class="card-title mb-4">اطلاعات دسته‌بندی</h6>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="name" class="form-label">نام دسته‌بندی <span class="text-danger">*</span></label>
                        <div class="input-group has-validation">
                            <span class="input-group-text" wire:ignore><i data-feather="folder"></i></span>
                            <input type="text" id="name" wire:model.live.debounce.400ms="name"
                                   class="form-control @error('name') is-invalid @enderror" placeholder="مثلا: هنرهای تجسمی">
                            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="slug" class="form-label">slug (نامک) <span class="text-danger">*</span></label>
                        <div class="input-group has-validation">
                            <span class="input-group-text" wire:ignore><i data-feather="link"></i></span>
                            <input type="text" id="slug" wire:model.blur="slug" autocomplete="off" dir="auto"
                                   class="form-control @error('slug') is-invalid @enderror" placeholder="به صورت خودکار از نام ساخته می‌شود">
                            <button type="button" wire:click="generateSlug" class="btn btn-outline-secondary" title="ساخت از روی نام">
                                <span wire:ignore><i data-feather="refresh-cw"></i></span>
                            </button>
                            @error('slug') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="form-text">فاصله‌ها به _ تبدیل می‌شوند و فقط حروف، عدد و _ مجاز است.</div>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="parent_id" class="form-label">دسته‌بندی والد</label>
                        <div class="input-group has-validation">
                            <span class="input-group-text" wire:ignore><i data-feather="git-branch"></i></span>
                            <select id="parent_id" wire:model="parent_id"
                                    class="form-select @error('parent_id') is-invalid @enderror">
                                <option value="">— بدون والد (دسته‌بندی اصلی) —</option>
                                @foreach ($this->parentOptions as $option)
                                    <option value="{{ $option['id'] }}">{{ $option['name'] }}</option>
                                @endforeach
                            </select>
                            @error('parent_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="col-12 mb-3">
                        <label for="description" class="form-label">توضیحات</label>
                        <div class="input-group has-validation">
                            <span class="input-group-text align-items-start pt-2" wire:ignore><i data-feather="file-text"></i></span>
                            <textarea id="description" wire:model="description" rows="4"
                                      class="form-control @error('description') is-invalid @enderror" placeholder="توضیح کوتاه درباره این دسته‌بندی"></textarea>
                            @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex gap-2 mb-3">
            <button type="submit" class="btn btn-primary btn-icon-text" wire:loading.attr="disabled" wire:target="save">
                <span wire:ignore><i class="btn-icon-prepend" data-feather="save"></i></span>
                <span wire:loading.remove wire:target="save">{{ $category ? 'ذخیره تغییرات' : 'ثبت دسته‌بندی' }}</span>
                <span wire:loading wire:target="save">در حال ذخیره...</span>
            </button>
            <a href="{{ route('categories.index') }}" class="btn btn-secondary">انصراف</a>
        </div>
    </form>
</div>
