<?php


use Illuminate\Support\Facades\Route;

Route::middleware('can:manage-sliders')->group(function () {
    Route::livewire('/sliders', 'admin::sliders.index')->name('sliders.index');
    Route::livewire('/sliders/create', 'admin::sliders.form')->name('sliders.create');
    Route::livewire('/sliders/{slider}/edit', 'admin::sliders.form')->name('sliders.edit');
});
