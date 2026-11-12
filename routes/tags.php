<?php


use Illuminate\Support\Facades\Route;

Route::middleware('can:manage-tags')->group(function () {
    Route::livewire('/tags', 'admin::tags.index')->name('tags.index');
    Route::livewire('/tags/create', 'admin::tags.form')->name('tags.create');
    Route::livewire('/tags/{tag}/edit', 'admin::tags.form')->name('tags.edit');
});
