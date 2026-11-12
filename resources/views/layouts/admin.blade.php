<!DOCTYPE html>
<html lang="ar" dir="rtl">

<!-- Mirrored from filenter.ir/Noble/template/demo1-ds-rtl/dashboard.html by HTTrack Website Copier/3.x [XR&CO'2014], Tue, 14 Oct 2025 19:45:14 GMT -->
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">

    <title>پنل مدیریت</title>

    <!-- color-modes:js -->
    <script src="{{asset("assets/admin/js/color-modes.js")}}"></script>
    <!-- endinject -->

    <!-- core:css -->
    <link rel="stylesheet" href="{{asset("assets/admin/vendors/core/core.css")}}">
    <!-- endinject -->

    <!-- Plugin css for this page -->
    <link rel="stylesheet" href="{{asset("assets/admin/vendors/flatpickr/flatpickr.min.css")}}">
    <link rel="stylesheet" href="{{asset("assets/admin/vendors/persian-datepicker/persian-datepicker.min.css")}}">
    <link rel="stylesheet" href="{{asset("assets/admin/vendors/select2/select2.min.css")}}">
    <link rel="stylesheet" href="{{asset("assets/admin/vendors/ckeditor5/ckeditor5.css")}}">
    <!-- End plugin css for this page -->

    <!-- inject:css -->
    <link rel="stylesheet" href="{{asset("assets/admin/fonts/feather-font/css/iconfont.css")}}">
    <!-- endinject -->

    <!-- Layout styles -->
    <link rel="stylesheet" href="{{asset("assets/admin/css/demo1/style-rtl.css")}}">
    <!-- End layouts styles -->

    <link rel="icon" type="image/x-icon" href="{{asset("assets/general/img/color-logo.png")}}">
    <link rel="stylesheet" href="{{asset("assets/admin/vendors/sweetalert2/sweetalert2.min.css")}}">

    @stack('css')

    @livewireStyles
    {{-- Alpine همراه لایو وایر لود می‌شود و نباید جداگانه اضافه شود --}}
    @livewireScripts
    <style>
        .upload-btn {
            width: 130px;
            background: #2196F3;
            border-radius: 5px;
            color: white;
            font-size: 14px;
            height: 45px;
            text-align: center;
            align-items: center;
        }

        .upload-txt {
            font-size: 14px;
            height: 45px;
            width: calc(100% - 130px);


        }

        /* ویرایشگر متن CKEditor: هماهنگ با تم روشن و تیره */
        .ck.ck-editor {
            --ck-color-base-background: var(--bs-body-bg);
            --ck-color-base-border: var(--bs-border-color);
            --ck-color-toolbar-background: var(--bs-tertiary-bg);
            --ck-color-toolbar-border: var(--bs-border-color);
            --ck-color-text: var(--bs-body-color);
            --ck-color-button-default-hover-background: var(--bs-secondary-bg);
            --ck-color-button-on-background: var(--bs-secondary-bg);
            --ck-color-button-on-hover-background: var(--bs-secondary-bg);
            --ck-color-dropdown-panel-background: var(--bs-body-bg);
            --ck-color-panel-background: var(--bs-body-bg);
            --ck-color-input-background: var(--bs-body-bg);
            --ck-color-input-text: var(--bs-body-color);
            --ck-color-list-background: var(--bs-body-bg);
            --ck-color-list-button-hover-background: var(--bs-secondary-bg);
            --ck-color-labeled-field-label-background: var(--bs-body-bg);
        }

        .ck-content {
            color: var(--bs-body-color);
        }
    </style>

</head>
<body class="sidebar-dark">
<div class="main-wrapper">

    <!-- partial:partials/_sidebar.html -->
    @include('layouts.partials.sidebar')
    <!-- partial -->

    <div class="page-wrapper">

        <!-- partial:partials/_navbar.html -->
        @include('layouts.partials.header')
        <!-- partial -->

        <div class="page-content">

            {{ $slot }}

        </div>

        <!-- partial:partials/_footer.html -->
        <footer
            class="footer d-flex flex-row align-items-center justify-content-between px-4 py-3 border-top small">
            <p class="text-secondary mb-1 mb-md-0">تمامی حقوق برای این سایت محفوظ است.</a>
            </p>
        </footer>
        <!-- partial -->

    </div>
</div>

<!-- core:js -->
<script src="{{asset("assets/admin/vendors/core/core.js")}}"></script>
<script src="{{asset("assets/admin/vendors/jquery/jquery.min.js")}}"></script>
<!-- endinject -->


<!-- inject:js -->
<script src="{{asset("assets/admin/vendors/feather-icons/feather.min.js")}}"></script>
<script src="{{asset("assets/admin/vendors/persian-datepicker/persian-date.min.js")}}"></script>
<script src="{{asset("assets/admin/vendors/persian-datepicker/persian-datepicker.min.js")}}"></script>
<script src="{{asset("assets/admin/vendors/select2/select2.min.js")}}"></script>
<script src="{{asset("assets/admin/js/app.js")}}"></script>
<script src="{{asset("assets/admin/js/editor.js")}}"></script>
<!-- endinject -->


<script src="{{asset("assets/admin/vendors/sweetalert2/sweetalert2.min.js")}}"></script>

<script>
    // نمایش پیغام کوتاه (موفق / خطا)
    function notify(type, message) {
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: type,
            title: message,
            showConfirmButton: false,
            timer: 3500,
            timerProgressBar: true,
            background: 'var(--bs-body-bg)',
            color: 'var(--bs-body-color)',
        });
    }

    // باکس تایید حذف
    function confirmDelete(onConfirm, text = 'این عملیات قابل بازگشت نیست.') {
        Swal.fire({
            title: 'آیا از حذف مطمئن هستید؟',
            text: text,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'بله، حذف شود',
            cancelButtonText: 'انصراف',
            confirmButtonColor: '#ff3366',
            cancelButtonColor: '#6c757d',
            reverseButtons: true,
            background: 'var(--bs-body-bg)',
            color: 'var(--bs-body-color)',
        }).then((result) => {
            if (result.isConfirmed) {
                onConfirm();
            }
        });
    }

    // کادر دریافت توضیحات (مثلا دلیل رد صلاحیت یا رد مطلب)
    // required: اگر true باشد، بدون نوشتن دلیل ثبت نمی‌شود
    function askReason(title, onSubmit, required = false, label = 'توضیحات (به کاربر هنگام ورود نمایش داده می‌شود)') {
        Swal.fire({
            title: title,
            input: 'textarea',
            inputLabel: label,
            inputPlaceholder: 'دلیل را بنویسید...',
            inputAttributes: {maxlength: 1000},
            inputValidator: (value) => required && ! value.trim() ? 'لطفا دلیل را بنویسید.' : undefined,
            showCancelButton: true,
            confirmButtonText: 'ثبت',
            cancelButtonText: 'انصراف',
            confirmButtonColor: '#ff3366',
            cancelButtonColor: '#6c757d',
            reverseButtons: true,
            background: 'var(--bs-body-bg)',
            color: 'var(--bs-body-color)',
        }).then((result) => {
            if (result.isConfirmed) {
                onSubmit(result.value ?? '');
            }
        });
    }

    document.addEventListener('livewire:init', () => {
        // پیغام‌هایی که از کامپوننت‌ها با dispatch('notify') ارسال می‌شوند
        Livewire.on('notify', ({type, message}) => notify(type, message));

        // آیکن‌های feather بعد از هر بروزرسانی لایو وایر دوباره ساخته شوند
        Livewire.hook('morphed', () => feather.replace());
    });

    @if (session('success'))
        document.addEventListener('DOMContentLoaded', () => notify('success', @js(session('success'))));
    @endif
    @if (session('error'))
        document.addEventListener('DOMContentLoaded', () => notify('error', @js(session('error'))));
    @endif
</script>


<!-- Custom js for this page -->
<script src="{{asset("assets/admin/js/sweet-alert.js")}}"></script>
<!-- End custom js for this page -->


<!-- Custom js for this page -->
<script src="{{asset("assets/admin/js/dashboard.js")}}"></script>
<!-- End custom js for this page -->
@stack('scripts')

</body>

<!-- Mirrored from filenter.ir/Noble/template/demo1-ds-rtl/dashboard.html by HTTrack Website Copier/3.x [XR&CO'2014], Tue, 14 Oct 2025 19:45:38 GMT -->
</html>
