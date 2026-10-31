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
    <link rel="stylesheet" href="{{asset("assets/admin/css/persian-datepicker-0.4.5.min.css")}}">
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
    @livewireScripts
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
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
<script src="{{asset("assets/admin/js/persian-date-0.1.8.min.js")}}"></script>
<script src="{{asset("assets/admin/js/app.js")}}"></script>
<!-- endinject -->


<script src="{{asset("assets/admin/vendors/sweetalert2/sweetalert2.min.js")}}"></script>


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
