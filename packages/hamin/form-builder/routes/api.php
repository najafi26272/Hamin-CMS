<?php

use Hamin\FormBuilder\Http\Controllers\ApiController;
use Illuminate\Support\Facades\Route;

Route::get('/{slug}', [ApiController::class, 'show'])->name('show');
Route::post('/{slug}/submit', [ApiController::class, 'submit'])->middleware('throttle:10,1')->name('submit');
