<?php

use Hamin\FormBuilder\Http\Controllers\PublicFormController;
use Illuminate\Support\Facades\Route;

Route::get('/{slug}', [PublicFormController::class, 'show'])->name('show');
