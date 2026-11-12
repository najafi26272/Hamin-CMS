<?php


use Illuminate\Support\Facades\Route;

Route::middleware('can:manage-categories')->group(function () {
    Route::livewire('/categories', 'admin::categories.index')->name('categories.index');
    Route::livewire('/categories/create', 'admin::categories.form')->name('categories.create');
    Route::livewire('/categories/{category}/edit', 'admin::categories.form')->name('categories.edit');
});
