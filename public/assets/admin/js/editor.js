// ویرایشگر متن مشترک پنل (CKEditor 5)
// فایل‌های ckeditor5.umd.js و translations/fa.umd.js باید قبل از صدا زدن این تابع لود شده باشند (با @assets در هر فرم)
//
// استفاده در بلوک @script کامپوننت:
//     createEditor(document.querySelector('#content-editor'), $wire, 'content');
window.createEditor = function (element, $wire, property) {
    const {
        ClassicEditor, Essentials, Paragraph, Heading, Bold, Italic, Underline, Link, List, Indent,
        BlockQuote, Table, TableToolbar, MediaEmbed, Alignment, Autoformat, PasteFromOffice,
    } = CKEDITOR;

    return ClassicEditor.create(element, {
        plugins: [
            Essentials, Paragraph, Heading, Bold, Italic, Underline, Link, List, Indent,
            BlockQuote, Table, TableToolbar, MediaEmbed, Alignment, Autoformat, PasteFromOffice,
        ],
        language: 'fa',
        translations: [window.CKEDITOR_TRANSLATIONS],
        toolbar: [
            'heading', '|', 'bold', 'italic', 'underline', 'link', '|',
            'alignment:right', 'alignment:center', 'alignment:left', 'alignment:justify', '|',
            'bulletedList', 'numberedList', 'outdent', 'indent', '|',
            'blockQuote', 'insertTable', 'mediaEmbed', '|', 'undo', 'redo',
        ],
        // راست‌چین، وسط‌چین، چپ‌چین و تراز دو طرفه
        alignment: {options: ['right', 'center', 'left', 'justify']},
        table: {contentToolbar: ['tableColumn', 'tableRow', 'mergeTableCells']},
    }).then((editor) => {
        editor.setData($wire.get(property) ?? '');

        // مقدار با درخواست بعدی (مثلا ذخیره فرم) به سرور ارسال می‌شود
        editor.model.document.on('change:data', () => $wire.$set(property, editor.getData(), false));

        return editor;
    });
};
