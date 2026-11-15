<div class="fb-admin">
    @include('form-builder::admin.partials.styles')

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <a href="{{ route('form-builder.admin.forms.index') }}" class="small text-muted">→ بازگشت به فرم‌ها</a>
            <h5 class="mb-0 mt-1">{{ $formId ? 'ویرایش فرم' : 'ساخت فرم جدید' }}</h5>
        </div>
        <div class="d-flex gap-2">
            @if ($publicUrl)
                <a href="{{ $publicUrl }}" target="_blank" class="btn btn-outline-secondary">{{ $info['is_active'] ? 'مشاهده فرم' : 'پیش‌نمایش' }}</a>
                <a href="{{ route('form-builder.admin.submissions.index', $formId) }}" class="btn btn-outline-secondary">پاسخ‌ها</a>
            @endif
            <button type="button" class="btn btn-primary" wire:click="save" wire:loading.attr="disabled" wire:target="save">
                <span wire:loading.remove wire:target="save">ذخیره فرم</span>
                <span wire:loading wire:target="save">در حال ذخیره...</span>
            </button>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="row g-3">
        {{-- ================= ستون اصلی: تنظیمات فرم و فیلدها ================= --}}
        <div class="col-lg-8">
            <div class="card mb-3">
                <div class="card-body">
                    <h6 class="card-title mb-3">تنظیمات فرم</h6>
                    <div class="row g-3">
                        <div class="col-md-7">
                            <label class="form-label" for="fb-title">عنوان فرم <span class="text-danger">*</span></label>
                            <input type="text" id="fb-title" wire:model.blur="info.title" class="form-control @error('info.title') is-invalid @enderror" placeholder="مثلا: فراخوان جشنواره عکس">
                            @error('info.title') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-5">
                            <label class="form-label" for="fb-slug">آدرس فرم</label>
                            <div class="input-group">
                                <span class="input-group-text small" dir="ltr">/{{ trim(config('form-builder.public.prefix'), '/') }}/</span>
                                <input type="text" id="fb-slug" wire:model="info.slug" class="form-control @error('info.slug') is-invalid @enderror" dir="ltr">
                            </div>
                            @error('info.slug') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="fb-desc">توضیحات بالای فرم</label>
                            <textarea id="fb-desc" wire:model="info.description" rows="2" class="form-control @error('info.description') is-invalid @enderror"></textarea>
                            @error('info.description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-5">
                            <label class="form-label" for="fb-submit">متن دکمه ثبت</label>
                            <input type="text" id="fb-submit" wire:model="info.submit_label" class="form-control" placeholder="ثبت فرم">
                        </div>
                        <div class="col-md-7">
                            <label class="form-label" for="fb-success">پیام پس از ثبت</label>
                            <input type="text" id="fb-success" wire:model="info.success_message" class="form-control" placeholder="فرم با موفقیت ثبت شد. سپاس از شما.">
                        </div>
                        <div class="col-12 d-flex flex-wrap gap-4">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" id="fb-active" wire:model="info.is_active">
                                <label class="form-check-label" for="fb-active">فرم فعال است</label>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" id="fb-auth" wire:model="info.requires_auth">
                                <label class="form-check-label" for="fb-auth">فقط کاربران واردشده</label>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" id="fb-multi" wire:model="info.allow_multiple">
                                <label class="form-check-label" for="fb-multi">هر کاربر چند بار بتواند ثبت کند</label>
                            </div>
                        </div>
                        @if (collect($fields)->contains('kind', 'profile'))
                            <div class="col-12">
                                <div class="alert alert-info py-2 mb-0 small">این فرم فیلد پروفایل دارد، پس فقط کاربران واردشده می‌توانند آن را پر کنند.</div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="mb-0">فیلدهای فرم <span class="text-muted small">({{ count($fields) }})</span></h6>
                @error('fields') <span class="text-danger small">{{ $message }}</span> @enderror
            </div>

            @forelse ($fields as $i => $field)
                @php
                    $isProfile = $field['kind'] === 'profile';
                    $open = $openUid === $field['uid'];
                    $hasError = $errors->has("fields.$i.*");
                    $typeLabel = $isProfile ? 'پروفایل: '.($profileFields[$field['type']]['label'] ?? $field['type']) : ($types[$field['type']] ?? $field['type']);
                @endphp
                <div wire:key="fb-field-{{ $field['uid'] }}" @class(['card fb-field mb-2', 'is-open' => $open, 'border-danger' => $hasError, 'is-profile' => $isProfile])>
                    <div class="fb-field-head" wire:click="toggleField('{{ $field['uid'] }}')">
                        <span @class(['badge', 'bg-info text-dark' => $isProfile, 'bg-light text-dark border' => ! $isProfile])>{{ $typeLabel }}</span>
                        <span class="fb-field-title">{{ $field['label'] ?: 'بدون عنوان' }}@if ($field['is_required'])<span class="text-danger"> *</span>@endif</span>
                        @if ($isProfile)
                            <span class="small text-muted">{{ $field['is_readonly'] ? 'غیرقابل ویرایش' : 'قابل ویرایش' }}</span>
                        @endif
                        <span class="ms-auto d-flex gap-1" wire:click.stop>
                            <button type="button" class="btn btn-sm btn-outline-secondary" wire:click="moveField('{{ $field['uid'] }}', -1)" @disabled($loop->first) title="بالا">↑</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" wire:click="moveField('{{ $field['uid'] }}', 1)" @disabled($loop->last) title="پایین">↓</button>
                            <button type="button" class="btn btn-sm btn-outline-danger" wire:click="removeField('{{ $field['uid'] }}')" wire:confirm="فیلد «{{ $field['label'] }}» حذف شود؟" title="حذف">✕</button>
                        </span>
                    </div>

                    @if ($open)
                        <div class="card-body border-top">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">عنوان فیلد <span class="text-danger">*</span></label>
                                    <input type="text" wire:model.live.debounce.500ms="fields.{{ $i }}.label" class="form-control @error("fields.$i.label") is-invalid @enderror">
                                    @error("fields.$i.label") <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">عرض</label>
                                    <select wire:model="fields.{{ $i }}.width" class="form-select">
                                        <option value="full">تمام عرض</option>
                                        <option value="half">نصف عرض</option>
                                    </select>
                                </div>
                                <div class="col-md-3 d-flex flex-column justify-content-end gap-1">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="req-{{ $field['uid'] }}" wire:model.live="fields.{{ $i }}.is_required">
                                        <label class="form-check-label" for="req-{{ $field['uid'] }}">الزامی</label>
                                    </div>
                                    @if ($isProfile)
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="ro-{{ $field['uid'] }}" wire:model.live="fields.{{ $i }}.is_readonly">
                                            <label class="form-check-label" for="ro-{{ $field['uid'] }}">غیرقابل ویرایش</label>
                                        </div>
                                    @endif
                                </div>

                                @if ($isProfile)
                                    <div class="col-12">
                                        <div class="alert alert-light border small mb-0">
                                            مقدار این فیلد خودکار از پروفایل کاربر (ستون <code>{{ implode(' + ', (array) ($profileFields[$field['type']]['attribute'] ?? $field['type'])) }}</code>) خوانده می‌شود.
                                            @if ($field['is_readonly'])
                                                کاربر نمی‌تواند آن را تغییر دهد و هنگام ثبت هم دوباره از پایگاه داده خوانده می‌شود.
                                            @else
                                                کاربر می‌تواند مقدار را فقط برای همین فرم تغییر دهد (پروفایلش تغییر نمی‌کند).
                                            @endif
                                        </div>
                                    </div>
                                @endif

                                @if (! $isProfile || ! $field['is_readonly'])
                                    @unless (in_array($field['type'], ['checkbox', 'radio', 'file'], true))
                                        <div class="col-md-6">
                                            <label class="form-label">متن راهنمای داخل فیلد</label>
                                            <input type="text" wire:model="fields.{{ $i }}.placeholder" class="form-control">
                                        </div>
                                    @endunless
                                @endif
                                <div class="col-md-6">
                                    <label class="form-label">توضیح زیر فیلد</label>
                                    <input type="text" wire:model="fields.{{ $i }}.help" class="form-control">
                                </div>

                                {{-- تنظیمات هر نوع --}}
                                @if (! $isProfile)
                                    @switch($field['type'])
                                        @case('text')
                                            <div class="col-md-4">
                                                <label class="form-label">قالب ورودی</label>
                                                <select wire:model="fields.{{ $i }}.settings.format" class="form-select">
                                                    @foreach ($formats as $key => $label)
                                                        <option value="{{ $key }}">{{ $label }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            @include('form-builder::admin.partials.length-settings')
                                            @break

                                        @case('textarea')
                                            <div class="col-md-4">
                                                <label class="form-label">تعداد سطر</label>
                                                <input type="number" min="2" max="20" wire:model="fields.{{ $i }}.settings.rows" class="form-control @error("fields.$i.settings.rows") is-invalid @enderror">
                                                @error("fields.$i.settings.rows") <div class="invalid-feedback">{{ $message }}</div> @enderror
                                            </div>
                                            @include('form-builder::admin.partials.length-settings')
                                            @break

                                        @case('number')
                                            <div class="col-md-4">
                                                <label class="form-label">حداقل مقدار</label>
                                                <input type="number" step="any" wire:model="fields.{{ $i }}.settings.min" class="form-control @error("fields.$i.settings.min") is-invalid @enderror">
                                                @error("fields.$i.settings.min") <div class="invalid-feedback">{{ $message }}</div> @enderror
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label">حداکثر مقدار</label>
                                                <input type="number" step="any" wire:model="fields.{{ $i }}.settings.max" class="form-control @error("fields.$i.settings.max") is-invalid @enderror">
                                                @error("fields.$i.settings.max") <div class="invalid-feedback">{{ $message }}</div> @enderror
                                            </div>
                                            <div class="col-md-4 d-flex align-items-end">
                                                <div class="form-check mb-2">
                                                    <input class="form-check-input" type="checkbox" id="int-{{ $field['uid'] }}" wire:model="fields.{{ $i }}.settings.integer">
                                                    <label class="form-check-label" for="int-{{ $field['uid'] }}">فقط عدد صحیح</label>
                                                </div>
                                            </div>
                                            @break

                                        @case('select')
                                        @case('radio')
                                        @case('checkbox')
                                            <div class="col-md-8">
                                                <label class="form-label">گزینه‌ها <small class="text-muted">(هر گزینه در یک سطر{{ $field['type'] === 'checkbox' ? '؛ خالی = یک تیک تکی مثل «قوانین را می‌پذیرم»' : '' }})</small></label>
                                                <textarea wire:model="fields.{{ $i }}.options" rows="4" class="form-control @error("fields.$i.options") is-invalid @enderror"></textarea>
                                                @error("fields.$i.options") <div class="invalid-feedback">{{ $message }}</div> @enderror
                                            </div>
                                            <div class="col-md-4 d-flex flex-column justify-content-end gap-1">
                                                @if ($field['type'] === 'select')
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="multi-{{ $field['uid'] }}" wire:model="fields.{{ $i }}.settings.multiple">
                                                        <label class="form-check-label" for="multi-{{ $field['uid'] }}">انتخاب چندگانه</label>
                                                    </div>
                                                @else
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="inline-{{ $field['uid'] }}" wire:model="fields.{{ $i }}.settings.inline">
                                                        <label class="form-check-label" for="inline-{{ $field['uid'] }}">گزینه‌ها در یک ردیف</label>
                                                    </div>
                                                @endif
                                            </div>
                                            @break

                                        @case('file')
                                            <div class="col-12">
                                                <label class="form-label">نوع فایل‌های مجاز</label>
                                                <div class="fb-ext-list">
                                                    @foreach ($extensions as $ext)
                                                        <label class="fb-ext">
                                                            <input type="checkbox" value="{{ $ext }}" wire:model="fields.{{ $i }}.settings.extensions">
                                                            <span dir="ltr">.{{ $ext }}</span>
                                                        </label>
                                                    @endforeach
                                                </div>
                                                @error("fields.$i.settings.extensions") <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label">حداکثر حجم هر فایل (کیلوبایت)</label>
                                                <input type="number" min="1" wire:model="fields.{{ $i }}.settings.max_size_kb" class="form-control @error("fields.$i.settings.max_size_kb") is-invalid @enderror">
                                                <small class="text-muted">۱۰۲۴ = یک مگابایت (سقف: {{ number_format(config('form-builder.uploads.max_size_kb')) }})</small>
                                                @error("fields.$i.settings.max_size_kb") <div class="invalid-feedback">{{ $message }}</div> @enderror
                                            </div>
                                            <div class="col-md-4 d-flex align-items-center">
                                                <div class="form-check mt-3">
                                                    <input class="form-check-input" type="checkbox" id="fm-{{ $field['uid'] }}" wire:model.live="fields.{{ $i }}.settings.multiple">
                                                    <label class="form-check-label" for="fm-{{ $field['uid'] }}">چند فایل</label>
                                                </div>
                                            </div>
                                            @if (! empty($field['settings']['multiple']))
                                                <div class="col-md-4">
                                                    <label class="form-label">حداکثر تعداد فایل</label>
                                                    <input type="number" min="1" wire:model="fields.{{ $i }}.settings.max_files" class="form-control @error("fields.$i.settings.max_files") is-invalid @enderror">
                                                    @error("fields.$i.settings.max_files") <div class="invalid-feedback">{{ $message }}</div> @enderror
                                                </div>
                                            @endif
                                            @break
                                    @endswitch
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
            @empty
                <div class="card">
                    <div class="card-body text-center text-muted py-5">
                        هنوز فیلدی اضافه نشده است. از ستون کناری نوع فیلد را انتخاب کنید.
                    </div>
                </div>
            @endforelse
        </div>

        {{-- ================= ستون کناری: افزودن فیلد ================= --}}
        <div class="col-lg-4">
            <div class="fb-palette">
                <div class="card mb-3">
                    <div class="card-body">
                        <h6 class="card-title">ورودی‌های خام</h6>
                        <p class="small text-muted">کاربر مقدار را خودش وارد می‌کند.</p>
                        <div class="fb-palette-grid">
                            @foreach ($types as $type => $label)
                                <button type="button" class="btn btn-outline-primary btn-sm" wire:click="addField('{{ $type }}')">+ {{ $label }}</button>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <h6 class="card-title">فیلدهای پایگاه داده</h6>
                        <p class="small text-muted">از پروفایل کاربر خوانده و خودکار پر می‌شوند.</p>
                        <div class="fb-palette-grid">
                            @foreach ($profileFields as $key => $config)
                                <button type="button" class="btn btn-outline-info btn-sm" wire:click="addProfileField('{{ $key }}')" @disabled(in_array($key, $addedProfile, true))>
                                    + {{ $config['label'] }}
                                </button>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
