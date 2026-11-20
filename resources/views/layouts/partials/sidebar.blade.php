<nav class="sidebar">
    @php
        $logoUrl = \App\Models\Setting::logoUrl();
    @endphp
    <div class="sidebar-header">
        <a href="/" class="sidebar-brand">
            <img width="120" src="{{ $logoUrl }}" alt="لوگو">
        </a>
        <div class="sidebar-toggler">
            <span></span>
            <span></span>
            <span></span>
        </div>
    </div>
    <div class="sidebar-body">
        <ul class="nav" id="sidebarNav">
            <li @class(['nav-item', 'active' => request()->routeIs('dashboard')])>
                <a href="{{route('dashboard')}}" class="nav-link">
                    <i class="link-icon" data-feather="home"></i>
                    <span class="link-title">داشبورد</span>
                </a>
            </li>

            {{-- درخواست هنرمندی: برای کاربرانی که هنوز هنرمند تأییدشده نیستند (مدیران و نویسندگان نیازی ندارند) --}}
            @if (auth()->user()->artist_status !== 'approved')
                @cannot('edit-content')
                    @cannot('manage-content')
                        <li @class(['nav-item', 'active' => request()->routeIs('artist.apply')])>
                            <a href="{{ route('artist.apply') }}" class="nav-link">
                                <i class="link-icon" data-feather="feather"></i>
                                <span class="link-title">ثبت‌نام به عنوان هنرمند</span>
                                @if (auth()->user()->artist_status === 'pending')
                                    <span class="badge bg-warning text-dark ms-auto">در انتظار</span>
                                @elseif (auth()->user()->artist_status === 'rejected')
                                    <span class="badge bg-danger ms-auto">رد شده</span>
                                @endif
                            </a>
                        </li>
                    @endcannot
                @endcannot
            @endif

            @canany(['view-users', 'manage-roles'])
                <li class="nav-item nav-category">کاربران</li>
            @endcanany

            @can('view-users')
                <li @class(['nav-item', 'active' => request()->routeIs('users.index', 'users.edit')])>
                    <a href="{{route('users.index')}}" class="nav-link">
                        <i class="link-icon" data-feather="users"></i>
                        <span class="link-title">لیست کاربران</span>
                        @php($pendingUsers = \App\Models\User::where('artist_status', 'pending')->count())
                        @if ($pendingUsers)
                            <span class="badge bg-danger ms-auto"
                                  title="درخواست‌های هنرمندی در انتظار">{{ $pendingUsers }}</span>
                        @endif
                    </a>
                </li>
            @endcan

            @can('manage-users')
                <li @class(['nav-item', 'active' => request()->routeIs('users.create')])>
                    <a href="{{route('users.create')}}" class="nav-link">
                        <i class="link-icon" data-feather="user-plus"></i>
                        <span class="link-title">ثبت کاربر جدید</span>
                    </a>
                </li>
            @endcan

            @can('manage-roles')
                <li @class(['nav-item', 'active' => request()->routeIs('roles.*')])>
                    <a href="{{route("roles.index")}}" class="nav-link">
                        <i class="link-icon" data-feather="shield"></i>
                        <span class="link-title">مدیریت نقش ها</span>
                    </a>
                </li>

                <li @class(['nav-item', 'active' => request()->routeIs('permissions.*')])>
                    <a href="{{route("permissions.index")}}" class="nav-link">
                        <i class="link-icon" data-feather="key"></i>
                        <span class="link-title">لیست مجوزها</span>
                    </a>
                </li>
            @endcan

            @canany(['list-posts', 'manage-tags', 'manage-categories', 'manage-comments', 'manage-events'])
                <li class="nav-item nav-category">محتوا</li>
            @endcanany

            @can('manage-events')
                <li @class(['nav-item', 'active' => request()->routeIs('events.*')])>
                    <a href="{{route('events.index')}}" class="nav-link">
                        <i class="link-icon" data-feather="calendar"></i>
                        <span class="link-title">رویدادها</span>
                    </a>
                </li>
            @endcan

            @can('list-posts')
                <li @class(['nav-item', 'active' => request()->routeIs('posts.index', 'posts.edit')])>
                    <a href="{{route('posts.index')}}" class="nav-link">
                        <i class="link-icon" data-feather="list"></i>
                        <span
                            class="link-title">{{ auth()->user()->canAny(['view-content', 'review-content']) ? 'لیست مطالب' : 'مطالب من' }}</span>
                        @can('review-content')
                            @php($pendingPosts = \App\Models\Post::where('status', 'pending')->count())
                            @if ($pendingPosts)
                                <span class="badge bg-danger ms-auto" title="در انتظار بررسی">{{ $pendingPosts }}</span>
                            @endif
                        @endcan
                    </a>
                </li>
            @endcan

            @can('create-post')
                <li @class(['nav-item', 'active' => request()->routeIs('posts.create')])>
                    <a href="{{route('posts.create')}}" class="nav-link">
                        <i class="link-icon" data-feather="edit"></i>
                        <span class="link-title">ثبت مطلب جدید</span>
                    </a>
                </li>
            @endcan

            @can('manage-tags')
                <li @class(['nav-item', 'active' => request()->routeIs('tags.*')])>
                    <a href="{{route('tags.index')}}" class="nav-link">
                        <i class="link-icon" data-feather="tag"></i>
                        <span class="link-title">مدیریت برچسب ها</span>
                    </a>
                </li>
            @endcan

            @can('manage-categories')
                <li @class(['nav-item', 'active' => request()->routeIs('categories.*')])>
                    <a href="{{route('categories.index')}}" class="nav-link">
                        <i class="link-icon" data-feather="package"></i>
                        <span class="link-title">مدیریت دسته بندی ها</span>
                    </a>
                </li>
            @endcan

            @can('manage-comments')
                @php($pendingComments = \App\Models\Comment::where('status', 'pending')->count())
                <li @class(['nav-item', 'active' => request()->routeIs('comments.*')])>
                    <a href="{{route('comments.index')}}" class="nav-link">
                        <i class="link-icon" data-feather="message-square"></i>
                        <span class="link-title">دیدگاه ها</span>
                        @if ($pendingComments)
                            <span class="badge bg-danger ms-auto" title="در انتظار تأیید">{{ $pendingComments }}</span>
                        @endif
                    </a>
                </li>
            @endcan


            @can('manage-media')
                <li class="nav-item nav-category">چند رسانه ای</li>
                <li @class(['nav-item', 'active' => request()->routeIs('galleries.*')])>
                    <a href="{{route('galleries.index')}}" class="nav-link">
                        <i class="link-icon" data-feather="image"></i>
                        <span class="link-title">گالری تصاویر</span>
                    </a>
                </li>

                <li @class(['nav-item', 'active' => request()->routeIs('videos.*')])>
                    <a href="{{route('videos.index')}}" class="nav-link">
                        <i class="link-icon" data-feather="video"></i>
                        <span class="link-title">فیلم ها</span>
                    </a>
                </li>
            @endcan

