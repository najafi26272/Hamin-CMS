@php
    $date = fn ($d) => function_exists('jdate') ? jdate($d->copy()->setTimezone('Asia/Tehran'))->format('Y/m/d H:i') : $d->format('Y-m-d H:i');
    $userName = fn ($u) => $u ? (trim(($u->first_name ?? '').' '.($u->last_name ?? '')) ?: ($u->name ?? '#'.$u->getKey())) : 'مهمان';
@endphp

<div class="fb-admin">
    @include('form-builder::admin.partials.styles')

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <a href="{{ route('form-builder.admin.forms.index') }}" class="small text-muted">→ بازگشت به فرم‌ها</a>
            <h5 class="mb-0 mt-1">پاسخ‌های «{{ $form->title }}» <span class="text-muted small">({{ number_format($submissions->total()) }})</span></h5>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('form-builder.admin.forms.edit', $form) }}" class="btn btn-outline-secondary">ویرایش فرم</a>
            @if ($submissions->total())
                <a href="{{ route('form-builder.admin.submissions.export', $form) }}" class="btn btn-success">خروجی اکسل (CSV)</a>
            @endif
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                    <tr>
                        <th>#</th>
                        <th>تاریخ</th>
                        <th>کاربر</th>
                        @foreach ($columns as $column)
                            <th>{{ $column->label }}</th>
                        @endforeach
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($submissions as $submission)
                        <tr wire:key="fb-sub-{{ $submission->id }}" style="cursor: pointer" wire:click="show({{ $submission->id }})">
                            <td>{{ $submission->id }}</td>
                            <td class="text-nowrap">{{ $date($submission->created_at) }}</td>
                            <td>{{ $userName($submission->user) }}</td>
                            @foreach ($columns as $column)
                                <td>{{ \Illuminate\Support\Str::limit($submission->displayValue($column->name), 40) }}</td>
                            @endforeach
                            <td class="text-nowrap" wire:click.stop>
                                <button type="button" class="btn btn-sm btn-outline-primary" wire:click="show({{ $submission->id }})">جزئیات</button>
                                <button type="button" class="btn btn-sm btn-outline-danger" wire:click="delete({{ $submission->id }})" wire:confirm="این پاسخ و فایل‌هایش حذف شود؟">حذف</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ 4 + $columns->count() }}" class="text-center text-muted py-5">هنوز پاسخی ثبت نشده است.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">{{ $submissions->links() }}</div>
        </div>
    </div>

    {{-- جزئیات پاسخ --}}
    @if ($viewing)
        <div class="modal d-block" tabindex="-1" wire:key="fb-sub-modal-{{ $viewing->id }}"
             x-data x-on:keydown.escape.window="$wire.close()" x-on:click.self="$wire.close()">
            <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">پاسخ شماره {{ $viewing->id }}</h5>
                        <button type="button" class="btn-close" wire:click="close" aria-label="بستن"></button>
                    </div>
                    <div class="modal-body">
                        <div class="d-flex flex-wrap gap-3 small text-muted mb-3">
                            <span>تاریخ: {{ $date($viewing->created_at) }}</span>
                            <span>کاربر: {{ $userName($viewing->user) }}</span>
                            @if ($viewing->ip_address)
                                <span dir="ltr">IP: {{ $viewing->ip_address }}</span>
                            @endif
                        </div>

                        @php
                            // ترتیب فیلدهای فعلی فرم، سپس فیلدهای حذف‌شده‌ای که در این پاسخ هستند
                            $names = $form->fields->pluck('name')->merge(array_keys((array) $viewing->data))->unique();
                        @endphp

                        <table class="table table-bordered mb-0">
                            <tbody>
                            @foreach ($names as $name)
                                @php
                                    $value = $viewing->data[$name] ?? null;
                                    $label = $viewing->labels[$name] ?? $form->fields->firstWhere('name', $name)?->label ?? $name;
                                @endphp
                                <tr>
                                    <th style="width: 35%" class="bg-light">{{ $label }}</th>
                                    <td>
                                        @if (\Hamin\FormBuilder\Models\FormSubmission::isFileValue($value))
                                            @foreach ($value as $index => $file)
                                                <div>
                                                    <a href="{{ route('form-builder.admin.submissions.file', [$viewing, $name, $index]) }}">{{ $file['name'] }}</a>
                                                    <small class="text-muted">({{ number_format(($file['size'] ?? 0) / 1024) }} کیلوبایت)</small>
                                                </div>
                                            @endforeach
                                        @else
                                            <div class="fb-sub-value">{{ $viewing->displayValue($name) ?: '—' }}</div>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-danger me-auto" wire:click="delete({{ $viewing->id }})" wire:confirm="این پاسخ و فایل‌هایش حذف شود؟">حذف پاسخ</button>
                        <button type="button" class="btn btn-secondary" wire:click="close">بستن</button>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-backdrop show"></div>
    @endif
</div>
