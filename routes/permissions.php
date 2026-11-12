<?php


use Illuminate\Support\Facades\Route;

// مجوزها در config/permissions.php تعریف می‌شوند و اینجا فقط قابل مشاهده‌اند
Route::livewire('/permissions', 'admin::permissions.index')->middleware('can:manage-roles')->name('permissions.index');
