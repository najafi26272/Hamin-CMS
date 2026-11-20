<?php

use App\Models\Comment;
use App\Models\Post;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts::admin'), Title('دیدگاه‌ها')] class extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    #[Url(history: true)]
    public string $status = '';

    #[Url(as: 'q', history: true)]
    public string $search = '';

    #[Url(as: 'post', history: true)]
    public string $postId = '';

    public array $selected = [];      // دیدگاه‌های انتخاب شده برای عملیات گروهی

    public ?int $replyingTo = null;   // دیدگاهی که در حال پاسخ به آن هستیم
    public string $replyBody = '';

    public ?int $editingId = null;    // دیدگاهی که در حال ویرایش آن هستیم
    public string $editBody = '';

    public function updated($property): void
    {
        if (in_array($property, ['status', 'search', 'postId'])) {
            $this->resetPage();
            $this->selected = [];
        }
    }

    public function resetFilters(): void
    {
        $this->reset('status', 'search', 'postId', 'selected');
        $this->resetPage();
    }

    // تعداد دیدگاه‌ها در هر وضعیت (برای تب‌ها)
    #[Computed]
    public function counts(): array
    {
        $counts = Comment::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        return ['' => $counts->sum()] + collect(Comment::STATUSES)->map(fn ($label, $key) => $counts[$key] ?? 0)->all();
    }

    #[Computed]
    public function posts()
    {
        return Post::whereHas('comments')->orderBy('title')->get(['id', 'title']);
    }

    #[Computed]
    public function comments()
    {
        $term = trim($this->search);

        return Comment::query()
            ->with(['post:id,title', 'parent:id,name', 'user:id,first_name,last_name'])
            ->when($this->status !== '', fn ($q) => $q->where('status', $this->status))
            ->when($this->postId !== '', fn ($q) => $q->where('post_id', $this->postId))
            ->when($term !== '', function ($q) use ($term) {
                $like = '%'.addcslashes($term, '%_\\').'%';

                $q->where(fn ($w) => $w->where('name', 'like', $like)
                    ->orWhere('contact', 'like', $like)
                    ->orWhere('body', 'like', $like));
            })
            ->latest('id')
            ->paginate(15);
    }

    // ---------- تغییر وضعیت ----------

    public function setStatus(int $id, string $status): void
    {
        if (! array_key_exists($status, Comment::STATUSES)) {
            return;
        }

        Comment::whereKey($id)->update(['status' => $status]);

        $this->dispatch('notify', type: 'success', message: 'وضعیت دیدگاه به «'.Comment::STATUSES[$status].'» تغییر کرد.');
    }

    public function deleteComment(int $id): void
    {
        Comment::find($id)?->delete();
        $this->selected = array_values(array_diff($this->selected, [$id]));

        $this->dispatch('notify', type: 'success', message: 'دیدگاه با موفقیت حذف شد.');
    }

    // ---------- عملیات گروهی ----------

    public function selectPage(): void
    {
        $ids = $this->comments->pluck('id')->map(fn ($id) => (string) $id)->all();

        // اگر همه انتخاب شده بودند، انتخاب‌ها برداشته می‌شوند
        $this->selected = count(array_diff($ids, $this->selected)) === 0 ? [] : $ids;
    }

    public function bulk(string $action): void
    {
        if (empty($this->selected)) {
            return;
        }

        $comments = Comment::whereKey($this->selected);
        $count = count($this->selected);

        if ($action === 'delete') {
            $comments->get()->each->delete();
            $message = "{$count} دیدگاه حذف شد.";
        } elseif (array_key_exists($action, Comment::STATUSES)) {
            $comments->update(['status' => $action]);
            $message = "وضعیت {$count} دیدگاه به «".Comment::STATUSES[$action].'» تغییر کرد.';
        } else {
            return;
        }

        $this->selected = [];
        $this->dispatch('notify', type: 'success', message: $message);
    }

    // ---------- پاسخ ----------

    public function startReply(int $id): void
    {
        $this->cancelEdit();
        $this->replyingTo = $id;
        $this->replyBody = '';
        $this->resetValidation();
    }

    public function cancelReply(): void
    {
        $this->reset('replyingTo', 'replyBody');
        $this->resetValidation();
    }

    public function sendReply(): void
    {
        $this->validate(
            ['replyBody' => ['required', 'string', 'min:2', 'max:2000']],
            [
                'replyBody.required' => 'متن پاسخ را وارد کنید.',
                'replyBody.min' => 'متن پاسخ خیلی کوتاه است.',
                'replyBody.max' => 'متن پاسخ نباید بیشتر از ۲۰۰۰ کاراکتر باشد.',
            ]
        );

        $parent = Comment::findOrFail($this->replyingTo);
        $admin = auth()->user();

        Comment::create([
            'post_id' => $parent->post_id,
            'parent_id' => $parent->id,
            'user_id' => $admin->id,
            'name' => trim($admin->first_name.' '.$admin->last_name),
            'contact' => $admin->email,
            'body' => trim($this->replyBody),
            'status' => 'approved',
            'ip_address' => request()->ip(),
        ]);

        // پاسخ دادن به دیدگاه یعنی تأیید آن
        if ($parent->status === 'pending') {
            $parent->update(['status' => 'approved']);
        }

        $this->cancelReply();
        $this->dispatch('notify', type: 'success', message: 'پاسخ شما ثبت و منتشر شد.');
    }

    // ---------- ویرایش ----------

    public function startEdit(int $id): void
    {
        $this->cancelReply();
        $this->editingId = $id;
        $this->editBody = Comment::findOrFail($id)->body;
        $this->resetValidation();
    }

    public function cancelEdit(): void
    {
        $this->reset('editingId', 'editBody');
        $this->resetValidation();
    }

    public function saveEdit(): void
    {
        $this->validate(
            ['editBody' => ['required', 'string', 'max:2000']],
            [
                'editBody.required' => 'متن دیدگاه را وارد کنید.',
                'editBody.max' => 'متن دیدگاه نباید بیشتر از ۲۰۰۰ کاراکتر باشد.',
            ]
        );

        Comment::whereKey($this->editingId)->update(['body' => trim($this->editBody)]);

        $this->cancelEdit();
        $this->dispatch('notify', type: 'success', message: 'دیدگاه با موفقیت ویرایش شد.');
    }
};
?>

