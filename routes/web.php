<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Route;

include 'site.php';

Route::livewire('/login', 'auth::login')
    ->middleware('guest')
    ->name('login');

// ثبت‌نام عمومی (نام، نام خانوادگی، نام کاربری و رمز)؛ درخواست هنرمندی بعدا از داخل پنل
Route::livewire('/register', 'auth::register')
    ->middleware(['guest', 'throttle:10,1'])
    ->name('register');



// پنل مدیریت و کاربری زیر /panel (تا با آدرس‌های سایت عمومی مثل /posts و /events تداخل نداشته باشد)
Route::middleware('auth')->prefix('panel')->group(function () {
    Route::livewire('/dashboard', 'admin::dashboard')->name('dashboard');

    // درخواست هنرمندی (برای همه کاربران واردشده)
    Route::livewire('/artist-request', 'admin::artist.apply')->name('artist.apply');

    include 'user.php';
    include 'roles.php';
    include 'permissions.php';
    include 'categories.php';
    include 'tags.php';
    include 'posts.php';
    include 'galleries.php';
    include 'videos.php';
    include 'comments.php';
    include 'sliders.php';
    include 'events.php';
    include 'social-links.php';
    include 'settings.php';

    Route::get('/logout', function () {
        Auth::logout();
        return redirect()->route('login');
    })->name('logout');
});
