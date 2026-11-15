{{-- یک فیلد فرم — $field (FormField) --}}
@php
    $name = $field->name;
    $id = 'fb-'.$name;
    $model = 'values.'.$name;
    $error = $field->type === 'file'
        ? ($errors->first('files.'.$name) ?: $errors->first('files.'.$name.'.*'))
        : ($errors->first($model) ?: $errors->first($model.'.*'));
    $options = $field->optionList();
    $inline = (bool) $field->setting('inline');
    $locked = $field->isLocked();
@endphp

<div @class(['fb-field', 'fb-half' => $field->width === 'half', 'fb-has-error' => $error, 'fb-locked' => $locked]) wire:key="fb-field-{{ $field->id }}">
    @if (! $field->isSingleCheckbox())
        <label class="fb-label" for="{{ $id }}">
            {{ $field->label }}
            @if ($field->is_required) <span class="fb-required" aria-hidden="true">*</span> @endif
            @if ($locked)
                <span class="fb-lock" title="از پروفایل شما خوانده شده و قابل تغییر نیست">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    از پروفایل
                </span>
            @elseif ($field->isProfile())
                <span class="fb-lock fb-lock-open">از پروفایل؛ قابل ویرایش</span>
            @endif
        </label>
    @endif

    @if ($field->isProfile())
        @php
            $type = $field->inputType();
            $htmlType = in_array($type, ['email', 'tel', 'number'], true) ? $type : 'text';
            $ltr = in_array($type, ['email', 'tel', 'number'], true) || in_array($field->format(), ['mobile', 'national_code', 'email', 'digits'], true);
        @endphp
        <input type="{{ $htmlType }}" id="{{ $id }}" wire:model="{{ $model }}" @if ($locked) readonly tabindex="-1" @endif
               @class(['fb-input', 'fb-ltr' => $ltr]) placeholder="{{ $locked ? 'ثبت نشده' : $field->placeholder }}">

    @else
        @switch($field->type)
            @case('text')
                @php
                    $ltr = in_array($field->format(), ['email', 'mobile', 'national_code', 'postal_code', 'digits', 'latin', 'url'], true);
                    $inputMode = in_array($field->format(), ['mobile', 'national_code', 'postal_code', 'digits'], true) ? 'numeric' : null;
                @endphp
                <input type="{{ $field->format() === 'email' ? 'email' : 'text' }}" id="{{ $id }}" wire:model="{{ $model }}"
                       @class(['fb-input', 'fb-ltr' => $ltr]) placeholder="{{ $field->placeholder }}"
                       @if ($inputMode) inputmode="{{ $inputMode }}" @endif
                       @if ($field->setting('max_length')) maxlength="{{ (int) $field->setting('max_length') }}" @endif>
                @break

            @case('number')
                <input type="text" inputmode="decimal" id="{{ $id }}" wire:model="{{ $model }}" class="fb-input fb-ltr" placeholder="{{ $field->placeholder }}">
                @if (is_numeric($field->setting('min')) || is_numeric($field->setting('max')))
                    <div class="fb-hint">
                        @if (is_numeric($field->setting('min'))) حداقل {{ $field->setting('min') }} @endif
                        @if (is_numeric($field->setting('min')) && is_numeric($field->setting('max'))) — @endif
                        @if (is_numeric($field->setting('max'))) حداکثر {{ $field->setting('max') }} @endif
                    </div>
                @endif
                @break

            @case('textarea')
                <textarea id="{{ $id }}" wire:model="{{ $model }}" class="fb-input" rows="{{ (int) $field->setting('rows', 4) }}" placeholder="{{ $field->placeholder }}"
                          @if ($field->setting('max_length')) maxlength="{{ (int) $field->setting('max_length') }}" @endif></textarea>
                @break

            @case('select')
                @if ($field->isMultiple())
                    {{-- انتخاب چندگانه: لیست کشویی با چک‌باکس --}}
                    <div class="fb-multiselect" x-data="{ open: false }" x-on:click.outside="open = false" x-on:keydown.escape="open = false">
                        <button type="button" id="{{ $id }}" class="fb-input fb-multiselect-toggle" x-on:click="open = !open" :aria-expanded="open">
                            @php
                                $selected = (array) ($values[$name] ?? []);
                            @endphp
                            @if ($selected)
                                <span class="fb-chips">
                                    @foreach ($selected as $item)
                                        <span class="fb-chip">{{ $item }}</span>
                                    @endforeach
                                </span>
                            @else
                                <span class="fb-placeholder">{{ $field->placeholder ?: 'انتخاب کنید (چند گزینه)' }}</span>
                            @endif
                            <svg class="fb-caret" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><polyline points="6 9 12 15 18 9"/></svg>
                        </button>
                        <div class="fb-multiselect-menu" x-show="open" x-cloak x-transition.opacity>
                            @foreach ($options as $option)
                                <label class="fb-option">
                                    <input type="checkbox" value="{{ $option }}" wire:model.live="{{ $model }}">
                                    <span>{{ $option }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @else
                    <select id="{{ $id }}" wire:model="{{ $model }}" class="fb-input fb-select">
                        <option value="">{{ $field->placeholder ?: 'انتخاب کنید' }}</option>
                        @foreach ($options as $option)
                            <option value="{{ $option }}">{{ $option }}</option>
                        @endforeach
                    </select>
                @endif
                @break

            @case('checkbox')
                @if ($field->isSingleCheckbox())
                    <label class="fb-option fb-single" for="{{ $id }}">
                        <input type="checkbox" id="{{ $id }}" wire:model="{{ $model }}">
                        <span>{{ $field->label }} @if ($field->is_required)<span class="fb-required">*</span>@endif</span>
                    </label>
                @else
                    <div @class(['fb-options', 'fb-inline' => $inline]) role="group" aria-labelledby="{{ $id }}">
                        @foreach ($options as $option)
                            <label class="fb-option">
                                <input type="checkbox" value="{{ $option }}" wire:model="{{ $model }}" @if ($loop->first) id="{{ $id }}" @endif>
                                <span>{{ $option }}</span>
                            </label>
                        @endforeach
                    </div>
                @endif
                @break

            @case('radio')
                <div @class(['fb-options', 'fb-inline' => $inline]) role="radiogroup">
                    @foreach ($options as $option)
                        <label class="fb-option">
                            <input type="radio" name="{{ $id }}" value="{{ $option }}" wire:model="{{ $model }}" @if ($loop->first) id="{{ $id }}" @endif>
                            <span>{{ $option }}</span>
                        </label>
                    @endforeach
                </div>
                @break

            @case('file')
                @php
                    $extensions = \Hamin\FormBuilder\Support\FieldTypes::allowedExtensions((array) $field->setting('extensions'));
                    $multiple = $field->isMultiple();
                    $chosen = $files[$name] ?? null;
                    $chosen = $chosen ? (is_array($chosen) ? $chosen : [$chosen]) : [];
                    $maxKb = (int) $field->setting('max_size_kb');
                @endphp
                <div x-data="{ progress: 0, uploading: false }"
                     x-on:livewire-upload-start="uploading = true; progress = 0"
                     x-on:livewire-upload-finish="uploading = false"
                     x-on:livewire-upload-error="uploading = false"
                     x-on:livewire-upload-progress="progress = $event.detail.progress">
                    <label class="fb-dropzone" for="{{ $id }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                        <span>{{ $multiple ? 'انتخاب فایل‌ها' : 'انتخاب فایل' }}</span>
                        <small>
                            {{ implode('، ', $extensions) }}
                            — حداکثر {{ $maxKb >= 1024 ? round($maxKb / 1024, 1).' مگابایت' : $maxKb.' کیلوبایت' }}
                            @if ($multiple) — تا {{ (int) $field->setting('max_files') }} فایل @endif
                        </small>
                    </label>
                    <input type="file" id="{{ $id }}" class="fb-file-input" wire:model="files.{{ $name }}" @if ($multiple) multiple @endif
                           accept="{{ implode(',', array_map(fn ($e) => '.'.$e, $extensions)) }}">

                    <div class="fb-progress" x-show="uploading" x-cloak><span :style="'width:' + progress + '%'"></span></div>

                    @if ($chosen)
                        <ul class="fb-file-list">
                            @foreach ($chosen as $index => $file)
                                @if (is_object($file) && method_exists($file, 'getClientOriginalName'))
                                    <li>
                                        <span class="fb-file-name">{{ $file->getClientOriginalName() }}</span>
                                        <span class="fb-hint">{{ number_format($file->getSize() / 1024) }} کیلوبایت</span>
                                        <button type="button" class="fb-file-remove" wire:click="removeFile('{{ $name }}'{{ $multiple ? ', '.$index : '' }})" aria-label="حذف فایل">✕</button>
                                    </li>
                                @endif
                            @endforeach
                        </ul>
                    @endif
                </div>
                @break
        @endswitch
    @endif

    @if ($field->help)
        <div class="fb-hint">{{ $field->help }}</div>
    @endif
    @if ($error)
        <div class="fb-error">{{ $error }}</div>
    @endif
</div>
