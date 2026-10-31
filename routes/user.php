<?php


use Illuminate\Support\Facades\Route;

Route::livewire('/users', 'admin::users.index')->name('users.index');
Route::livewire('/users/create', 'admin::users.form')->name('users.create');
Route::livewire('/users/{user}/edit', 'admin::users.form')->name('users.edit');
