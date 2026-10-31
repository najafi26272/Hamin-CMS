<nav class="sidebar">
    <div class="sidebar-header">
        <a href="/" class="sidebar-brand">
            HAMIN
        </a>
        <div class="sidebar-toggler">
            <span></span>
            <span></span>
            <span></span>
        </div>
    </div>
    <div class="sidebar-body">
        <ul class="nav" id="sidebarNav">
            <li class="nav-item nav-category">کاربران</li>
            <li class="nav-item">
                <a href="{{route('users.index')}}" class="nav-link">
                    <i class="link-icon" data-feather="users"></i>
                    <span class="link-title">لیست کاربران</span>
                </a>
            </li>

            <li class="nav-item">
                <a href="{{route('users.create')}}" class="nav-link">
                    <i class="link-icon" data-feather="user-plus"></i>
                    <span class="link-title">ثبت کاربر جدید</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{route("roles.index")}}" class="nav-link">
                    <i class="link-icon" data-feather="shield"></i>
                    <span class="link-title">مدیریت نقش ها</span>
                </a>
            </li>

            <li class="nav-item nav-category">محتوا</li>

            <li class="nav-item">
                <a href="" class="nav-link">
                    <i class="link-icon" data-feather="list"></i>
                    <span class="link-title">لیست مطالب</span>
                </a>
            </li>

            <li class="nav-item">
                <a href="" class="nav-link">
                    <i class="link-icon" data-feather="edit"></i>
                    <span class="link-title">ثبت مطلب جدید</span>
                </a>
            </li>

            <li class="nav-item">
                <a href="" class="nav-link">
                    <i class="link-icon" data-feather="tag"></i>
                    <span class="link-title">مدیریت برچسب ها</span>
                </a>
            </li>

            <li class="nav-item">
                <a href="" class="nav-link">
                    <i class="link-icon" data-feather="package"></i>
                    <span class="link-title">مدیریت دسته بندی ها</span>
                </a>
            </li>


            <li class="nav-item nav-category">خروج از سامانه</li>

            <li class="nav-item">
                <a href="{{route('logout')}}" class="nav-link">
                    <i class="link-icon" data-feather="log-out"></i>
                    <span class="link-title">خروج</span>
                </a>
            </li>
        </ul>
    </div>
</nav>
