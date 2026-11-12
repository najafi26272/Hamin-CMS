<?php


use Illuminate\Support\Facades\Route;

Route::middleware('can:manage-media')->group(function () {
    Route::livewire('/galleries', 'admin::galleries.index')->name('galleries.index');
    Route::livewire('/galleries/create', 'admin::galleries.form')->name('galleries.create');
    Route::livewire('/galleries/{gallery}/edit', 'admin::galleries.form')->name('galleries.edit');
});
