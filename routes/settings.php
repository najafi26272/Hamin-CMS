<?php

use Illuminate\Support\Facades\Route;

// تنظیمات سایت: عنوان، توضیحات، لوگو، فاوآیکن و حالت توسعه
Route::livewire('/settings', 'admin::settings.index')->middleware('can:manage-settings')->name('settings.index');
