<nav class="navbar">
    <div class="navbar-content">

        <div class="logo-mini-wrapper">
            <h5>مدیریت محتوا</h5>
        </div>

        <ul class="navbar-nav">
            <li class="theme-switcher-wrapper nav-item">
                <input type="checkbox" value="" id="theme-switcher">
                <label for="theme-switcher">
                    <div class="box">
                        <div class="ball"></div>
                        <div class="icons">
                            <i class="me-2 icon-md" data-feather="sun"></i>
                            <i class="me-2 icon-md" data-feather="moon"></i>
                        </div>
                    </div>
                </label>
            </li>

            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" id="profileDropdown" role="button" data-bs-toggle="dropdown"
                   aria-haspopup="true" aria-expanded="false">
                    <img class="w-30px h-30px ms-1 rounded-circle" src="{{asset("assets/admin/images/faces/pro.png")}}" alt="profile">
                   {{Auth::user()?->name  }}   {{Auth::user()?->family }}
                </a>
                <div class="dropdown-menu p-0" aria-labelledby="profileDropdown">
                    <div class="d-flex flex-column align-items-center border-bottom px-5 py-3">
                        <div class="mb-3">
                            <img width="50" class="wd-40 ht-40 rounded-circle" src="{{asset("assets/admin/images/faces/pro.png")}}" alt="">
                        </div>
                        <div class="text-center">
                            <p class="tx-16 fw-bolder">{{Auth::user()?->name}} {{Auth::user()?->family}}</p>
                            <p class="tx-12 text-muted">{{Auth::user()?->username}}</p>
                        </div>
                    </div>
                    <ul class="list-unstyled p-1">
                        <li class="dropdown-item py-2">
                            <a href="" class="text-body ms-0">
                                <i class="me-2 icon-md" data-feather="edit"></i>
                                <span>ویرایش پروفایل</span>
                            </a>
                        </li>
                        <li class="dropdown-item py-2">
                            <a href="" class="text-body ms-0">
                                <i class="me-2 icon-md" data-feather="key"></i>
                                <span>تغییر رمز عبور</span>
                            </a>
                        </li>
                        <li class="dropdown-item py-2">
                            <a href="{{route('logout')}}" class="text-body ms-0">
                                <i class="me-2 icon-md" data-feather="log-out"></i>
                                <span>خروج</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </li>
        </ul>

        <a href="#" class="sidebar-toggler">
            <i data-feather="menu"></i>
        </a>

    </div>
</nav>
