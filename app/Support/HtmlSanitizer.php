<?php

namespace App\Support;

/**
 * پاکسازی HTML خروجی ویرایشگر متن (CKEditor) پیش از ذخیره و نمایش
 * فقط تگ‌ها و ویژگی‌هایی که ویرایشگر تولید می‌کند باقی می‌مانند؛
 * اسکریپت، رویدادها (onclick و ...) و لینک‌های javascript: حذف می‌شوند.
 */
class HtmlSanitizer
{
    // تگ مجاز => ویژگی‌های مجاز آن (style همه‌جا فقط برای text-align مجاز است)
    private const TAGS = [
        'p' => [], 'br' => [], 'h2' => [], 'h3' => [], 'h4' => [],
        'strong' => [], 'b' => [], 'em' => [], 'i' => [], 'u' => [], 's' => [],
        'a' => ['href', 'target', 'rel'],
        'ul' => [], 'ol' => [], 'li' => [], 'blockquote' => [],
        'figure' => ['class'], 'table' => [], 'thead' => [], 'tbody' => [], 'tr' => [],
        'th' => ['colspan', 'rowspan'], 'td' => ['colspan', 'rowspan'],
        'oembed' => ['url'],
    ];

    // این تگ‌ها همراه با محتوایشان حذف می‌شوند
    private const DROP = ['script', 'style', 'iframe', 'object', 'embed', 'form', 'input', 'button', 'textarea', 'select', 'svg', 'math'];

    public static function clean(?string $html): ?string
    {
        if ($html === null || trim($html) === '') {
            return $html;
        }

        $doc = new \DOMDocument();
        libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="UTF-8"><div>'.$html.'</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        $root = $doc->getElementsByTagName('div')->item(0);
        self::walk($root);

        $output = '';
        foreach ($root->childNodes as $child) {
            $output .= $doc->saveHTML($child);
        }

        return $output;
    }

    private static function walk(\DOMNode $node): void
    {
        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child instanceof \DOMComment || $child instanceof \DOMProcessingInstruction) {
                $node->removeChild($child);
                continue;
            }

            if (! $child instanceof \DOMElement) {
                continue;
            }

            $tag = strtolower($child->tagName);

            if (in_array($tag, self::DROP)) {
                $node->removeChild($child);
                continue;
            }

            self::walk($child);

            // تگ ناشناخته حذف می‌شود ولی متن داخل آن باقی می‌ماند
            if (! array_key_exists($tag, self::TAGS)) {
                while ($child->firstChild) {
                    $node->insertBefore($child->firstChild, $child);
                }
                $node->removeChild($child);
                continue;
            }

            foreach (iterator_to_array($child->attributes) as $attribute) {
                if (! self::allowedAttribute($tag, $attribute->name, $attribute->value)) {
                    $child->removeAttribute($attribute->name);
                }
            }

            if ($tag === 'a' && $child->getAttribute('target') === '_blank') {
                $child->setAttribute('rel', 'noopener noreferrer');
            }
        }
    }

    private static function allowedAttribute(string $tag, string $name, string $value): bool
    {
        $name = strtolower($name);

        if ($name === 'style') {
            return (bool) preg_match('/^\s*text-align\s*:\s*(left|right|center|justify)\s*;?\s*$/i', $value);
        }

        if (! in_array($name, self::TAGS[$tag])) {
            return false;
        }

        return match ($name) {
            'href' => (bool) preg_match('#^(https?://|mailto:|/|\#)#i', trim($value)),
            'url' => (bool) preg_match('#^https?://#i', trim($value)),
            'target' => $value === '_blank',
            'colspan', 'rowspan' => ctype_digit($value),
            'class' => in_array($value, ['table', 'media']),
            default => true,
        };
    }
}
