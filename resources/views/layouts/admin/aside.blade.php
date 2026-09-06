<aside id="layout-menu" class="layout-menu menu-vertical menu">
    <div class="app-brand demo ">
        <a href="{{ url('/') }}" target="_blank" class="app-brand-link">
            <span class="app-brand-logo demo">
                <img src="{{ setting('site_logo') }}" alt="Logo" class="w-px-40 h-auto rounded-circle">
            </span>
            <span class="app-brand-text demo menu-text fw-bold ms-3">
                {{ setting('site_name') }}
            </span>
        </a>
        <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto">
            <i class="icon-base ti menu-toggle-icon d-none d-xl-block"></i>
            <i class="icon-base ti tabler-x d-block d-xl-none"></i>
        </a>
    </div>
    <div class="menu-inner-shadow"></div>
    <ul class="menu-inner py-1">
        
        @if (admin_can('dashboard_show'))
            <li class="menu-item">
                <a href="{{ route('admin.home') }}" class="menu-link">
                    <i class="fa-solid fa-house"></i>
                    <div>{{__('dashboard.title')}}</div>
                </a>
            </li>
        @endif

        @if (admin_can('setting_show') || admin_can('setting_clear_cache'))
            <li class="menu-item">
                <a href="javascript:void(0);" class="menu-link menu-toggle">
                    <i class="fa-solid fa-gear"></i>
                    <div>{{__('setting.title')}}</div>
                </a>
                <ul class="menu-sub">
                    {{-- @foreach (App\Models\Settings::getAllGroups() as $group) --}}
                    @foreach (App\Models\Settings::getAllGroups() as $group)
                        <li class="menu-item">
                            <a href="{{ route('admin.settings').'?group=' . $group }}" class="menu-link">
                                <div>{{ __('setting.groups.' . $group) }}</div>
                            </a>
                        </li>
                    @endforeach
                    @if (admin_can('setting_clear_cache'))
                        <li class="menu-item">
                            <a href="{{ route('admin.settings.clearCache') }}" class="menu-link">
                                <div>{{__('setting.clear_cache')}}</div>
                            </a>
                        </li>
                    @endif
                </ul>
            </li>
        @endif

        @if (admin_can('role_show') || admin_can('admin_show') || admin_can('setting_clear_cache'))
            <li class="menu-item">
                <a href="javascript:void(0);" class="menu-link menu-toggle">
                    <i class="fa-solid fa-users"></i>
                    <div>{{__('admin.admins_and_roles')}}</div>
                </a>
                <ul class="menu-sub">
                    @if (admin_can('role_show'))
                        <li class="menu-item">
                            <a href="{{ route('admin.roles') }}" class="menu-link">
                                <div>{{__('role.title')}}</div>
                            </a>
                        </li>
                    @endif
                    @if (admin_can('admin_show'))
                        <li class="menu-item">
                            <a href="{{ route('admin.admins') }}" class="menu-link">
                                <div>{{__('admin.title')}}</div>
                            </a>
                        </li>
                    @endif
                </ul>
            </li>
        @endif

        @if (admin_can('admin_profile_show'))         
            <li class="menu-item">
                <a href="{{ route('admin.profile') }}" class="menu-link">
                    <i class="fa-solid fa-user"></i>
                    <div>{{ __('admin.profile') }}</div>
                </a>
            </li>
        @endif

        <li class="menu-item">
            <a href="{{ route('admin.logout') }}" class="menu-link">
                <i class="fa-solid fa-right-from-bracket"></i>
                <div>{{ __('auth.logout') }}</div>
            </a>
        </li>
    </ul>
</aside>