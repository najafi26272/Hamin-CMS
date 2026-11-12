<?php


use Illuminate\Support\Facades\Route;

Route::middleware('can:manage-events')->group(function () {
    Route::livewire('/events', 'admin::events.index')->name('events.index');
    Route::livewire('/events/create', 'admin::events.form')->name('events.create');
    Route::livewire('/events/{event}/edit', 'admin::events.form')->name('events.edit');
});
