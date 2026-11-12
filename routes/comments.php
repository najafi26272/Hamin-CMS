<?php


use Illuminate\Support\Facades\Route;

Route::livewire('/comments', 'admin::comments.index')->middleware('can:manage-comments')->name('comments.index');
