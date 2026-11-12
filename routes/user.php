<?php


use Illuminate\Support\Facades\Route;

Route::livewire('/users', 'admin::users.index')->middleware('can:view-users')->name('users.index');
Route::livewire('/users/create', 'admin::users.form')->middleware('can:manage-users')->name('users.create');
Route::livewire('/users/{user}/edit', 'admin::users.form')->middleware('can:edit-users')->name('users.edit');
