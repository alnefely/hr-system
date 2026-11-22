<!doctype html>
{{--
    قالب بوابة الموظف — نفس هيكل لوحة الإدارة (Vuexy): قائمة جانبية +
    شريط علوي منفصل فيه تبديل الوضع الليلي وقائمة المستخدم. الموظف اللي
    بيستخدم اللوحتين بيلاقي نفس الشكل ونفس أماكن الحاجات.
--}}
<html lang="{{ vuexy_setting('lang') }}" dir="{{ vuexy_setting('dir') }}"
      class="{{ vuexy_setting('class') }}"
      data-skin="{{ vuexy_setting('data_skin') }}"
      data-bs-theme="{{ vuexy_setting('data_bs_theme') }}"
      data-assets-path="{{ vuexy_setting('data_assets_path') }}"
      data-template="{{ vuexy_setting('data_template') }}">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="robots" content="noindex, nofollow" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', __('portal.title')) — {{ setting('site_name') }}</title>
    <link rel="icon" type="image/x-icon" href="{{ setting('site_favicon') }}" />

    <link rel="stylesheet" href="/admin/vendor/fonts/iconify-icons.css" />
    <link rel="stylesheet" href="/admin/vendor/fonts/fontawesome.css" />
    <link rel="stylesheet" href="/admin/vendor/libs/node-waves/node-waves.css" />
    <link rel="stylesheet" href="/admin/vendor/css/core.css" />
    <link rel="stylesheet" href="/admin/css/demo.css" />
    <link rel="stylesheet" href="/admin/vendor/libs/perfect-scrollbar/perfect-scrollbar.css" />
    <script src="/admin/vendor/js/helpers.js"></script>
    <script src="/admin/js/config.js"></script>
    @include('layouts.partials.theme-init')
    @include('layouts.partials.fonts')
    <link rel="stylesheet" href="{{ asset('admin/custom.css') }}" />
    <style>
        .portal-stat .icon { width: 2.75rem; height: 2.75rem; display: inline-flex; align-items: center; justify-content: center; border-radius: .5rem; }
    </style>
    <link rel="stylesheet" href="/admin/vendor/libs/sweetalert2/sweetalert2.css" />
    @stack('css')
</head>
<body>
{{-- @php كبلوك مش سطر: لو @php(...) جه قبل بلوك @php ... @endphp في نفس الملف Blade بيبلع اللي بينهم --}}
@php
    $me = auth('employee')->user();

    $mainMenu = [
        ['portal.home',              'fa-house',               'home',        'portal.home'],
        ['portal.tasks.index',       'fa-list-check',          'tasks',       'portal.tasks.*'],
        ['portal.projects.index',    'fa-diagram-project',     'projects',    'portal.projects.*'],
        ['portal.time.index',        'fa-stopwatch',           'time',        'portal.time.*'],
        ['portal.tasks.performance', 'fa-chart-column',        'performance', 'portal.tasks.performance'],
        ['portal.permissions.index', 'fa-clock',               'permissions', 'portal.permissions.*'],
        ['portal.leaves.index',      'fa-calendar-check',      'leaves',      'portal.leaves.*'],
        ['portal.advances.index',    'fa-hand-holding-dollar', 'advances',    'portal.advances.*'],
        ['portal.documents.index',   'fa-folder-open',         'documents',   'portal.documents.*'],
    ];

    // الشاشات المالية (عرض بس) في مجموعة واحدة
    $finance = [
        ['portal.salary.index',    'fa-sack-dollar',         'salary',    'portal.salary.*'],
        ['portal.payslips.index',  'fa-file-invoice-dollar', 'payslips',  'portal.payslips.*'],
        ['portal.rewards.index',   'fa-gift',                'rewards',   'portal.rewards.*'],
        ['portal.penalties.index', 'fa-scale-balanced',      'penalties', 'portal.penalties.*'],
        ['portal.expenses.index',  'fa-wallet',              'expenses',  'portal.expenses.*'],
    ];
    $financeActive = request()->routeIs(...array_column($finance, 3));
@endphp

