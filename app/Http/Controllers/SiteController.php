<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Comment;
use App\Models\Event;
use App\Models\PageView;
use App\Models\Post;
use App\Models\Slider;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

// صفحه‌های سایت عمومی (فقط محتوای منتشرشده نمایش داده می‌شود)
class SiteController extends Controller
{
    public function home()
    {
        $latest = fn (string $type, int $count) => Post::published()->where('type', $type)
            ->latest('published_at')->take($count)->get();

        return view('site.home', [
            'sliders' => Slider::active()->get(),
            'visuals' => $latest('visual', 4),
            'audios' => $latest('audio', 2),
            'videos' => $latest('video', 2),
            'poems' => $latest('poem', 4),
        ]);
    }

    // آرشیو مطالب با فیلتر نوع، موضوع، برچسب و جستجو
    public function posts(Request $request)
    {
        $type = array_key_exists($request->query('type'), Post::TYPES) ? $request->query('type') : null;
        $category = $request->filled('category') ? Category::where('slug', $request->query('category'))->first() : null;
        $tag = $request->filled('tag') ? Tag::where('slug', $request->query('tag'))->first() : null;
        $q = trim((string) $request->query('q'));

        $posts = Post::published()
            ->when($type, fn ($query) => $query->where('type', $type))
            ->when($category, fn ($query) => $query->whereHas('categories', fn ($c) => $c->whereIn('categories.id', $category->descendantIds())))
            ->when($tag, fn ($query) => $query->whereHas('tags', fn ($t) => $t->whereKey($tag->id)))
            ->when($q !== '', function ($query) use ($q) {
                $like = '%'.addcslashes($q, '%_\\').'%';
                $query->where(fn ($w) => $w->where('title', 'like', $like)->orWhere('creator_name', 'like', $like)->orWhere('content', 'like', $like));
            })
            ->latest('published_at')
            ->paginate(12)
            ->withQueryString();

        $title = match (true) {
            $q !== '' => 'نتایج جستجو برای «'.$q.'»',
            (bool) $category => $category->name,
            (bool) $tag => 'برچسب: '.$tag->name,
            (bool) $type => Post::TYPES[$type]['label'],
            default => 'همه آثار و مطالب',
        };

        // جستجو در هنرمندان هم نمایش داده می‌شود
        $like = '%'.addcslashes($q, '%_\\').'%';
        $artists = $q !== ''
            ? User::where('artist_status', 'approved')
                ->where(fn ($w) => $w->where('first_name', 'like', $like)->orWhere('last_name', 'like', $like))
                ->take(6)->get()
            : collect();

        return view('site.posts', compact('posts', 'type', 'category', 'tag', 'q', 'title', 'artists'));
    }

    public function post(Post $post)
    {
        abort_unless($post->status === 'published' && $post->published_at?->isPast(), 404);

        $this->recordView($post);

        $post->load(['user.artSpecialties', 'categories', 'tags']);

        $comments = $post->comments()
            ->whereNull('parent_id')->where('status', 'approved')
            ->with(['replies' => fn ($q) => $q->where('status', 'approved')->oldest()])
            ->oldest()->get();

        $related = Post::published()->where('type', $post->type)->whereKeyNot($post->id)
            ->latest('published_at')->take(4)->get();

        return view('site.post', compact('post', 'comments', 'related'));
    }

    public function storeComment(Request $request, Post $post)
    {
        abort_unless($post->status === 'published' && $post->published_at?->isPast(), 404);

        // فیلد مخفی برای ربات‌ها
        if ($request->filled('website')) {
            return back();
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'contact' => ['required', 'string', 'max:150'],
            'body' => ['required', 'string', 'min:3', 'max:2000'],
        ], [
            'required' => ':attribute را وارد کنید.',
            'max' => ':attribute نباید بیشتر از :max کاراکتر باشد.',
            'body.min' => 'متن دیدگاه خیلی کوتاه است.',
        ], ['name' => 'نام', 'contact' => 'ایمیل یا شماره تماس', 'body' => 'متن دیدگاه']);

        $post->comments()->create($data + [
            'status' => 'pending',
            'ip_address' => $request->ip(),
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 255),
        ]);

        return redirect()->to(route('site.post', $post).'#comments')
            ->with('success', 'دیدگاه شما ثبت شد و پس از تأیید نمایش داده می‌شود.');
    }

    public function artists()
    {
        $artists = User::where('artist_status', 'approved')
            ->with('artSpecialties')
            ->withCount(['posts' => fn ($q) => $q->published()])
            ->orderByDesc('posts_count')->orderBy('first_name')
            ->paginate(16);

        return view('site.artists', compact('artists'));
    }

    public function artist(string $username)
    {
        $artist = User::where('username', $username)->where('artist_status', 'approved')->with('artSpecialties')->firstOrFail();

        $posts = Post::published()->where('user_id', $artist->id)->latest('published_at')->paginate(12);

        return view('site.artist', compact('artist', 'posts'));
    }

    public function events(Request $request)
    {
        $past = $request->query('show') === 'past';

        $events = Event::where('is_active', true)
            ->when($past,
                fn ($q) => $q->where('ends_at', '<', now())->latest('starts_at'),
                fn ($q) => $q->where('ends_at', '>=', now())->orderBy('starts_at'))
            ->paginate(12)
            ->withQueryString();

        return view('site.events', compact('events', 'past'));
    }

    public function event(Event $event)
    {
        abort_unless($event->is_active, 404);

        $others = Event::upcoming()->whereKeyNot($event->id)->take(4)->get();

        return view('site.event', compact('event', 'others'));
    }

    // ثبت بازدید: هر مطلب در هر نشست فقط یک بار شمرده می‌شود
    private function recordView(Post $post): void
    {
        $key = 'viewed_posts';
        $viewed = session($key, []);

        if (in_array($post->id, $viewed, true)) {
            return;
        }

        session()->put($key, array_slice([...$viewed, $post->id], -200));

        $request = request();

        PageView::create([
            'viewable_type' => $post->getMorphClass(),
            'viewable_id' => $post->id,
            'url' => mb_substr($request->fullUrl(), 0, 2048),
            'path' => mb_substr('/'.ltrim($request->path(), '/'), 0, 255),
            'referer' => $request->headers->get('referer') ? mb_substr($request->headers->get('referer'), 0, 2048) : null,
            'ip_address' => $request->ip(),
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 255),
            'user_id' => auth()->id(),
            'viewed_at' => now(),
        ]);

        // بدون تغییر updated_at مطلب
        \Illuminate\Support\Facades\DB::table('posts')->where('id', $post->id)->increment('views_count');
    }
}
