<?php

namespace App\Models;

use App\Models\Concerns\HasSlug;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasSlug;

    protected $fillable = ['parent_id', 'name', 'slug', 'description'];

    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function posts()
    {
        return $this->belongsToMany(Post::class);
    }

    // لیست درختی برای select: «هنر»، «— نقاشی»، «— — آبرنگ»
    public static function treeOptions(array $excludeIds = []): array
    {
        $grouped = static::orderBy('name')->get(['id', 'name', 'parent_id'])->groupBy('parent_id');
        $options = [];

        $walk = function ($parentId, $depth) use (&$walk, &$options, $grouped, $excludeIds) {
            foreach ($grouped->get($parentId ?? '', []) as $item) {
                if (in_array($item->id, $excludeIds)) {
                    continue;
                }

                $options[] = ['id' => $item->id, 'name' => str_repeat('— ', $depth).$item->name];
                $walk($item->id, $depth + 1);
            }
        };

        $walk(null, 0);

        return $options;
    }

    // شناسه همه زیرمجموعه‌ها (برای جلوگیری از انتخاب زیرمجموعه به عنوان والد)
    public function descendantIds(): array
    {
        $ids = [];
        $current = [$this->id];

        while ($current) {
            $current = static::whereIn('parent_id', $current)->pluck('id')->all();
            $ids = array_merge($ids, $current);
        }

        return $ids;
    }
}
