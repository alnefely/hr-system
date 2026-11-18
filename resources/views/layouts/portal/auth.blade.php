<!doctype html>
{{--
    قالب صفحات دخول البوابة (من غير قائمة).

    لازم يكون layout مش صفحة مستقلة: Blade بيرندر الصفحة الابن الأول، فأي
    @push من مكوّن جوّه المحتوى (زي x-inputs.phone اللي بيضيف CSS مكتبة
    الأرقام) بيوصل لـ @stack('css') في الـ head. في صفحة مستقلة الـ head
    بيترندر قبل المكوّن فالـ CSS كان بيضيع.
--}}
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
    <title>@yield('title') — {{ setting('site_name') }}</title>
    <link rel="icon" type="image/x-icon" href="{{ setting('site_favicon') }}" />
    <link rel="stylesheet" href="/admin/vendor/fonts/iconify-icons.css" />
    <link rel="stylesheet" href="/admin/vendor/fonts/fontawesome.css" />
    <link rel="stylesheet" href="/admin/vendor/css/core.css" />
    <link rel="stylesheet" href="/admin/css/demo.css" />
    <link rel="stylesheet" href="/admin/vendor/css/pages/page-auth.css" />
    <script src="/admin/vendor/js/helpers.js"></script>
    <script src="/admin/js/config.js"></script>
    @include('layouts.partials.theme-init')
    <link rel="stylesheet" href="{{ asset('admin/custom.css') }}" />
    @stack('css')
</head>
<body>
<div class="container-xxl">
    <div class="authentication-wrapper authentication-basic container-p-y">
        <div class="authentication-inner py-6">
            <div class="card">
                <div class="card-body">
                    <div class="app-brand justify-content-center mb-6">
                        <span class="app-brand-logo demo">
                            @if (setting('site_logo'))
                                <img src="{{ setting('site_logo') }}" alt="{{ setting('site_name') }}" width="50" />
                            @endif
                        </span>
                        <span class="app-brand-text demo text-heading fw-bold">{{ setting('site_name') }}</span>
                    </div>

                    @yield('content')
                </div>
            </div>
        </div>
    </div>
</div>

<script src="/admin/vendor/libs/jquery/jquery.js"></script>
<script src="/admin/vendor/libs/popper/popper.js"></script>
<script src="/admin/vendor/js/bootstrap.js"></script>
<script src="/admin/vendor/js/menu.js"></script>
<script src="/admin/js/main.js"></script>
@stack('js')
</body>
</html>
