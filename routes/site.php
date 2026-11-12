<?php

use App\Http\Controllers\SiteController;
use Illuminate\Support\Facades\Route;

// سایت عمومی
Route::controller(SiteController::class)->name('site.')->group(function () {
    Route::get('/', 'home')->name('home');
    Route::get('/posts', 'posts')->name('posts');
    Route::get('/post/{post:slug}', 'post')->name('post');
    Route::post('/post/{post:slug}/comments', 'storeComment')->middleware('throttle:5,1')->name('comments.store');
    Route::get('/artists', 'artists')->name('artists');
    Route::get('/artists/{username}', 'artist')->name('artist');
    Route::get('/events', 'events')->name('events');
    Route::get('/events/{event:slug}', 'event')->name('event');
});
