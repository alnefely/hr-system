<!doctype html>
<html lang="{{ vuexy_setting('lang') }}" dir="{{ vuexy_setting('dir') }}"
      class="{{ vuexy_setting(key: 'class', customizerHide: true) }}"
      data-skin="{{ vuexy_setting('data_skin') }}"
      data-bs-theme="{{ vuexy_setting('data_bs_theme') }}"
      data-assets-path="{{ vuexy_setting('data_assets_path') }}"
      data-template="{{ vuexy_setting('data_template') }}">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="robots" content="noindex, nofollow" />
    <title>{{ setting('site_name') }}</title>
    <link rel="icon" type="image/x-icon" href="{{ setting('site_favicon') }}" />
    <link rel="stylesheet" href="/admin/vendor/fonts/iconify-icons.css" />
    <link rel="stylesheet" href="/admin/vendor/fonts/fontawesome.css" />
    <link rel="stylesheet" href="/admin/vendor/css/core.css" />
    <link rel="stylesheet" href="/admin/css/demo.css" />
    <script src="/admin/vendor/js/helpers.js"></script>
    <script src="/admin/js/config.js"></script>
    @include('layouts.partials.theme-init')
    @include('layouts.partials.fonts')
    <link rel="stylesheet" href="{{ asset('admin/custom.css') }}" />
    <style>
        .landing { min-height: 100vh; display: flex; align-items: center; padding: 2rem 1rem; }
        .panel-card { transition: transform .15s ease, box-shadow .15s ease; text-decoration: none; height: 100%; }
        .panel-card:hover, .panel-card:focus-visible { transform: translateY(-4px); box-shadow: 0 .5rem 1.5rem rgba(0, 0, 0, .12); }
        .panel-icon { width: 72px; height: 72px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 1.9rem; }
    </style>
</head>
<body>
<div class="landing">
    <div class="container" style="max-width: 860px">
        <div class="text-center mb-8">
            @if (setting('site_logo'))
                <img src="{{ setting('site_logo') }}" alt="{{ setting('site_name') }}" height="64" class="mb-4">
            @endif
            <h3 class="mb-2">{{ setting('site_name') }}</h3>
            <p class="text-muted mb-0">{{ __('landing.subtitle') }}</p>
        </div>

        <div class="row g-6 justify-content-center">
            {{-- بوابة الموظف --}}
            <div class="col-md-6">
                <a href="{{ route($employee ? 'portal.home' : 'portal.login') }}" class="card panel-card">
                    <div class="card-body text-center p-6">
                        <span class="panel-icon bg-label-primary mb-4"><i class="fa-solid fa-id-badge"></i></span>
                        <h5 class="mb-2 text-heading">{{ __('landing.portal.title') }}</h5>
                        <p class="text-muted mb-5">{{ __('landing.portal.description') }}</p>
                        @if ($employee)
                            <span class="badge bg-label-success mb-3 d-inline-block">{{ __('landing.signed_in_as', ['name' => $employee->name]) }}</span><br>
                        @endif
                        <span class="btn btn-primary">
                            {{ $employee ? __('landing.continue') : __('landing.portal.button') }}
                            <i class="fa-solid fa-arrow-{{ vuexy_setting('dir') === 'rtl' ? 'left' : 'right' }} ms-2"></i>
                        </span>
                    </div>
                </a>
            </div>

            {{-- لوحة الإدارة --}}
            <div class="col-md-6">
                <a href="{{ route($admin ? 'admin.home' : 'admin.login') }}" class="card panel-card">
                    <div class="card-body text-center p-6">
                        <span class="panel-icon bg-label-secondary mb-4"><i class="fa-solid fa-user-shield"></i></span>
                        <h5 class="mb-2 text-heading">{{ __('landing.admin.title') }}</h5>
                        <p class="text-muted mb-5">{{ __('landing.admin.description') }}</p>
                        @if ($admin)
                            <span class="badge bg-label-success mb-3 d-inline-block">{{ __('landing.signed_in_as', ['name' => $admin->name]) }}</span><br>
                        @endif
                        <span class="btn btn-label-secondary">
                            {{ $admin ? __('landing.continue') : __('landing.admin.button') }}
                            <i class="fa-solid fa-arrow-{{ vuexy_setting('dir') === 'rtl' ? 'left' : 'right' }} ms-2"></i>
                        </span>
                    </div>
                </a>
            </div>
        </div>
    </div>
</div>
</body>
</html>
