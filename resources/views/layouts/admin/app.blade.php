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
      content="width=device-width, initial-scale=1" />
    <meta name="robots" content="noindex, nofollow" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', setting('site_name'))</title>

    <meta name="description" content="" />

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="{{ setting('site_favicon') }}" />
  

    <link rel="stylesheet" href="/admin/vendor/fonts/iconify-icons.css" />

    <!-- Core CSS -->
    <link rel="stylesheet" href="/admin/vendor/fonts/fontawesome.css" />

    <link rel="stylesheet" href="/admin/vendor/libs/node-waves/node-waves.css" />

    <link rel="stylesheet" href="/admin/vendor/css/core.css" />
    <link rel="stylesheet" href="/admin/css/demo.css" />

    <!-- Vendors CSS -->

    <link rel="stylesheet" href="/admin/vendor/libs/perfect-scrollbar/perfect-scrollbar.css" />

    <!-- endbuild -->

    <!-- Page CSS -->

    <!-- Helpers -->
    <script src="/admin/vendor/js/helpers.js"></script>
    {{-- لوحة الـ Template Customizer مقفولة (customizer-hide)، فمش بنحمّل
         template-customizer.js ولا pickr (اللي بيستخدمهم الـ customizer بس) ولا
         @algolia/autocomplete (بحث الديمو — مفيش #autocomplete في الواجهة).
         تطبيق الوضع الليلي المحفوظ بيتم في theme-init زي البوابة. --}}
    <script src="/admin/js/config.js"></script>
    @include('layouts.partials.theme-init')
    <link rel="stylesheet" href="/admin/vendor/libs/select2/select2.css" />
    @include('layouts.partials.fonts')
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
            <div class="container-fluid flex-grow-1 container-p-y">

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
              <div class="container-fluid">
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
    <script src="/admin/vendor/libs/select2/select2.js"></script>
    <script>
      // Global Select2 Initializer for all select inputs & modals
      window.initSelect2 = function (context) {
          let $ctx = context ? $(context) : $(document);
          $ctx.find('select.select2, select.form-select:not(.no-select2):not(.dt-select2)').each(function () {
              let $el = $(this);
              if ($el.hasClass('select2-hidden-accessible')) {
                  return;
              }
              let $modal = $el.closest('.modal');
              let placeholderText = $el.data('placeholder') || $el.find('option[value=""]').first().text() || 'اختر...';

              let config = {
                  dir: $('html').attr('dir') === 'rtl' ? 'rtl' : 'ltr',
                  width: '100%',
                  placeholder: placeholderText,
                  allowClear: !$el.prop('required')
              };

              if ($modal.length) {
                  config.dropdownParent = $modal;
              }

              $el.select2(config);
          });
      };

      $(document).ready(function () {
          window.initSelect2();
      });

      // Automatically re-initialize on modal open
      $(document).on('shown.bs.modal', function (e) {
          window.initSelect2(e.target);
      });

      // تفعيل الـ Popovers.
      // ثيم Vuexy بيفعّل الـ Tooltips بس في main.js، فالـ popover
      // مكانش بيشتغل خالص. بنفعّله هنا بدل ما نعدّل ملف الثيم.
      document.addEventListener('DOMContentLoaded', function () {
          document.querySelectorAll('[data-bs-toggle="popover"]').forEach(function (el) {
              new bootstrap.Popover(el, {
                  container: 'body',
                  sanitize: false,   // بنسمح بالـ HTML جوّه المحتوى
              });
          });
      });
    </script>
    @include('layouts.alert')

    {{-- الإشعارات: تحميل القائمة + التحديث اللحظي عبر Pusher --}}
    @if (admin_can('notification_show'))
      @php
          $pusherEnabled = config('broadcasting.default') === 'pusher' && config('broadcasting.connections.pusher.key');
      @endphp

      @if ($pusherEnabled)
        <script src="https://js.pusher.com/8.2/pusher.min.js"></script>
      @endif

      <script>
      (function () {
          const bell    = document.getElementById('notificationsBell');
          if (!bell) return;

          const listEl  = document.getElementById('notificationsList');
          const countEl = document.getElementById('notificationsCount');
          const csrf    = document.querySelector('meta[name="csrf-token"]')?.content;

          const T = {
              empty:     @json(__('notification.empty')),
              emptyHint: @json(__('notification.empty_hint')),
          };

          const esc = (v) => String(v ?? '')
              .replace(/&/g, '&amp;').replace(/</g, '&lt;')
              .replace(/>/g, '&gt;').replace(/"/g, '&quot;');

          function setCount(n) {
              if (!countEl) return;
              countEl.textContent = n;
              countEl.classList.toggle('d-none', !n);
          }

          function render(items) {
              if (!items || items.length === 0) {
                  listEl.innerHTML = '<div class="text-center text-muted py-4 px-3">'
                      + '<i class="fa-solid fa-bell-slash mb-2" style="font-size:1.8rem;opacity:.3"></i>'
                      + '<div class="small fw-semibold">' + esc(T.empty) + '</div>'
                      + '<div class="small">' + esc(T.emptyHint) + '</div></div>';
                  return;
              }

              listEl.innerHTML = items.map(function (n) {
                  const unread = n.is_read ? '' : 'style="background: rgba(115,103,240,0.06);"';
                  return '<a href="' + esc(n.url || '#') + '" data-id="' + n.id + '" data-unread="' + (n.is_read ? '0' : '1') + '"'
                      + ' class="d-flex align-items-start gap-2 px-3 py-2 border-bottom text-body notification-item" ' + unread + '>'
                      + '<span class="badge bg-label-' + esc(n.level) + ' rounded p-2 flex-shrink-0">'
                      + '<i class="' + esc(n.icon) + '"></i></span>'
                      + '<span class="flex-grow-1 small">'
                      + '<span class="d-block fw-semibold">' + esc(n.title) + '</span>'
                      + (n.body ? '<span class="d-block text-muted">' + esc(n.body) + '</span>' : '')
                      + '<span class="d-block text-muted" style="font-size:.72rem">' + esc(n.human_time) + '</span>'
                      + '</span></a>';
              }).join('');
          }

          function load() {
              fetch(@json(route('admin.notifications.latest')), {
                  headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
              })
              .then(r => r.ok ? r.json() : Promise.reject(r.status))
              .then(d => { render(d.notifications); setCount(d.unread_count); })
              .catch(() => { listEl.innerHTML = ''; });
          }

          // نحمّل أول ما القائمة تتفتح — مش مع كل تحميل صفحة
          bell.addEventListener('shown.bs.dropdown', load);

          // الضغط على إشعار غير مقروء: نعلّمه مقروء الأول وبعدين نروح لرابطه.
          // قبل كده الرابط كان بيودّي للصفحة مباشرة من غير ما يعدّي على
          // notifications.read، فالإشعار كان بيفضل غير مقروء والعداد مابيتغيّرش.
          const readUrl = @json(route('admin.notifications.read', ['notification' => '__ID__']));

          listEl.addEventListener('click', function (e) {
              const item = e.target.closest('.notification-item');
              if (!item || item.dataset.unread !== '1') return;   // المقروء: رابط عادي

              e.preventDefault();
              const href = item.getAttribute('href');

              fetch(readUrl.replace('__ID__', item.dataset.id), {
                  method: 'POST',
                  keepalive: true,   // يكمّل حتى لو الصفحة اتنقلت
                  headers: {
                      'X-CSRF-TOKEN': csrf,
                      'X-Requested-With': 'XMLHttpRequest',
                      'Accept': 'application/json'
                  }
              })
              .then(r => r.ok ? r.json() : null)
              .then(d => { if (d) setCount(d.unread_count); })
              .catch(() => {})
              .finally(() => {
                  if (href && href !== '#') {
                      window.location.href = href;
                  } else {
                      item.dataset.unread = '0';
                      item.style.background = '';
                  }
              });
          });

          // تعليم الكل كمقروء
          document.getElementById('markAllNotificationsRead')?.addEventListener('click', function (e) {
              e.preventDefault();
              e.stopPropagation();
              fetch(@json(route('admin.notifications.read-all')), {
                  method: 'POST',
                  headers: {
                      'X-CSRF-TOKEN': csrf,
                      'X-Requested-With': 'XMLHttpRequest',
                      'Accept': 'application/json'
                  }
              }).then(() => { setCount(0); load(); });
          });

          @if ($pusherEnabled)
          // التحديث اللحظي: القناة خاصة بالمشرف الحالي بس
          try {
              const pusher = new Pusher(@json(config('broadcasting.connections.pusher.key')), {
                  cluster: @json(config('broadcasting.connections.pusher.options.cluster')),
                  authEndpoint: @json(route('admin.broadcasting.auth')),
                  auth: { headers: { 'X-CSRF-TOKEN': csrf } }
              });

              pusher.subscribe('private-admin.' + @json(admin()?->id))
                    .bind('notification.created', function (data) {
                        setCount(data.unread_count);
                        if (bell.querySelector('.dropdown-menu.show')) load();
                        // الصفحات المفتوحة (تفاصيل مهمة مثلاً) بتسمع وتعرض تنبيه تحديث
                        document.dispatchEvent(new CustomEvent('admin:notification', { detail: data.notification }));
                    });
          } catch (e) {
              // فشل الاتصال بـ Pusher مايكسرش الصفحة — الجرس يفضل شغال بالتحميل اليدوي
              console.warn('Pusher unavailable:', e);
          }
          @endif
      })();
      </script>
    @endif

    @stack('js')
  </body>
</html>
