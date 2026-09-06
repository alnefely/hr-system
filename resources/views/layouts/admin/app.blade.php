<!doctype html>

<html
  lang="{{ vuexy_setting('lang') }}"
  dir="{{ vuexy_setting('dir') }}"
  class="{{ vuexy_setting('class') }}"
  data-skin="{{ vuexy_setting('data_skin') }}"
  data-bs-theme="{{ vuexy_setting('data_bs_theme') }}"
  data-assets-path="{{ vuexy_setting('data_assets_path') }}"
  data-template="{{ vuexy_setting('data_template') }}">
  <head>
    <meta charset="utf-8" />
    <meta
      name="viewport"
      content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <meta name="robots" content="noindex, nofollow" />
    <title>@yield('title', setting('site_name'))</title>

    <meta name="description" content="" />

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="{{ setting('site_favicon') }}" />
  

    <link rel="stylesheet" href="/admin/vendor/fonts/iconify-icons.css" />

    <script src="/admin/vendor/libs/@algolia/autocomplete-js.js"></script>

    <!-- Core CSS -->
    <link rel="stylesheet" href="/admin/vendor/fonts/fontawesome.css" />

    <link rel="stylesheet" href="/admin/vendor/libs/node-waves/node-waves.css" />

    <link rel="stylesheet" href="/admin/vendor/libs/pickr/pickr-themes.css" />

    <link rel="stylesheet" href="/admin/vendor/css/core.css" />
    <link rel="stylesheet" href="/admin/css/demo.css" />

    <!-- Vendors CSS -->

    <link rel="stylesheet" href="/admin/vendor/libs/perfect-scrollbar/perfect-scrollbar.css" />

    <!-- endbuild -->

    <!-- Page CSS -->

    <!-- Helpers -->
    <script src="/admin/vendor/js/helpers.js"></script>
    <!--! Template customizer & Theme config files MUST be included after core stylesheets and helpers.js in the <head> section -->

    <!--? Template customizer: To hide customizer set displayCustomizer value false in config.js.  -->
    <script src="/admin/vendor/js/template-customizer.js"></script>

    <!--? Config:  Mandatory theme config file contain global vars & default theme options, Set your preferred theme option in this file.  -->

    <script src="/admin/js/config.js"></script>
    <link rel="stylesheet" href="{{ asset('admin/custom.css') }}" />
    @stack('css')
  </head>

  <body>
    <!-- Layout wrapper -->
    <div class="layout-wrapper layout-content-navbar  ">
      <div class="layout-container">
        <!-- Menu -->

       @include('layouts.admin.aside')

        <div class="menu-mobile-toggler d-xl-none rounded-1">
          <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large text-bg-secondary p-2 rounded-1">
            <i class="ti tabler-menu icon-base"></i>
            <i class="ti tabler-chevron-right icon-base"></i>
          </a>
        </div>
        <!-- / Menu -->

        <!-- Layout container -->
        <div class="layout-page">
          <!-- Navbar -->
          @include('layouts.admin.navbar')
          

          <!-- / Navbar -->

          <!-- Content wrapper -->
          <div class="content-wrapper">
            <!-- Content -->
            <div class="container-xxl flex-grow-1 container-p-y">

              @if( $errors->all() )
                  @foreach ($errors->all() as $message)
                      <div class="alert alert-danger mb-1 p-1"><i class="fas fa-exclamation-triangle"></i> {{ $message }}</div>
                  @endforeach
              @endif
              @yield('content')
            </div>
            <!-- / Content -->

            <!-- Footer -->
            <footer class="content-footer footer bg-footer-theme">
              <div class="container-xxl">
                <div
                  class="footer-container d-flex align-items-center justify-content-between py-4 flex-md-row flex-column">
                  <div class="text-body">
                    Copyright &copy; {{ date('Y') }} {{ setting('site_name') }}. All rights reserved.
                  </div>
                  <div class="d-none d-lg-inline-block">
                    <a
                      href="https://fb.com/alnefelys"
                      target="_blank"
                      class="footer-link me-4"
                      >Rhmany</a
                    >
                  </div>
                </div>
              </div>
            </footer>
            <!-- / Footer -->

            <div class="content-backdrop fade"></div>
          </div>
          <!-- Content wrapper -->
        </div>
        <!-- / Layout page -->
      </div>

      <!-- Overlay -->
      <div class="layout-overlay layout-menu-toggle"></div>

      <!-- Drag Target Area To SlideIn Menu On Small Screens -->
      <div class="drag-target"></div>
    </div>
    <!-- / Layout wrapper -->

    <!-- Core JS -->
    <!-- build:js assets/vendor/js/theme.js  -->

    <script src="/admin/vendor/libs/jquery/jquery.js"></script>

    <script src="/admin/vendor/libs/popper/popper.js"></script>
    <script src="/admin/vendor/js/bootstrap.js"></script>
    <script src="/admin/vendor/libs/node-waves/node-waves.js"></script>

    <script src="/admin/vendor/libs/pickr/pickr.js"></script>

    <script src="/admin/vendor/libs/perfect-scrollbar/perfect-scrollbar.js"></script>

    <script src="/admin/vendor/libs/hammer/hammer.js"></script>

    <script src="/admin/vendor/js/menu.js"></script>

    <!-- endbuild -->

    <!-- Vendors JS -->

    <!-- Main JS -->

    <script src="/admin/js/main.js"></script>
    <script>
      // let currentPath = window.location.pathname;
      // $("aside a").each(function () {
      //     let linkPath = new URL(this.href).pathname;
      //     if (currentPath === linkPath) {
      //       $(this).parent().addClass('active');
      //       $(this).closest('.menu-sub').parent().addClass('active open');
      //     }
      // });
      let currentPath = window.location.pathname;
      let currentSearch = window.location.search;

      $("aside a").each(function () {
          if (!this.href || this.href === 'javascript:void(0);') return;
          
          let url = new URL(this.href);
          let linkPath = url.pathname;
          let linkSearch = url.search;

          if (currentPath === linkPath && currentSearch === linkSearch) {
              $(this).parent().addClass('active');
              $(this).closest('.menu-sub').parent().addClass('active open');
          }
      });
    </script>
    @include('layouts.alert')

    @stack('js')
  </body>
</html>
