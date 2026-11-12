<!DOCTYPE html>
<html lang="ar" dir="rtl">

<!-- Mirrored from filenter.ir/Noble/template/demo1-ds-rtl/pages/auth/login.html by HTTrack Website Copier/3.x [XR&CO'2014], Tue, 14 Oct 2025 19:46:25 GMT -->
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">

    <title>احراز هویت</title>

    <!-- color-modes:js -->
    <script src="{{asset("assets/admin/js/color-modes.js")}}"></script>
    <!-- endinject -->

    <!-- core:css -->
    <link rel="stylesheet" href="{{asset("assets/admin/vendors/core/core.css")}}">
    <!-- endinject -->

    <!-- Plugin css for this page -->
    <link rel="stylesheet" href="{{asset("assets/admin/vendors/persian-datepicker/persian-datepicker.min.css")}}">
    <link rel="stylesheet" href="{{asset("assets/admin/vendors/select2/select2.min.css")}}">
    <!-- End plugin css for this page -->

    <!-- inject:css -->
    <link rel="stylesheet" href="{{asset("assets/admin/fonts/feather-font/css/iconfont.css")}}">
    <!-- endinject -->

    <!-- Layout styles -->
    <link rel="stylesheet" href="{{asset("assets/admin/css/demo1/style-rtl.css")}}">
    <!-- End layouts styles -->

    <link rel="stylesheet" href="{{asset("assets/admin/css/header-footer.css")}}">

    <!-- fontawesome -->
    <link rel="stylesheet"  type='text/css' href="{{asset("assets/general/font-awesome/css/font-awesome.css")}}">
    <!-- End fontawesome -->

    <link rel="shortcut icon" type="image/x-icon" href="{{asset("assets/site/images/setad-logo.png")}}">

    {{-- Alpine همراه لایو وایر لود می‌شود و نباید جداگانه اضافه شود --}}
    @livewireStyles
    @livewireScripts


</head>
<body class="sidebar-dark">

{{--@include('site.layouts.partials.header')--}}
<div class="main-wrapper">
    <div class="page-wrapper full-page">
        <div class="page-content d-flex align-items-center justify-content-center">

            <div class="row w-100 mx-0 auth-page">


                {{-- صفحه‌ها می‌توانند عنوان ($heading) و کارت عریض ($wide) را از طریق Layout تعیین کنند --}}
                <div @class(['mx-auto', 'col-md-8 col-xl-6' => ! ($wide ?? false), 'col-lg-11 col-xl-9 my-4' => $wide ?? false])>
                    <div class="card">
                        <div class="row">
                            @unless ($wide ?? false)
                                <div class="col-md-4 pe-md-0">
                                    <div class="auth-side-wrapper">

                                    </div>
                                </div>
                            @endunless
                            <div @class(['ps-md-0' => ! ($wide ?? false), 'col-md-8' => ! ($wide ?? false), 'col-12' => $wide ?? false])>
                                <div class="auth-form-wrapper px-4 py-5">
                                    <h5 class="text-muted fw-normal mb-4">{{ $heading ?? 'خوش آمدید! به حساب کاربری خود وارد شوید' }}</h5>
                                    {{ $slot }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>


            </div>

        </div>
    </div>
</div>
{{--@include('site.layouts.partials.footer')--}}
<!-- core:js -->
<script src="{{asset("assets/admin/vendors/core/core.js")}}"></script>
<script src="{{asset("assets/admin/vendors/jquery/jquery.min.js")}}"></script>
<!-- endinject -->

<!-- Plugin js for this page -->
<!-- End plugin js for this page -->

<!-- inject:js -->
<script src="{{asset("assets/admin/vendors/feather-icons/feather.min.js")}}"></script>
<script src="{{asset("assets/admin/vendors/persian-datepicker/persian-date.min.js")}}"></script>
<script src="{{asset("assets/admin/vendors/persian-datepicker/persian-datepicker.min.js")}}"></script>
<script src="{{asset("assets/admin/vendors/select2/select2.min.js")}}"></script>
<script src="{{asset("assets/admin/js/app.js")}}"></script>
<script>
    // آیکن‌های feather بعد از هر بروزرسانی لایو وایر دوباره ساخته شوند
    document.addEventListener('livewire:init', () => Livewire.hook('morphed', () => feather.replace()));
</script>
<!-- endinject -->

<!-- Custom js for this page -->
<!-- End custom js for this page -->

</body>

<!-- Mirrored from filenter.ir/Noble/template/demo1-ds-rtl/pages/auth/login.html by HTTrack Website Copier/3.x [XR&CO'2014], Tue, 14 Oct 2025 19:46:25 GMT -->
</html>
