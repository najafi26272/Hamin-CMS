<?php

namespace Hamin\FormBuilder\Http\Controllers;

use Hamin\FormBuilder\Models\Form;
use Hamin\FormBuilder\Models\FormSubmission;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

// دانلود فایل‌های پاسخ‌ها و خروجی CSV (فقط از پنل مدیریت؛ middleware و gate در ServiceProvider)
class FileController extends Controller
{
    public function download(FormSubmission $submission, string $field, int $index)
    {
        $file = $submission->data[$field][$index] ?? null;
        abort_unless(is_array($file) && isset($file['path']), 404);

        $disk = Storage::disk(config('form-builder.storage.disk'));
        abort_unless($disk->exists($file['path']), 404);

        return $disk->download($file['path'], $file['name'] ?? basename($file['path']));
    }

    public function export(Form $form): StreamedResponse
    {
        $form->load('fields');

        $filename = 'form-'.$form->id.'-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($form) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM برای نمایش درست فارسی در اکسل

            $names = $form->fields->pluck('name')->all();
            $labels = $form->fields->pluck('label')->all();

            // فیلدهایی که حذف شده‌اند ولی در پاسخ‌های قدیمی هستند
            $extra = [];
            $form->submissions()->select('labels')->lazyById(200)->each(function ($s) use (&$extra, $names) {
                foreach ((array) $s->labels as $name => $label) {
                    if (! in_array($name, $names, true)) {
                        $extra[$name] = $label;
                    }
                }
            });

            fputcsv($out, ['شناسه', 'تاریخ ثبت', 'کاربر', ...$labels, ...array_values($extra)]);

            $form->submissions()->with('user')->lazyById(200)->each(function (FormSubmission $s) use ($out, $names, $extra) {
                $row = [
                    $s->id,
                    function_exists('jdate') ? jdate($s->created_at->copy()->setTimezone('Asia/Tehran'))->format('Y/m/d H:i') : $s->created_at->format('Y-m-d H:i'),
                    $s->user ? trim(($s->user->first_name ?? '').' '.($s->user->last_name ?? '')) ?: ($s->user->name ?? $s->user_id) : 'مهمان',
                ];
                foreach ([...$names, ...array_keys($extra)] as $name) {
                    $row[] = $s->displayValue($name);
                }
                fputcsv($out, $row);
            });

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
