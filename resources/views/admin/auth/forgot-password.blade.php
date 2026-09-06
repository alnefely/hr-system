<!doctype html>
<html
  lang="{{ vuexy_setting('lang') }}"
  class="{{ vuexy_setting(key:'class', customizerHide:true) }}"
  dir="{{ vuexy_setting('dir') }}"
  data-skin="{{ vuexy_setting('data_skin') }}"
  data-bs-theme="{{ vuexy_setting('data_bs_theme') }}"
  data-assets-path="{{ vuexy_setting('data_assets_path') }}"
  data-template="{{ vuexy_setting('data_template') }}">
>
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <title>{{ __('auth.reset_password') }}</title>

    <meta name="description" content="" />

    <link rel="icon" type="image/x-icon" href="{{setting('site_favicon')}}" />

    <!-- Core CSS -->
    <link rel="stylesheet" href="/admin/vendor/fonts/iconify-icons.css" />
    <link rel="stylesheet" href="/admin/vendor/libs/node-waves/node-waves.css" />
    <link rel="stylesheet" href="/admin/vendor/libs/pickr/pickr-themes.css" />
    <link rel="stylesheet" href="/admin/vendor/css/core.css" />
    <link rel="stylesheet" href="/admin/css/demo.css" />
    <link rel="stylesheet" href="/admin/vendor/libs/perfect-scrollbar/perfect-scrollbar.css" />
    <link rel="stylesheet" href="/admin/vendor/css/pages/page-auth.css" />
    <link rel="stylesheet" href="/admin/custom.css" />

    <!-- Core JS -->
    <script src="/admin/vendor/js/helpers.js"></script>
    <script src="/admin/vendor/js/template-customizer.js"></script>
    <script src="/admin/js/config.js"></script>
</head>
<body>
    <div class="container-xxl">
        <div class="authentication-wrapper authentication-basic container-p-y">
            <div class="authentication-inner py-6">
                <!-- Login Card -->
                <div class="card">
                    <div class="card-body">
                        <!-- Logo -->
                        <div class="app-brand justify-content-center mb-6">
                            <a href="{{ url('/') }}" class="app-brand-link">
                                <span class="app-brand-logo demo">
                                    <img src="{{setting('site_logo')}}" alt="{{setting('site_name')}}" width="60" height="60" />
                                </span>
                            </a>
                        </div>
                        <!-- /Logo -->
                        <h4 class="mb-1">{{__('auth.forgot_password')}} 🔒</h4>
                        <p class="mb-6">{{__('auth.enter_email_to_reset')}}</p>
                        @if( $errors->all() )
                            @foreach ($errors->all() as $message)
                                <div class="alert alert-danger mb-1 p-1"><i class="fas fa-exclamation-triangle"></i> {{ $message }}</div>
                            @endforeach
                        @endif
                        <form id="formAuthentication" class="mb-6 fv-plugins-bootstrap5 fv-plugins-framework" action="{{route('admin.sendPassword')}}" method="post">@csrf
                            <div class="mb-6 form-control-validation fv-plugins-icon-container">
                            <label for="email" class="form-label">{{__('global.email')}} <strong class="text-danger">*</strong></label>
                            <input
                              type="email"
                              class="form-control mb-3"
                              id="email"
                              name="email"
                              dir="ltr"
                              placeholder="{{ __('auth.enter_email_to_reset') }}"
                              required
                              autofocus />
                            <button class="btn btn-primary d-grid w-100 waves-effect mb-3 waves-light">{{__('auth.reset_password')}}</button>
                        <input type="hidden"></form>
                        <div class="text-center">
                            <a href="{{ route('admin.login') }}" class="d-flex justify-content-center">
                                <i class="icon-base ti tabler-chevron-left scaleX-n1-rtl me-1_5"></i>
                                {{__('auth.back_to_login')}}
                            </a>
                        </div>
                        </div>
                </div>
                <!-- /Login Card -->
            </div>
        </div>
    </div>

    <!-- Core JS -->
    <script src="/admin/vendor/libs/jquery/jquery.js"></script>
    <script src="/admin/vendor/libs/popper/popper.js"></script>
    <script src="/admin/vendor/js/bootstrap.js"></script>
    <script src="/admin/vendor/libs/node-waves/node-waves.js"></script>
    <script src="/admin/vendor/libs/@algolia/autocomplete-js.js"></script>
    <script src="/admin/vendor/libs/pickr/pickr.js"></script>
    <script src="/admin/vendor/libs/perfect-scrollbar/perfect-scrollbar.js"></script>
    <script src="/admin/vendor/libs/hammer/hammer.js"></script>
    <script src="/admin/vendor/js/menu.js"></script>
    <script src="/admin/js/main.js"></script>
</body>
</html>