<div>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0">دیدگاه‌ها</h5>
    </div>

    {{-- تب‌های وضعیت --}}
    <ul class="nav nav-pills mb-3 gap-2">
        @foreach (['' => 'همه'] + \App\Models\Comment::STATUSES as $key => $label)
            <li class="nav-item">
                <button type="button" wire:click="$set('status', '{{ $key }}')"
                        @class(['nav-link', 'active' => $status === $key])>
                    {{ $label }}
                    <span @class(['badge ms-1', 'bg-danger' => $key === 'pending' && $this->counts[$key] > 0, 'bg-secondary' => ! ($key === 'pending' && $this->counts[$key] > 0)])>{{ $this->counts[$key] }}</span>
                </button>
            </li>
        @endforeach
    </ul>

    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-md-5">
                    <label for="search" class="form-label">جستجو</label>
                    <input type="text" id="search" wire:model.live.debounce.400ms="search" class="form-control" placeholder="نام، ایمیل/تلفن یا متن دیدگاه">
                </div>
                <div class="col-md-5">
                    <label for="postId" class="form-label">مطلب</label>
                    <select id="postId" wire:model.live="postId" class="form-select">
                        <option value="">همه مطالب</option>
                        @foreach ($this->posts as $post)
                            <option value="{{ $post->id }}">{{ \Illuminate\Support\Str::limit($post->title, 60) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="button" wire:click="resetFilters" class="btn btn-outline-secondary w-100">پاک کردن</button>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            {{-- عملیات گروهی --}}
            <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
                <button type="button" wire:click="selectPage" class="btn btn-sm btn-outline-secondary">انتخاب همه این صفحه</button>

                @if ($selected)
                    <span class="text-muted small mx-2">{{ count($selected) }} مورد انتخاب شده:</span>
                    <button type="button" wire:click="bulk('approved')" class="btn btn-sm btn-success">تأیید</button>
                    <button type="button" wire:click="bulk('pending')" class="btn btn-sm btn-warning">در انتظار</button>
                    <button type="button" wire:click="bulk('spam')" class="btn btn-sm btn-secondary">اسپم</button>
                    <button type="button" class="btn btn-sm btn-danger"
                            x-on:click="confirmDelete(() => $wire.bulk('delete'), '{{ count($selected) }} دیدگاه انتخاب شده و پاسخ‌هایشان حذف خواهند شد.')">حذف</button>
                @endif
            </div>

            <div wire:loading.class="opacity-50">
                @forelse ($this->comments as $comment)
                    <div wire:key="comment-{{ $comment->id }}" @class(['comment-item', 'comment-'.$comment->status, 'comment-admin' => $comment->isAdminReply()])>
                        <div class="d-flex gap-3">
                            <input type="checkbox" class="form-check-input mt-2" value="{{ $comment->id }}" wire:model.live="selected">

                            <div class="comment-avatar">{{ mb_substr($comment->name, 0, 1) }}</div>

                            <div class="flex-grow-1 min-w-0">
                                {{-- سربرگ --}}
                                <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                                    <strong>{{ $comment->name }}</strong>
                                    @if ($comment->isAdminReply())
                                        <span class="badge bg-primary">پاسخ مدیر</span>
                                    @endif
                                    <small class="text-muted" dir="ltr">{{ $comment->contact }}</small>
                                    <small class="text-muted">· {{ jdate($comment->created_at->copy()->setTimezone('Asia/Tehran'))->format('Y/m/d H:i') }}</small>

                                    @if ($comment->status === 'pending')
                                        <span class="badge bg-warning text-dark me-auto">در انتظار تأیید</span>
                                    @elseif ($comment->status === 'approved')
                                        <span class="badge bg-success me-auto">تأیید شده</span>
                                    @else
                                        <span class="badge bg-secondary me-auto">اسپم</span>
                                    @endif
                                </div>

                                <div class="small text-muted mb-2">
                                    در مطلب:
                                    @if ($comment->post)
                                        @can('edit-content')
                                            <a href="{{ route('posts.edit', ['post' => $comment->post_id]) }}">{{ \Illuminate\Support\Str::limit($comment->post->title, 70) }}</a>
                                        @else
                                            {{ \Illuminate\Support\Str::limit($comment->post->title, 70) }}
                                        @endcan
                                    @else
                                        <span>مطلب حذف شده</span>
                                    @endif

                                    @if ($comment->parent)
                                        <span class="ms-2">— در پاسخ به «{{ $comment->parent->name }}»</span>
                                    @endif
                                </div>

                                {{-- متن یا فرم ویرایش --}}
                                @if ($editingId === $comment->id)
                                    <div class="mb-2">
                                        <textarea wire:model="editBody" rows="3" class="form-control @error('editBody') is-invalid @enderror"></textarea>
                                        @error('editBody') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        <div class="mt-2 d-flex gap-2">
                                            <button type="button" wire:click="saveEdit" class="btn btn-sm btn-primary">ذخیره</button>
                                            <button type="button" wire:click="cancelEdit" class="btn btn-sm btn-light">انصراف</button>
                                        </div>
                                    </div>
                                @else
                                    <p class="comment-body mb-2">{!! nl2br(e($comment->body)) !!}</p>
                                @endif

                                {{-- عملیات --}}
                                <div class="comment-actions">
                                    @if ($comment->status !== 'approved')
                                        <button type="button" wire:click="setStatus({{ $comment->id }}, 'approved')" class="btn btn-link text-success">تأیید</button>
                                    @endif
                                    @if ($comment->status !== 'pending')
                                        <button type="button" wire:click="setStatus({{ $comment->id }}, 'pending')" class="btn btn-link text-warning">در انتظار</button>
                                    @endif
                                    @if ($comment->status !== 'spam')
                                        <button type="button" wire:click="setStatus({{ $comment->id }}, 'spam')" class="btn btn-link text-secondary">اسپم</button>
                                    @endif
                                    <button type="button" wire:click="startReply({{ $comment->id }})" class="btn btn-link">پاسخ</button>
                                    <button type="button" wire:click="startEdit({{ $comment->id }})" class="btn btn-link">ویرایش</button>
                                    <button type="button" class="btn btn-link text-danger"
                                            x-on:click="confirmDelete(() => $wire.deleteComment({{ $comment->id }}), @js('دیدگاه «'.$comment->name.'» و پاسخ‌های آن حذف خواهند شد.'))">حذف</button>
                                </div>

                                {{-- فرم پاسخ --}}
                                @if ($replyingTo === $comment->id)
                                    <div class="reply-box mt-2">
                                        <label class="form-label small">پاسخ به {{ $comment->name }}</label>
                                        <textarea wire:model="replyBody" rows="3" class="form-control @error('replyBody') is-invalid @enderror" placeholder="متن پاسخ..." autofocus></textarea>
                                        @error('replyBody') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        @if ($comment->status === 'pending')
                                            <div class="form-text">با ثبت پاسخ، این دیدگاه هم تأیید می‌شود.</div>
                                        @endif
                                        <div class="mt-2 d-flex gap-2">
                                            <button type="button" wire:click="sendReply" class="btn btn-sm btn-primary" wire:loading.attr="disabled" wire:target="sendReply">ارسال پاسخ</button>
                                            <button type="button" wire:click="cancelReply" class="btn btn-sm btn-light">انصراف</button>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-center text-muted py-5">دیدگاهی پیدا نشد.</div>
                @endforelse
            </div>

            <div class="mt-3">
                {{ $this->comments->links() }}
            </div>
        </div>
    </div>
</div>

