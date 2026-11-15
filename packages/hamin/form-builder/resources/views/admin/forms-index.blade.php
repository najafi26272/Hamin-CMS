<div class="fb-admin">
    @include('form-builder::admin.partials.styles')

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h5 class="mb-1">فرم‌ساز</h5>
            <p class="text-muted small mb-0">فرم بسازید، در سایت منتشر کنید و پاسخ‌ها را ببینید.</p>
        </div>
        <a href="{{ route('form-builder.admin.forms.create') }}" class="btn btn-primary">ساخت فرم جدید</a>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card mb-3">
        <div class="card-body py-3">
            <input type="search" wire:model.live.debounce.400ms="search" class="form-control" placeholder="جستجوی عنوان فرم..." style="max-width: 320px">
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive" wire:loading.class="opacity-50">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                    <tr>
                        <th>عنوان</th>
                        <th>آدرس</th>
                        <th class="text-center">فیلدها</th>
                        <th class="text-center">پاسخ‌ها</th>
                        <th>وضعیت</th>
                        <th>عملیات</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($forms as $form)
                        <tr wire:key="fb-form-{{ $form->id }}">
                            <td>
                                <a href="{{ route('form-builder.admin.forms.edit', $form) }}" class="fw-bold text-body">{{ $form->title }}</a>
                                <div class="small text-muted">
                                    {{ $form->requires_auth ? 'فقط کاربران واردشده' : 'عمومی' }}
                                    · {{ $form->allow_multiple ? 'ثبت چندباره' : 'یک بار برای هر کاربر' }}
                                </div>
                            </td>
                            <td>
                                @if (Route::has('form-builder.show'))
                                    <a href="{{ route('form-builder.show', $form->slug) }}" target="_blank" class="small" dir="ltr">/{{ trim(config('form-builder.public.prefix'), '/') }}/{{ $form->slug }}</a>
                                @endif
                            </td>
                            <td class="text-center">{{ $form->fields_count }}</td>
                            <td class="text-center">
                                <a href="{{ route('form-builder.admin.submissions.index', $form) }}" class="badge {{ $form->submissions_count ? 'bg-primary' : 'bg-secondary' }} text-decoration-none fs-6">{{ $form->submissions_count }}</a>
                            </td>
                            <td>
                                <div class="form-check form-switch mb-0">
                                    <input class="form-check-input" type="checkbox" role="switch" id="fb-active-{{ $form->id }}"
                                           @checked($form->is_active) wire:click="toggleActive({{ $form->id }})">
                                    <label class="form-check-label small" for="fb-active-{{ $form->id }}">{{ $form->is_active ? 'فعال' : 'غیرفعال' }}</label>
                                </div>
                            </td>
                            <td class="text-nowrap">
                                <a href="{{ route('form-builder.admin.forms.edit', $form) }}" class="btn btn-sm btn-outline-primary">ویرایش</a>
                                <a href="{{ route('form-builder.admin.submissions.index', $form) }}" class="btn btn-sm btn-outline-secondary">پاسخ‌ها</a>
                                <button type="button" class="btn btn-sm btn-outline-secondary" wire:click="duplicate({{ $form->id }})">کپی</button>
                                <button type="button" class="btn btn-sm btn-outline-danger"
                                        wire:click="delete({{ $form->id }})"
                                        wire:confirm="فرم «{{ $form->title }}» و دسترسی به پاسخ‌هایش حذف می‌شود. ادامه می‌دهید؟">حذف</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-5">
                                هنوز فرمی ساخته نشده است.
                                <a href="{{ route('form-builder.admin.forms.create') }}">اولین فرم را بسازید</a>
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">{{ $forms->links() }}</div>
        </div>
    </div>
</div>