<div class="layout-wrapper layout-content-navbar">
    <div class="layout-container">

        {{-- القائمة الجانبية --}}
        <aside id="layout-menu" class="layout-menu menu-vertical menu">
            <div class="app-brand demo">
                <a href="{{ route('portal.home') }}" class="app-brand-link">
                    @if (setting('site_logo'))
                        <span class="app-brand-logo demo">
                            <img src="{{ setting('site_logo') }}" alt="Logo" class="w-px-40 h-auto rounded-circle">
                        </span>
                    @endif
                    <span class="app-brand-text demo menu-text fw-bold ms-3">{{ __('portal.title') }}</span>
                </a>
                <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto">
                    <i class="icon-base ti menu-toggle-icon d-none d-xl-block"></i>
                    <i class="icon-base ti tabler-x d-block d-xl-none"></i>
                </a>
            </div>
            <div class="menu-inner-shadow"></div>

            <ul class="menu-inner py-1">
                @foreach ($mainMenu as [$route, $icon, $label, $pattern])
                    <li class="menu-item @if (request()->routeIs($pattern)) active @endif">
                        <a href="{{ route($route) }}" class="menu-link">
                            <i class="fa-solid {{ $icon }}"></i>
                            <div>{{ __('portal.nav.' . $label) }}</div>
                        </a>
                    </li>
                @endforeach

                <li class="menu-item @if ($financeActive) active open @endif">
                    <a href="javascript:void(0);" class="menu-link menu-toggle">
                        <i class="fa-solid fa-coins"></i>
                        <div>{{ __('portal.nav.finance') }}</div>
                    </a>
                    <ul class="menu-sub">
                        @foreach ($finance as [$route, $icon, $label, $pattern])
                            <li class="menu-item @if (request()->routeIs($pattern)) active @endif">
                                <a href="{{ route($route) }}" class="menu-link">
                                    <div>{{ __('portal.nav.' . $label) }}</div>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </li>

                <li class="menu-item @if (request()->routeIs('portal.profile')) active @endif">
                    <a href="{{ route('portal.profile') }}" class="menu-link">
                        <i class="fa-solid fa-id-card"></i>
                        <div>{{ __('portal.nav.profile') }}</div>
                    </a>
                </li>

                <li class="menu-item @if (request()->routeIs('portal.password.*')) active @endif">
                    <a href="{{ route('portal.password.edit') }}" class="menu-link">
                        <i class="fa-solid fa-key"></i>
                        <div>{{ __('portal.nav.change_password') }}</div>
                    </a>
                </li>

                <li class="menu-item">
                    <form action="{{ route('portal.logout') }}" method="POST" class="m-0" data-no-confirm>
                        @csrf
                        <a href="#" class="menu-link" onclick="event.preventDefault(); this.closest('form').submit();">
                            <i class="fa-solid fa-right-from-bracket"></i>
                            <div>{{ __('portal.nav.logout') }}</div>
                        </a>
                    </form>
                </li>
            </ul>
        </aside>

        <div class="menu-mobile-toggler d-xl-none rounded-1">
            <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large text-bg-secondary p-2 rounded-1">
                <i class="ti tabler-menu icon-base"></i>
                <i class="ti tabler-chevron-right icon-base"></i>
            </a>
        </div>

        <div class="layout-page">

            {{-- الشريط العلوي — نفس شريط لوحة الإدارة --}}
            <nav class="layout-navbar container-fluid navbar-detached navbar navbar-expand-xl align-items-center bg-navbar-theme" id="layout-navbar">
                <div class="layout-menu-toggle navbar-nav align-items-xl-center me-3 me-xl-0 d-xl-none">
                    <a class="nav-item nav-link px-0 me-xl-6" href="javascript:void(0)" role="button" aria-label="{{ __('global.toggle_menu') }}">
                        <i class="icon-base ti tabler-menu-2 icon-md" aria-hidden="true"></i>
                    </a>
                </div>

                <div class="navbar-nav-right d-flex align-items-center justify-content-end" id="navbar-collapse">
                    {{-- الوضع الليلي --}}
                    <div class="navbar-nav align-items-center">
                        <div class="nav-item dropdown me-2 me-xl-0">
                            <a class="nav-link dropdown-toggle hide-arrow" id="nav-theme" href="javascript:void(0);" role="button"
                               aria-label="{{ __('global.toggle_theme') }}" title="{{ __('global.toggle_theme') }}" data-bs-toggle="dropdown">
                                <i class="icon-base ti tabler-sun icon-md theme-icon-active" aria-hidden="true"></i>
                                <span class="d-none ms-2" id="nav-theme-text">{{ __('global.toggle_theme') }}</span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-start" aria-labelledby="nav-theme-text">
                                <li>
                                    <button type="button" class="dropdown-item align-items-center active" data-bs-theme-value="light" aria-pressed="false">
                                        <span><i class="icon-base ti tabler-sun icon-md me-3" data-icon="sun"></i>{{ __('global.theme_light') }}</span>
                                    </button>
                                </li>
                                <li>
                                    <button type="button" class="dropdown-item align-items-center" data-bs-theme-value="dark" aria-pressed="true">
                                        <span><i class="icon-base ti tabler-moon-stars icon-md me-3" data-icon="moon-stars"></i>{{ __('global.theme_dark') }}</span>
                                    </button>
                                </li>
                                <li>
                                    <button type="button" class="dropdown-item align-items-center" data-bs-theme-value="system" aria-pressed="false">
                                        <span><i class="icon-base ti tabler-device-desktop-analytics icon-md me-3" data-icon="device-desktop-analytics"></i>{{ __('global.theme_system') }}</span>
                                    </button>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <ul class="navbar-nav flex-row align-items-center ms-md-auto">
                        {{-- اختصار: طلب جديد --}}
                        <li class="nav-item me-2 me-xl-3 d-none d-sm-block">
                            <a class="btn btn-sm btn-label-primary waves-effect" href="{{ route('portal.permissions.index') }}#new">
                                <i class="fa-solid fa-plus me-1"></i> {{ __('portal.permissions.new') }}
                            </a>
                        </li>

                        @include('portal.partials.running-timer')
                        @include('portal.partials.notifications-bell')

                        {{-- المستخدم --}}
                        <li class="nav-item navbar-dropdown dropdown-user dropdown">
                            <a class="nav-link dropdown-toggle hide-arrow p-0" href="javascript:void(0);" role="button" aria-label="{{ __('global.user_menu') }}" data-bs-toggle="dropdown">
                                <div class="avatar avatar-online">
                                    <img src="{{ $me->avatar_url }}" alt="{{ $me->name }}" class="rounded-circle" style="object-fit: cover" />
                                </div>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li>
                                    <a class="dropdown-item" href="{{ route('portal.profile') }}">
                                        <div class="d-flex">
                                            <div class="flex-shrink-0 me-3">
                                                <div class="avatar avatar-online">
                                                    <img src="{{ $me->avatar_url }}" alt="{{ $me->name }}" class="w-px-40 h-auto rounded-circle" style="object-fit: cover" />
                                                </div>
                                            </div>
                                            <div class="flex-grow-1">
                                                <h6 class="mb-0">{{ $me->name }}</h6>
                                                <small class="text-body-secondary" dir="ltr">{{ $me->employee_code }}</small>
                                            </div>
                                        </div>
                                    </a>
                                </li>
                                <li><div class="dropdown-divider my-1 mx-n2"></div></li>
                                <li>
                                    <a class="dropdown-item" href="{{ route('portal.profile') }}">
                                        <i class="icon-base ti tabler-user icon-md me-3"></i><span>{{ __('portal.nav.profile') }}</span>
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="{{ route('portal.password.edit') }}">
                                        <i class="icon-base ti tabler-key icon-md me-3"></i><span>{{ __('portal.nav.change_password') }}</span>
                                    </a>
                                </li>
                                <li><div class="dropdown-divider my-1 mx-n2"></div></li>
                                <li>
                                    {{-- الخروج بـ submit() مباشرة: مالوش تأكيد --}}
                                    <form action="{{ route('portal.logout') }}" method="POST" class="m-0" data-no-confirm>
                                        @csrf
                                        <a class="dropdown-item" href="#" onclick="event.preventDefault(); this.closest('form').submit();">
                                            <i class="icon-base ti tabler-power icon-md me-3"></i><span>{{ __('portal.nav.logout') }}</span>
                                        </a>
                                    </form>
                                </li>
                            </ul>
                        </li>
                    </ul>
                </div>
            </nav>

            <div class="content-wrapper">
                <div class="container-fluid flex-grow-1 container-p-y">
                    @foreach (['success' => 'success', 'error' => 'danger'] as $key => $class)
                        @if (session($key))
                            <div class="alert alert-{{ $class }} alert-dismissible">
                                {{ session($key) }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        @endif
                    @endforeach

                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0 ps-3">
                                @foreach ($errors->all() as $message)
                                    <li>{{ $message }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @yield('content')
                </div>

                <footer class="content-footer footer bg-footer-theme">
                    <div class="container-fluid">
                        <div class="footer-container d-flex align-items-center justify-content-between py-4 flex-md-row flex-column">
                            <div class="text-body">Copyright &copy; {{ date('Y') }} {{ setting('site_name') }}. All rights reserved.</div>
                        </div>
                    </div>
                </footer>

                <div class="content-backdrop fade"></div>
            </div>
        </div>
    </div>

    <div class="layout-overlay layout-menu-toggle"></div>
    <div class="drag-target"></div>
</div>

<script src="/admin/vendor/libs/jquery/jquery.js"></script>
<script src="/admin/vendor/libs/popper/popper.js"></script>
<script src="/admin/vendor/js/bootstrap.js"></script>
<script src="/admin/vendor/libs/node-waves/node-waves.js"></script>
<script src="/admin/vendor/libs/perfect-scrollbar/perfect-scrollbar.js"></script>
<script src="/admin/vendor/libs/hammer/hammer.js"></script>
<script src="/admin/vendor/js/menu.js"></script>
<script src="/admin/js/main.js"></script>
<script src="/admin/vendor/libs/sweetalert2/sweetalert2.js"></script>
@include('layouts.partials.confirm-submit')
@stack('js')
</body>
</html>
