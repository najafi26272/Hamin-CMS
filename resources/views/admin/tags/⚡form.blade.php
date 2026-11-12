<?php

use App\Models\Tag;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts::admin')] class extends Component
{
    public ?Tag $tag = null;

    public string $name = '';
    public string $slug = '';

    // اگر کاربر slug را دستی تغییر دهد، دیگر از روی نام ساخته نمی‌شود
    public bool $slugTouched = false;

    public function mount(Tag $tag): void
    {
        if (! $tag->exists) {
            return;
        }

        $this->tag = $tag;

        $this->name = $tag->name;
        $this->slug = $tag->slug;

        // در ویرایش، slug قبلی حفظ می‌شود تا آدرس‌ها تغییر نکنند
        $this->slugTouched = true;
    }

    public function updatedName(): void
    {
        if (! $this->slugTouched) {
            $this->slug = Tag::uniqueSlug($this->name, $this->tag?->id);
        }
    }

    public function updatedSlug(): void
    {
        $this->slugTouched = true;
    }

    // دکمه «ساخت از روی نام»
    public function generateSlug(): void
    {
        $this->slug = Tag::uniqueSlug($this->name, $this->tag?->id);
        $this->slugTouched = false;
        $this->resetValidation('slug');
    }

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:190'],
            'slug' => ['required', 'max:190', 'regex:/^[\p{L}\p{N}_]+$/u', Rule::unique('tags', 'slug')->ignore($this->tag?->id)],
        ];
    }

    protected function messages(): array
    {
        return [
            'required' => ':attribute را وارد کنید.',
            'max' => ':attribute نباید بیشتر از :max کاراکتر باشد.',
            'unique' => 'این :attribute قبلا ثبت شده است.',
            'slug.regex' => 'slug فقط می‌تواند شامل حروف، عدد و _ باشد.',
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'name' => 'نام برچسب',
            'slug' => 'slug',
        ];
    }

    public function save()
    {
        // slug دستی هم با همان قوانین استاندارد اصلاح می‌شود
        $this->slug = Tag::makeSlug($this->slug);

        try {
            $this->validate();
        } catch (ValidationException $e) {
            $this->dispatch('notify', type: 'error', message: 'لطفا خطاهای فرم را بررسی کنید.');
            throw $e;
        }

        $data = [
            'name' => trim($this->name),
            'slug' => $this->slug,
        ];

        try {
            if ($this->tag) {
                $this->tag->update($data);
                $message = 'برچسب با موفقیت ویرایش شد.';
            } else {
                Tag::create($data);
                $message = 'برچسب جدید با موفقیت ثبت شد.';
            }
        } catch (\Throwable $e) {
            report($e);
            $this->dispatch('notify', type: 'error', message: 'ذخیره اطلاعات با خطا مواجه شد. دوباره تلاش کنید.');
            return null;
        }

        session()->flash('success', $message);

        return $this->redirectRoute('tags.index');
    }
};
?>

<div>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0">{{ $tag ? 'ویرایش برچسب: '.$tag->name : 'ثبت برچسب جدید' }}</h5>

        <a href="{{ route('tags.index') }}" class="btn btn-outline-secondary btn-icon-text">
            <span wire:ignore><i class="btn-icon-prepend" data-feather="arrow-right"></i></span>
            بازگشت به لیست
        </a>
    </div>

    <form wire:submit="save" novalidate>
        <div class="card mb-3">
            <div class="card-body">
                <h6 class="card-title mb-4">اطلاعات برچسب</h6>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="name" class="form-label">نام برچسب <span class="text-danger">*</span></label>
                        <div class="input-group has-validation">
                            <span class="input-group-text" wire:ignore><i data-feather="tag"></i></span>
                            <input type="text" id="name" wire:model.live.debounce.400ms="name"
                                   class="form-control @error('name') is-invalid @enderror" placeholder="مثلا: خوشنویسی">
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
                </div>
            </div>
        </div>

        <div class="d-flex gap-2 mb-3">
            <button type="submit" class="btn btn-primary btn-icon-text" wire:loading.attr="disabled" wire:target="save">
                <span wire:ignore><i class="btn-icon-prepend" data-feather="save"></i></span>
                <span wire:loading.remove wire:target="save">{{ $tag ? 'ذخیره تغییرات' : 'ثبت برچسب' }}</span>
                <span wire:loading wire:target="save">در حال ذخیره...</span>
            </button>
            <a href="{{ route('tags.index') }}" class="btn btn-secondary">انصراف</a>
        </div>
    </form>
</div>
