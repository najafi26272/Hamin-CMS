<?php


use Illuminate\Support\Facades\Route;

Route::middleware('can:manage-media')->group(function () {
    Route::livewire('/videos', 'admin::videos.index')->name('videos.index');
    Route::livewire('/videos/create', 'admin::videos.form')->name('videos.create');
    Route::livewire('/videos/{video}/edit', 'admin::videos.form')->name('videos.edit');
});
