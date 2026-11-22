<nav
    class="layout-navbar container-xxl navbar-detached navbar navbar-expand-xl align-items-center bg-navbar-theme"
    id="layout-navbar">
    <div class="layout-menu-toggle navbar-nav align-items-xl-center me-3 me-xl-0   d-xl-none ">
      <a class="nav-item nav-link px-0 me-xl-6" href="javascript:void(0)" role="button" aria-label="{{ __('global.toggle_menu') }}">
        <i class="icon-base ti tabler-menu-2 icon-md" aria-hidden="true"></i>
      </a>
    </div>

    <div class="navbar-nav-right d-flex align-items-center justify-content-end" id="navbar-collapse">
      <div class="navbar-nav align-items-center">
        <div class="nav-item dropdown me-2 me-xl-0">
          <a
            class="nav-link dropdown-toggle hide-arrow"
            id="nav-theme"
            href="javascript:void(0);"
            role="button"
            aria-label="{{ __('global.toggle_theme') }}"
            title="{{ __('global.toggle_theme') }}"
            data-bs-toggle="dropdown">
            <i class="icon-base ti tabler-sun icon-md theme-icon-active" aria-hidden="true"></i>
            <span class="d-none ms-2" id="nav-theme-text">{{ __('global.toggle_theme') }}</span>
          </a>
          <ul class="dropdown-menu dropdown-menu-start" aria-labelledby="nav-theme-text">
            <li>
              <button
                type="button"
                class="dropdown-item align-items-center active"
                data-bs-theme-value="light"
                aria-pressed="false">
                <span><i class="icon-base ti tabler-sun icon-md me-3" data-icon="sun"></i>{{ __('global.theme_light') }}</span>
              </button>
            </li>
            <li>
              <button
                type="button"
                class="dropdown-item align-items-center"
                data-bs-theme-value="dark"
                aria-pressed="true">
                <span
                  ><i class="icon-base ti tabler-moon-stars icon-md me-3" data-icon="moon-stars"></i>{{ __('global.theme_dark') }}</span
                >
              </button>
            </li>
            <li>
              <button
                type="button"
                class="dropdown-item align-items-center"
                data-bs-theme-value="system"
                aria-pressed="false">
                <span
                  ><i
                    class="icon-base ti tabler-device-desktop-analytics icon-md me-3"
                    data-icon="device-desktop-analytics"></i
                  >{{ __('global.theme_system') }}</span
                >
              </button>
            </li>
          </ul>
        </div>
      </div>

      <ul class="navbar-nav flex-row align-items-center ms-md-auto">

        {{-- جرس الإشعارات --}}
        @if (admin_can('notification_show'))
          @php
              $unreadNotifications = \App\Services\Notification\NotificationService::unreadCountForRequest(admin()?->id);
          @endphp
          <li class="nav-item dropdown me-2 me-xl-3" id="notificationsBell">
            <a class="nav-link dropdown-toggle hide-arrow position-relative"
               href="javascript:void(0);"
               data-bs-toggle="dropdown"
               data-bs-auto-close="outside"
               role="button"
               aria-label="{{ __('notification.title') }}"
               title="{{ __('notification.title') }}">
              <i class="fa-solid fa-bell fa-lg" aria-hidden="true"></i>
              <span class="badge rounded-pill bg-danger position-absolute top-0 start-100 translate-middle
                           {{ $unreadNotifications ? '' : 'd-none' }}"
                    id="notificationsCount">{{ $unreadNotifications }}</span>
            </a>

            <ul class="dropdown-menu dropdown-menu-end p-0 notifications-dropdown">
              <li class="dropdown-header border-bottom d-flex justify-content-between align-items-center py-3 px-3">
                <span class="fw-bold">{{ __('notification.title') }}</span>
                <button type="button" class="btn btn-sm btn-label-primary py-0 px-2" id="markAllNotificationsRead">
                  {{ __('notification.mark_all') }}
                </button>
              </li>

              <li>
                <div id="notificationsList" style="max-height: 340px; overflow-y: auto;">
                  <div class="text-center text-muted py-4">
                    <div class="spinner-border spinner-border-sm"></div>
                  </div>
                </div>
              </li>

              <li class="border-top text-center py-2">
                <a href="{{ route('admin.notifications.index') }}" class="text-primary small fw-semibold">
                  {{ __('notification.view_all') }}
                </a>
              </li>
            </ul>
          </li>
        @endif

        <li class="nav-item me-2 me-xl-3">
          <a class="btn btn-sm btn-label-primary waves-effect" href="{{ route('admin.system-guide') }}"
             title="{{ __('global.system_guide_hint') }}" aria-label="{{ __('global.system_guide') }}">
            <i class="fa-solid fa-book-open me-0 me-sm-1" aria-hidden="true"></i><span class="d-none d-sm-inline"> {{ __('global.system_guide') }}</span>
          </a>
        </li>
        <!-- User -->
        <li class="nav-item navbar-dropdown dropdown-user dropdown">
          <a
            class="nav-link dropdown-toggle hide-arrow p-0"
            href="javascript:void(0);"
            role="button"
            aria-label="{{ __('global.user_menu') }}"
            data-bs-toggle="dropdown">
            <div class="avatar avatar-online">
              <img src="{{ admin()->img }}" alt="{{ admin()->name }}" class="rounded-circle" />
            </div>
          </a>
          <ul class="dropdown-menu dropdown-menu-end">
            <li>
              <a class="dropdown-item" @if (admin_can('admin_profile_update')) href="{{ route('admin.profile') }}" @else href="#" @endif>
                <div class="d-flex">
                  <div class="flex-shrink-0 me-3">
                    <div class="avatar avatar-online">
                      <img src="{{ asset(admin()->img) }}" alt="{{ admin()->name }}" class="w-px-40 h-auto rounded-circle" />
                    </div>
                  </div>
                  <div class="flex-grow-1">
                    <h6 class="mb-0">{{ admin()->name }}</h6>
                    <small class="text-body-secondary">{{ admin()->role_name }}</small>
                  </div>
                </div>
              </a>
            </li>
            <li>
              <div class="dropdown-divider my-1 mx-n2"></div>
            </li>
            @if (admin_can('admin_profile_show'))
              <li>
                <a class="dropdown-item" href="{{ route('admin.profile') }}">
                  <i class="icon-base ti tabler-user icon-md me-3"></i>
                  <span>{{ __('admin.profile') }}</span>
                </a>
              </li>
            @endif
            {{-- <li>
              <a class="dropdown-item" href="#">
                <i class="icon-base ti tabler-settings icon-md me-3"></i><span>{{ __('admin.settings') }}</span>
              </a>
            </li> --}}
            <li>
              <div class="dropdown-divider my-1 mx-n2"></div>
            </li>
            <li>
              <form action="{{ route('admin.logout') }}" method="POST" class="m-0">
                @csrf
                <a class="dropdown-item" href="#" onclick="event.preventDefault(); this.closest('form').submit();">
                  <i class="icon-base ti tabler-power icon-md me-3"></i><span>{{ __('auth.logout') }}</span>
                </a>
              </form>
            </li>
          </ul>
        </li>
      </ul>
    </div>
  </nav>