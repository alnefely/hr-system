<!doctype html>
<html
  lang="{{ vuexy_setting('lang') }}"
  class="{{ vuexy_setting(key:'class', customizerHide:true) }}"
  dir="{{ vuexy_setting('dir') }}"
  data-skin="{{ vuexy_setting('data_skin') }}"
  data-bs-theme="{{ vuexy_setting('data_bs_theme') }}"
  data-assets-path="{{ vuexy_setting('data_assets_path') }}"
  data-template="{{ vuexy_setting('data_template') }}">
  <head>
    <meta charset="utf-8" />
    <meta
      name="viewport"
      content="width=device-width, initial-scale=1" />
    <meta name="robots" content="noindex, nofollow" />
    <title>{{ setting('site_name') }}</title>

    <meta name="description" content="" />

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="{{setting('site_favicon')}}" />

    <link rel="stylesheet" href="/admin/vendor/fonts/iconify-icons.css" />


    <!-- Core CSS -->
    <!-- build:css assets/vendor/css/theme.css  -->

    <link rel="stylesheet" href="/admin/vendor/libs/node-waves/node-waves.css" />


    <link rel="stylesheet" href="/admin/vendor/css/core.css" />
    <link rel="stylesheet" href="/admin/css/demo.css" />

    <!-- Vendors CSS -->

    <link rel="stylesheet" href="/admin/vendor/libs/perfect-scrollbar/perfect-scrollbar.css" />

    <!-- endbuild -->

    <!-- Page CSS -->
    <!-- Page -->
    <link rel="stylesheet" href="/admin/vendor/css/pages/page-auth.css" />

    <!-- Helpers -->
    <script src="/admin/vendor/js/helpers.js"></script>
    <!--! Template customizer & Theme config files MUST be included after core stylesheets and helpers.js in the <head> section -->

    <!--? Template customizer: To hide customizer set displayCustomizer value false in config.js.  -->

    <!--? Config:  Mandatory theme config file contain global vars & default theme options, Set your preferred theme option in this file.  -->

    <script src="/admin/js/config.js"></script>
    @include('layouts.partials.theme-init')
    @include('layouts.partials.fonts')
    <link rel="stylesheet" href="{{ asset('admin/custom.css') }}" />
  </head>

  <body>
    <!-- Content -->

    <div class="container-xxl">
      <div class="authentication-wrapper authentication-basic container-p-y">
        <div class="authentication-inner py-6">
          <!-- Login -->
          <div class="card">
            <div class="card-body">
              <!-- Logo -->
              <div class="app-brand justify-content-center mb-6">
                <a href="{{url('/')}}" class="app-brand-link">
                  <span class="app-brand-logo demo">
                    <img src="{{ setting('site_logo') }}" alt="{{ setting('site_name') }}" width="50" />
                  </span>
                  <span class="app-brand-text demo text-heading fw-bold">{{ setting('site_name') }}</span>
                </a>
              </div>
              <!-- /Logo -->
              <h4 class="mb-1">{{ __('auth.welcome', ['name' => setting('site_name')]) }} 👋</h4>
              <p class="mb-6">{{ __('auth.please_signin') }}</p>

              <form id="formAuthentication" class="mb-4" action="{{ route('admin.checkLogin') }}" method="POST">@csrf
                <div class="mb-6 form-control-validation">
                  <label for="email" class="form-label">{{ __('global.email') }} <strong class="text-danger">*</strong></label>
                  <input
                    type="email"
                    class="form-control"
                    id="email"
                    name="email"
                    dir="ltr"
                    @if( env('APP_ENV') == 'local') value="admin@admin.com" @else" value="{{old('email')}}" @endif
                    placeholder="{{ __('global.email') }}"
                    required
                    autofocus />
                </div>
                <div class="mb-6 form-password-toggle form-control-validation">
                  <label class="form-label" for="password">{{ __('global.password') }} <strong class="text-danger">*</strong></label>
                  <div class="input-group input-group-merge">
                    <input
                      type="password"
                      id="password"
                      class="form-control"
                      name="password"
                      @if( env('APP_ENV') == 'local') value="123456" @endif
                      placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;"
                      aria-describedby="password"
                      required />
                    <span class="input-group-text cursor-pointer"><i class="icon-base ti tabler-eye-off"></i></span>
                  </div>
                </div>
                <div class="my-8">
                  <div class="d-flex justify-content-between">
                    <div class="form-check mb-0 ms-2">
                      <input class="form-check-input" type="checkbox" @if(old('remember')) checked @endif id="remember-me" name="remember" />
                      <label class="form-check-label" for="remember-me"> {{ __('auth.remember_me') }} </label>
                    </div>
                    <a href="{{ route('admin.forgotPassword') }}">
                      <p class="mb-0"> {{ __('auth.forgot_password') }} </p>
                    </a>
                  </div>
                </div>
                <div class="mb-6">
                  <button class="btn btn-primary d-grid w-100" type="submit">{{ __('global.login') }}</button>
                </div>
              </form>
              <p class="text-center mb-0 mt-4">
                  <a href="{{ route('home') }}" class="small"><i class="fa-solid fa-table-cells-large me-1"></i>{{ __('landing.choose_panel') }}</a>
              </p>
            </div>
          </div>
          <!-- /Login -->
        </div>
      </div>
    </div>

    <!-- / Content -->

    <!-- Core JS -->
    <!-- build:js assets/vendor/js/theme.js  -->

    <script src="/admin/vendor/libs/jquery/jquery.js"></script>

    <script src="/admin/vendor/libs/popper/popper.js"></script>
    <script src="/admin/vendor/js/bootstrap.js"></script>
    <script src="/admin/vendor/libs/node-waves/node-waves.js"></script>


    <script src="/admin/vendor/libs/perfect-scrollbar/perfect-scrollbar.js"></script>

    <script src="/admin/vendor/libs/hammer/hammer.js"></script>

    <script src="/admin/vendor/libs/i18n/i18n.js"></script>

    <script src="/admin/vendor/js/menu.js"></script>


    <!-- Main JS -->
    <script src="/admin/js/main.js"></script>

    <!-- Page JS -->
    {{-- <script src="/admin/js/pages-auth.js"></script> --}}
    @include('layouts.alert')
  </body>
</html>
