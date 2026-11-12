<?php

namespace App\Models\Concerns;

// ساخت slug مشترک برای مدل‌هایی که ستون slug دارند (دسته‌بندی، برچسب، ...)
trait HasSlug
{
    /**
     * ساخت slug از متن (فارسی و انگلیسی)
     * فاصله‌ها به _ تبدیل می‌شوند، علائم حذف می‌شوند و حروف انگلیسی کوچک می‌شوند.
     * مثال: «هنرهای  تجسمی!» => «هنرهای_تجسمی»
     */
    public static function makeSlug(string $text): string
    {
        // یکسان‌سازی حروف عربی با فارسی و حذف نیم‌فاصله (کتاب‌ها => کتابها)
        $text = str_replace(['ي', 'ك', 'ة', "\u{200C}"], ['ی', 'ک', 'ه', ''], $text);

        $text = mb_strtolower(trim($text));
        $text = preg_replace('/[\s\-]+/u', '_', $text);      // فاصله و خط تیره => _
        $text = preg_replace('/[^\p{L}\p{N}_]/u', '', $text); // فقط حرف، عدد و _
        $text = preg_replace('/_+/', '_', $text);             // حذف _ های تکراری

        return mb_substr(trim($text, '_'), 0, 190);
    }

    // slug یکتا (در صورت تکراری بودن، _2 و _3 و ... اضافه می‌شود)
    public static function uniqueSlug(string $text, ?int $ignoreId = null): string
    {
        $base = static::makeSlug($text);
        $slug = $base;
        $i = 2;

        while ($base !== '' && static::where('slug', $slug)->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            $slug = $base.'_'.$i++;
        }

        return $slug;
    }
}
