<?php


use Illuminate\Support\Facades\Route;

// کارکنان همه مطالب را مدیریت می‌کنند؛ هنرمندان (مجوز ارسال مطلب) فقط مطالب خودشان را
Route::livewire('/posts', 'admin::posts.index')->middleware('can:list-posts')->name('posts.index');
Route::livewire('/posts/create', 'admin::posts.form')->middleware('can:create-post')->name('posts.create');
Route::livewire('/posts/{post}/edit', 'admin::posts.form')->middleware('can:update-post,post')->name('posts.edit');
