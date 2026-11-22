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
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>{{ __('auth.set_new_password') }}</title>

    <meta name="description" content="" />

    <link rel="icon" type="image/x-icon" href="{{setting('site_favicon')}}" />

    <!-- Core CSS -->
    <link rel="stylesheet" href="/admin/vendor/fonts/iconify-icons.css" />
    <link rel="stylesheet" href="/admin/vendor/libs/node-waves/node-waves.css" />
    <link rel="stylesheet" href="/admin/vendor/css/core.css" />
    <link rel="stylesheet" href="/admin/css/demo.css" />
    <link rel="stylesheet" href="/admin/vendor/libs/perfect-scrollbar/perfect-scrollbar.css" />
    <link rel="stylesheet" href="/admin/vendor/css/pages/page-auth.css" />
    @include('layouts.partials.fonts')
    <link rel="stylesheet" href="/admin/custom.css" />

    <!-- Core JS -->
    <script src="/admin/vendor/js/helpers.js"></script>
    <script src="/admin/js/config.js"></script>
    @include('layouts.partials.theme-init')
</head>
<body>
    <div class="container-xxl">
        <div class="authentication-wrapper authentication-basic container-p-y">
            <div class="authentication-inner py-6">
                <!-- Reset Card -->
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

                        <h4 class="mb-1">{{ __('auth.set_new_password') }} 🔑</h4>
                        <p class="mb-6">{{ __('auth.set_new_password_hint') }}</p>

                        @if (session('error'))
                            <div class="alert alert-danger mb-3 p-2">
                                <i class="fas fa-exclamation-triangle"></i> {{ session('error') }}
                            </div>
                        @endif

                        @if ($errors->any())
                            @foreach ($errors->all() as $message)
                                <div class="alert alert-danger mb-1 p-1">
                                    <i class="fas fa-exclamation-triangle"></i> {{ $message }}
                                </div>
                            @endforeach
                        @endif

                        <form id="formResetPassword" class="mb-6" action="{{ route('admin.password.update') }}" method="post">
                            @csrf
                            <input type="hidden" name="token" value="{{ $token }}" />

                            <div class="mb-4">
                                <label for="email" class="form-label">
                                    {{ __('global.email') }} <strong class="text-danger">*</strong>
                                </label>
                                <input
                                    type="email"
                                    class="form-control"
                                    id="email"
                                    name="email"
                                    dir="ltr"
                                    value="{{ old('email', $email) }}"
                                    required
                                    autofocus />
                            </div>

                            <div class="mb-4 form-password-toggle">
                                <label class="form-label" for="password">
                                    {{ __('auth.new_password') }} <strong class="text-danger">*</strong>
                                </label>
                                <div class="input-group input-group-merge">
                                    <input
                                        type="password"
                                        id="password"
                                        class="form-control"
                                        name="password"
                                        dir="ltr"
                                        placeholder="••••••••"
                                        required />
                                    <span class="input-group-text cursor-pointer toggle-password">
                                        <i class="icon-base ti tabler-eye-off"></i>
                                    </span>
                                </div>
                                <div class="form-text">{{ __('auth.password_rules') }}</div>
                            </div>

                            <div class="mb-6 form-password-toggle">
                                <label class="form-label" for="password_confirmation">
                                    {{ __('auth.confirm_password') }} <strong class="text-danger">*</strong>
                                </label>
                                <div class="input-group input-group-merge">
                                    <input
                                        type="password"
                                        id="password_confirmation"
                                        class="form-control"
                                        name="password_confirmation"
                                        dir="ltr"
                                        placeholder="••••••••"
                                        required />
                                    <span class="input-group-text cursor-pointer toggle-password">
                                        <i class="icon-base ti tabler-eye-off"></i>
                                    </span>
                                </div>
                            </div>

                            <button class="btn btn-primary d-grid w-100 waves-effect waves-light">
                                {{ __('auth.save_new_password') }}
                            </button>
                        </form>

                        <div class="text-center">
                            <a href="{{ route('admin.login') }}" class="d-flex justify-content-center">
                                <i class="icon-base ti tabler-chevron-left scaleX-n1-rtl me-1_5"></i>
                                {{ __('auth.back_to_login') }}
                            </a>
                        </div>
                    </div>
                </div>
                <!-- /Reset Card -->
            </div>
        </div>
    </div>

    <!-- Core JS -->
    <script src="/admin/vendor/libs/jquery/jquery.js"></script>
    <script src="/admin/vendor/libs/popper/popper.js"></script>
    <script src="/admin/vendor/js/bootstrap.js"></script>
    <script src="/admin/vendor/libs/node-waves/node-waves.js"></script>
    <script src="/admin/vendor/libs/perfect-scrollbar/perfect-scrollbar.js"></script>
    <script src="/admin/vendor/libs/hammer/hammer.js"></script>
    <script src="/admin/vendor/js/menu.js"></script>
    <script src="/admin/js/main.js"></script>

    <script>
        document.querySelectorAll('.toggle-password').forEach(function (el) {
            el.addEventListener('click', function () {
                var input = this.closest('.input-group').querySelector('input');
                var icon  = this.querySelector('i');
                var show  = input.type === 'password';

                input.type = show ? 'text' : 'password';
                icon.classList.toggle('tabler-eye-off', !show);
                icon.classList.toggle('tabler-eye', show);
            });
        });
    </script>
</body>
</html>
