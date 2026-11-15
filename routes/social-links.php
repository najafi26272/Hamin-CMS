<?php

use Illuminate\Support\Facades\Route;

Route::livewire('/social-links', 'admin::social-links.index')->middleware('can:manage-social-links')->name('social-links.index');
