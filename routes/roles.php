<?php


use Illuminate\Support\Facades\Route;

Route::livewire('/roles', 'admin::roles.index')->name('roles.index');
Route::livewire('/roles/create', 'admin::roles.form')->name('roles.create');
Route::livewire('/roles/{role}/edit', 'admin::roles.form')->name('roles.edit');
