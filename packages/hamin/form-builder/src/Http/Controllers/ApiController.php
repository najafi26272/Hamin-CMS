<?php

namespace Hamin\FormBuilder\Http\Controllers;

use Hamin\FormBuilder\Models\Form;
use Hamin\FormBuilder\Models\FormField;
use Hamin\FormBuilder\Support\FieldTypes;
use Hamin\FormBuilder\Support\SubmissionService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

/**
 * API JSON برای رابط‌های غیر Livewire (React، Vue، اپ موبایل)
 * GET  /{slug}         ساختار فرم + مقادیر پروفایل کاربر
 * POST /{slug}/submit  ثبت با multipart: values[name]=... و files[name] یا files[name][]
 */
class ApiController extends Controller
{
    public function show(string $slug, SubmissionService $service)
    {
        $form = Form::where('slug', $slug)->where('is_active', true)->with('fields')->firstOrFail();
        $user = auth()->user();

        return response()->json([
            'form' => [
                'title' => $form->title,
                'slug' => $form->slug,
                'description' => $form->description,
                'submit_label' => $form->submit_text,
                'requires_login' => $form->needsLogin(),
                'can_submit' => $service->denialReason($form, $user) === null,
                'denial_reason' => $service->denialReason($form, $user),
                'missing_profile_fields' => $service->missingProfileFields($form, $user),
            ],
            'fields' => $form->fields->map(fn (FormField $f) => [
                'name' => $f->name,
                'kind' => $f->kind,
                'type' => $f->isProfile() ? $f->inputType() : $f->type,
                'profile_key' => $f->isProfile() ? $f->type : null,
                'label' => $f->label,
                'placeholder' => $f->placeholder,
                'help' => $f->help,
                'required' => $f->is_required,
                'readonly' => $f->isLocked(),
                'multiple' => $f->isMultiple(),
                'width' => $f->width,
                'options' => $f->optionList(),
                'settings' => $f->type === 'file'
                    ? ['extensions' => FieldTypes::allowedExtensions((array) $f->setting('extensions')), 'max_size_kb' => (int) $f->setting('max_size_kb'), 'max_files' => (int) $f->setting('max_files')]
                    : (array) $f->settings,
            ])->values(),
            'values' => $service->initialValues($form, $user),
        ]);
    }

    public function submit(string $slug, Request $request, SubmissionService $service)
    {
        $form = Form::where('slug', $slug)->where('is_active', true)->with('fields')->firstOrFail();

        $submission = $service->submit(
            $form,
            (array) $request->input('values', []),
            (array) $request->file('files', []),
            $request->user(),
            $request->ip(),
            $request->userAgent(),
        );

        return response()->json(['id' => $submission->id, 'message' => $form->success_text], 201);
    }
}
