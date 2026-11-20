<?php

namespace App\Providers;

use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->loadMigrationsFrom(
            glob(database_path('migrations/*'), GLOB_ONLYDIR)
        );

        // مدیر اصلی به همه بخش‌ها دسترسی دارد
        Gate::before(fn (User $user) => $user->isAdmin() ? true : null);

        // برای هر مجوز config/permissions.php یک Gate هم‌نام
        foreach (array_keys(config('permissions')) as $permission) {
            Gate::define($permission, fn (User $user) => $user->hasPermission($permission));
        }

        // دیدن لیست کاربران / مطالب با مجوز مدیریت یا ویرایش
        Gate::define('view-users', fn (User $user) => $user->hasPermission('manage-users') || $user->hasPermission('edit-users'));
        Gate::define('view-content', fn (User $user) => $user->hasPermission('manage-content') || $user->hasPermission('edit-content'));

        // مطالب: کارکنان همه مطالب را می‌بینند، هنرمندان (ارسال مطلب) فقط مطالب خودشان را
        Gate::define('list-posts', fn (User $user) => Gate::forUser($user)->any(['view-content', 'review-content', 'submit-content']));
        Gate::define('create-post', fn (User $user) => $user->hasPermission('manage-content') || $user->hasPermission('submit-content'));
        Gate::define('choose-author', fn (User $user) => $user->hasPermission('choose-author') );
        Gate::define('update-post', fn (User $user, Post $post) => $user->hasPermission('edit-content')
            || $user->hasPermission('review-content')
            || ($user->hasPermission('submit-content') && $post->user_id === $user->id));
        Gate::define('delete-post', fn (User $user, Post $post) => $user->hasPermission('manage-content')
            || ($user->hasPermission('submit-content') && $post->user_id === $user->id));
    }
}
