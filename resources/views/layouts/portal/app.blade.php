<!doctype html>
<html lang="{{ vuexy_setting('lang') }}" dir="{{ vuexy_setting('dir') }}"
      class="layout-wide customizer-hide"
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
    <script src="/admin/vendor/js/helpers.js"></script>
    <script src="/admin/js/config.js"></script>
    <link rel="stylesheet" href="{{ asset('admin/custom.css') }}" />
    <style>
        body { background: var(--bs-body-bg); }
        .portal-nav .nav-link.active { color: var(--bs-primary) !important; font-weight: 600; }
        .portal-stat .icon { width: 2.75rem; height: 2.75rem; display: inline-flex; align-items: center; justify-content: center; border-radius: .5rem; }
    </style>
    <link rel="stylesheet" href="/admin/vendor/libs/sweetalert2/sweetalert2.css" />
    @stack('css')
</head>
<body>
@php($me = auth('employee')->user())

<nav class="navbar navbar-expand-lg bg-body shadow-sm portal-nav mb-4">
    <div class="container-xxl">
        <a class="navbar-brand d-flex align-items-center gap-2 fw-bold" href="{{ route('portal.home') }}">
            @if (setting('site_logo'))
                <img src="{{ setting('site_logo') }}" alt="" height="32">
            @endif
            <span>{{ __('portal.title') }}</span>
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#portalNav" aria-controls="portalNav" aria-expanded="false" aria-label="menu">
            <i class="fa-solid fa-bars"></i>
        </button>

        <div class="collapse navbar-collapse" id="portalNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0 gap-lg-1">
                @foreach ([
                    ['portal.home',              'fa-house',               'home',        'portal.home'],
                    ['portal.permissions.index', 'fa-clock',               'permissions', 'portal.permissions.*'],
                    ['portal.leaves.index',      'fa-calendar-check',      'leaves',      'portal.leaves.*'],
                    ['portal.advances.index',    'fa-hand-holding-dollar', 'advances',    'portal.advances.*'],
                    ['portal.payslips.index',    'fa-file-invoice-dollar', 'payslips',    'portal.payslips.*'],
                ] as [$route, $icon, $label, $pattern])
                    <li class="nav-item">
                        <a class="nav-link @if (request()->routeIs($pattern)) active @endif" href="{{ route($route) }}">
                            <i class="fa-solid {{ $icon }} me-1"></i> {{ __('portal.nav.' . $label) }}
                        </a>
                    </li>
                @endforeach
            </ul>

            <div class="dropdown">
                <a class="nav-link dropdown-toggle d-flex align-items-center gap-2" href="#" data-bs-toggle="dropdown" aria-expanded="false">
                    <img src="{{ $me->avatar_url }}" alt="" class="rounded-circle" width="32" height="32" style="object-fit: cover">
                    <span>{{ $me->name }}</span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="{{ route('portal.profile') }}"><i class="fa-solid fa-id-card me-2"></i>{{ __('portal.nav.profile') }}</a></li>
                    <li><a class="dropdown-item" href="{{ route('portal.password.edit') }}"><i class="fa-solid fa-key me-2"></i>{{ __('portal.nav.change_password') }}</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <form action="{{ route('portal.logout') }}" method="POST" class="m-0">
                            @csrf
                            <button type="submit" class="dropdown-item text-danger"><i class="fa-solid fa-right-from-bracket me-2"></i>{{ __('portal.nav.logout') }}</button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</nav>

<main class="container-xxl pb-5">
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
</main>

<script src="/admin/vendor/libs/jquery/jquery.js"></script>
<script src="/admin/vendor/libs/popper/popper.js"></script>
<script src="/admin/vendor/js/bootstrap.js"></script>
<script src="/admin/vendor/libs/node-waves/node-waves.js"></script>
<script src="/admin/vendor/libs/sweetalert2/sweetalert2.js"></script>
@include('layouts.partials.confirm-submit')
@stack('js')
</body>
</html>
