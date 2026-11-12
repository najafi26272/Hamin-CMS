<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Comment;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * محتوای تستی: ۱۰ مطلب همراه با دسته‌بندی، برچسب، آمار بازدید یک سال اخیر و دیدگاه‌ها
 * اجرا: php artisan db:seed --class=DemoContentSeeder
 * (در DatabaseSeeder صدا زده نمی‌شود تا روی سرور اصلی اجرا نشود)
 */
class DemoContentSeeder extends Seeder
{
    public function run(): void
    {
        $author = User::where('username', 'admin')->firstOrFail();

        $categories = collect(['نقاشی', 'خوشنویسی', 'عکاسی', 'موسیقی', 'صنایع دستی'])
            ->mapWithKeys(fn ($name) => [$name => Category::firstOrCreate(['slug' => Category::makeSlug($name)], ['name' => $name])]);

        $tags = collect(['آبرنگ', 'نستعلیق', 'نمایشگاه', 'کارگاه', 'هنر معاصر', 'سفال'])
            ->mapWithKeys(fn ($name) => [$name => Tag::firstOrCreate(['slug' => Tag::makeSlug($name)], ['name' => $name])]);

        // [عنوان، دسته‌بندی، برچسب‌ها، چند روز پیش منتشر شده، حداکثر بازدید]
        $posts = [
            ['نمایشگاه گروهی «رنگ‌های پاییز» با آثار ۲۴ هنرمند جوان افتتاح شد', 'نقاشی', ['آبرنگ', 'نمایشگاه'], 6, 900],
            ['ثبت‌نام کارگاه خوشنویسی نستعلیق ویژه نوجوانان آغاز شد', 'خوشنویسی', ['نستعلیق', 'کارگاه'], 12, 700],
            ['روایت تصویری از بازار قدیمی شهر در قاب عکاسان مجمع', 'عکاسی', ['نمایشگاه'], 25, 500],
            ['نگاهی به هنر سفالگری و نقش‌های کهن ایرانی', 'صنایع دستی', ['سفال'], 40, 650],
            ['آموزش مقدماتی آبرنگ؛ از انتخاب کاغذ تا اولین منظره', 'نقاشی', ['آبرنگ', 'کارگاه'], 70, 1200],
            ['شب موسیقی سنتی با اجرای گروه جوان مجمع برگزار شد', 'موسیقی', ['هنر معاصر'], 95, 400],
            ['چگونه یک اثر خوشنویسی را بخوانیم و از آن لذت ببریم؟', 'خوشنویسی', ['نستعلیق'], 150, 800],
            ['هنر معاصر ایران در دهه اخیر؛ گفت‌وگو با هنرمندان جوان', 'نقاشی', ['هنر معاصر'], 230, 350],
            ['فراخوان نمایشگاه سالانه عکس مجمع هنری هامین', 'عکاسی', ['نمایشگاه'], 0, 0],      // پیش‌نویس
            ['برنامه کارگاه‌های هنری زمستان ۱۴۰۵ اعلام می‌شود', 'صنایع دستی', ['کارگاه'], -5, 0], // زمان‌بندی شده
        ];

        $ips = collect(range(1, 400))->map(fn () => long2ip(random_int(1_000_000_000, 3_000_000_000)))->all();
        $agents = [
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/130.0 Safari/537.36',
            'Mozilla/5.0 (iPhone; CPU iPhone OS 17_5 like Mac OS X) Safari/604.1',
            'Mozilla/5.0 (Linux; Android 14) Chrome/130.0 Mobile Safari/537.36',
        ];

        foreach ($posts as $i => [$title, $category, $postTags, $daysAgo, $maxViews]) {
            $slug = Post::makeSlug($title);

            if (Post::withTrashed()->where('slug', $slug)->exists()) {
                continue; // قبلا ساخته شده است
            }

            $status = $maxViews === 0 && $daysAgo === 0 ? 'draft' : 'published';
            $publishedAt = $status === 'draft' ? null : now()->subDays($daysAgo)->setTime(random_int(8, 20), random_int(0, 59));

            $post = Post::create([
                'user_id' => $author->id,
                'title' => $title,
                'slug' => $slug,
                'content' => $this->content($title),
                'status' => $status,
                'published_at' => $publishedAt,
            ]);

            $post->categories()->sync([$categories[$category]->id]);
            $post->tags()->sync(collect($postTags)->map(fn ($name) => $tags[$name]->id));

            if ($maxViews === 0 || $publishedAt->isFuture()) {
                continue;
            }

            // بازدیدها بین زمان انتشار و الان پخش می‌شوند (روزهای اخیر بیشتر)
            $count = random_int((int) ($maxViews * 0.6), $maxViews);
            $rows = [];
            $seconds = max(1, now()->diffInSeconds($publishedAt, true));

            for ($v = 0; $v < $count; $v++) {
                $rows[] = [
                    'viewable_type' => Post::class,
                    'viewable_id' => $post->id,
                    'url' => url('posts/'.$post->slug),
                    'path' => 'posts/'.$post->slug,
                    'ip_address' => $ips[array_rand($ips)],
                    'user_agent' => $agents[array_rand($agents)],
                    'viewed_at' => now()->subSeconds((int) ($seconds * (mt_rand() / mt_getrandmax()) ** 2)),
                ];
            }

            foreach (array_chunk($rows, 500) as $chunk) {
                DB::table('page_views')->insert($chunk);
            }

            $post->update(['views_count' => $count]);
        }

        // بازدید صفحه اول سایت در یک سال اخیر
        if (! DB::table('page_views')->where('path', '/')->exists()) {
            $rows = [];
            for ($v = 0; $v < 2500; $v++) {
                $rows[] = [
                    'url' => url('/'),
                    'path' => '/',
                    'ip_address' => $ips[array_rand($ips)],
                    'user_agent' => $agents[array_rand($agents)],
                    'viewed_at' => now()->subSeconds((int) (365 * 86400 * (mt_rand() / mt_getrandmax()) ** 2)),
                ];
            }

            foreach (array_chunk($rows, 500) as $chunk) {
                DB::table('page_views')->insert($chunk);
            }
        }

        $this->seedComments($author);
    }

