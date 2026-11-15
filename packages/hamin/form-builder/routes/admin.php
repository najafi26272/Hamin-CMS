<?php

use Hamin\FormBuilder\Http\Controllers\FileController;
use Hamin\FormBuilder\Livewire\Admin\FormEditor;
use Hamin\FormBuilder\Livewire\Admin\FormsIndex;
use Hamin\FormBuilder\Livewire\Admin\Submissions;
use Illuminate\Support\Facades\Route;

// prefix و middleware در FormBuilderServiceProvider از config خوانده می‌شوند
Route::livewire('/', FormsIndex::class)->name('forms.index');
Route::livewire('/create', FormEditor::class)->name('forms.create');
Route::livewire('/{form}/edit', FormEditor::class)->name('forms.edit');
Route::livewire('/{form}/submissions', Submissions::class)->name('submissions.index');
Route::get('/{form}/export', [FileController::class, 'export'])->name('submissions.export');
Route::get('/submissions/{submission}/files/{field}/{index}', [FileController::class, 'download'])
    ->whereNumber('index')->name('submissions.file');