{{--            @can('manage-forms')--}}
{{--                <li class="nav-item nav-category">فرم‌ها</li>--}}
{{--                <li @class(['nav-item', 'active' => request()->routeIs('form-builder.admin.*')])>--}}
{{--                    <a href="{{ route('form-builder.admin.forms.index') }}" class="nav-link">--}}
{{--                        <i class="link-icon" data-feather="clipboard"></i>--}}
{{--                        <span class="link-title">فرم‌ساز</span>--}}
{{--                    </a>--}}
{{--                </li>--}}
{{--            @endcan--}}

            {{-- تنظیمات سایت: تنظیمات عمومی، اسلایدرها و شبکه‌های اجتماعی --}}
            @canany(['manage-settings', 'manage-sliders', 'manage-social-links'])
                @php($settingsOpen = request()->routeIs('settings.*', 'sliders.*', 'social-links.*'))

                <li class="nav-item nav-category">تنظیمات

                    @if (\App\Models\Setting::maintenance())
                        <span class="badge bg-warning text-dark ms-auto me-1" title="حالت توسعه فعال است">حالت توسعه</span>
                    @endif
                </li>
                @can('manage-settings')
                    <li class="nav-item">
                        <a href="{{ route('settings.index') }}" @class(['nav-link', 'active' => request()->routeIs('settings.*')])>
                            <i class="link-icon" data-feather="settings"></i>
                            <span class="link-title">تنظیمات عمومی</span>
                        </a>
                    </li>
                @endcan
                @can('manage-sliders')
                    <li class="nav-item">
                        <a href="{{ route('sliders.index') }}" @class(['nav-link', 'active' => request()->routeIs('sliders.*')])>
                            <i class="link-icon" data-feather="align-justify"></i>
                            <span class="link-title">اسلایدرها</span>
                        </a>
                    </li>
                @endcan
                @can('manage-social-links')
                    <li class="nav-item">
                        <a href="{{ route('social-links.index') }}" @class(['nav-link', 'active' => request()->routeIs('social-links.*')])>
                            <i class="link-icon" data-feather="share-2"></i>
                            <span class="link-title">شبکه‌های اجتماعی</span>
                        </a>
                    </li>
                @endcan
            @endcanany

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