    // دیدگاه‌های تستی با وضعیت‌های مختلف و چند پاسخ مدیر
    private function seedComments(User $admin): void
    {
        if (Comment::withTrashed()->exists()) {
            return;
        }

        $posts = Post::where('status', 'published')->where('published_at', '<=', now())->orderByDesc('views_count')->get();

        $samples = [
            ['مریم احمدی', 'maryam@example.com', 'نمایشگاه خیلی خوبی بود. آیا آثار برای فروش هم هستند؟', 'approved'],
            ['علی رضایی', '09121234567', 'ساعت بازدید روزهای جمعه چطور است؟', 'pending'],
            ['سارا کریمی', 'sara.k@example.com', 'ممنون از مطلب مفید. لطفا درباره تکنیک خیس در خیس آبرنگ هم بنویسید.', 'approved'],
            ['حسین موسوی', 'hossein@example.com', 'برای ثبت‌نام کارگاه حد سنی وجود دارد؟', 'pending'],
            ['زهرا نوری', '09351112233', 'عکس‌ها فوق‌العاده بودند، دست عکاسان درد نکند.', 'approved'],
            ['کاربر ناشناس', 'spam@spam.test', 'بهترین قیمت خرید فالوور اینستاگرام! همین الان کلیک کنید', 'spam'],
            ['نرگس جعفری', 'narges@example.com', 'کاش این کارگاه‌ها به صورت آنلاین هم برگزار شوند.', 'pending'],
            ['محمد صادقی', 'm.sadeghi@example.com', 'مطلب کاملی بود، فقط چند غلط تایپی در پاراگراف دوم دیده می‌شود.', 'approved'],
            ['فاطمه حسینی', '09129876543', 'آیا برای کودکان زیر ۱۰ سال هم کلاس دارید؟', 'pending'],
            ['امیر قاسمی', 'amir@example.com', 'موسیقی آن شب واقعا دلنشین بود. منتظر برنامه بعدی هستیم.', 'approved'],
            ['تبلیغات', 'ads@ads.test', 'سایت شما را با ۵۰ درصد تخفیف سئو می‌کنیم', 'spam'],
            ['لیلا مرادی', 'leila@example.com', 'لطفا آدرس دقیق سالن را هم اعلام کنید.', 'pending'],
        ];

        foreach ($samples as $i => [$name, $contact, $body, $status]) {
            $post = $posts[$i % $posts->count()];

            $comment = Comment::create([
                'post_id' => $post->id,
                'name' => $name,
                'contact' => $contact,
                'body' => $body,
                'status' => $status,
                'ip_address' => long2ip(random_int(1_000_000_000, 3_000_000_000)),
            ]);
            $comment->forceFill(['created_at' => now()->subHours(random_int(1, 24 * 20))])->save();

            // پاسخ مدیر به چند دیدگاه تأیید شده
            if ($status === 'approved' && $i % 2 === 0) {
                $reply = Comment::create([
                    'post_id' => $post->id,
                    'parent_id' => $comment->id,
                    'user_id' => $admin->id,
                    'name' => trim($admin->first_name.' '.$admin->last_name),
                    'contact' => $admin->email,
                    'body' => 'سپاس از همراهی شما. اطلاعات تکمیلی به‌زودی در همین صفحه منتشر می‌شود.',
                    'status' => 'approved',
                ]);
                $reply->forceFill(['created_at' => $comment->created_at->copy()->addHours(3)])->save();
            }
        }
    }

    private function content(string $title): string
    {
        return '<h2>'.e($title).'</h2>'
            .'<p>این یک متن آزمایشی برای نمایش مطالب در پنل مدیریت و سایت مجمع هنری هامین است. '
            .'در این بخش خلاصه‌ای از رویداد، نام هنرمندان شرکت‌کننده و زمان و مکان برگزاری نوشته می‌شود.</p>'
            .'<p>هنر، زبانی مشترک میان مردم است و مجمع هنری هامین تلاش می‌کند فضایی برای دیده شدن هنرمندان، '
            .'برگزاری کارگاه‌ها و آشنایی علاقه‌مندان با شاخه‌های مختلف هنر فراهم کند.</p>'
            .'<ul><li>زمان: هفته آینده، ساعت ۱۷</li><li>مکان: سالن اصلی مجمع</li><li>ورود برای عموم آزاد است</li></ul>';
    }
}
