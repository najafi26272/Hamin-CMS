<?php

namespace Hamin\FormBuilder\Http\Controllers;

use Hamin\FormBuilder\Models\Form;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;

// صفحه عمومی فرم داخل لایوت سایت پروژه
class PublicFormController extends Controller
{
    public function show(string $slug)
    {
        $form = Form::where('slug', $slug)->firstOrFail();

        // فرم غیرفعال فقط برای مدیران (پیش‌نمایش) نمایش داده می‌شود
        $gate = config('form-builder.admin.gate');
        $preview = ! $form->is_active && auth()->check() && (! $gate || Gate::allows($gate));
        abort_unless($form->is_active || $preview, 404);

        $view = config('form-builder.public.layout_type') === 'component' ? 'show-component' : 'show-extends';

        return view('form-builder::public.'.$view, [
            'form' => $form,
            'preview' => $preview,
            'layout' => config('form-builder.public.layout'),
            'section' => config('form-builder.public.section', 'content'),
        ]);
    }
}
