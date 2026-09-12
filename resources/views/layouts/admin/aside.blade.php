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

        @if (admin_can('notification_show'))
            <li class="menu-item {{ request()->is('admin/notifications*') ? 'active' : '' }}">
                <a href="{{ route('admin.notifications.index') }}" class="menu-link">
                    <i class="fa-solid fa-bell"></i>
                    <div>{{ __('notification.title') }}</div>
                </a>
            </li>
        @endif

        @if (admin_can('activity_log_show') || admin_can('activity_log_show_all'))
            <li class="menu-item {{ request()->is('admin/activity-logs*') ? 'active' : '' }}">
                <a href="{{ admin_can('activity_log_show_all') ? route('admin.activity-logs.index') : route('admin.activity-logs.subject', array_key_first(config('activity.subjects', []))) }}" class="menu-link">
                    <i class="fa-solid fa-shield-halved"></i>
                    <div>{{ __('activity.title') }}</div>
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

        @if (admin_can('branch_show') || admin_can('job_title_show') || admin_can('employee_show') || admin_can('leave_show') || admin_can('permission_show') || admin_can('penalty_show') || admin_can('reward_show') || admin_can('advance_show'))
            <li class="menu-item {{ request()->is('admin/branches*') || request()->is('admin/job-titles*') || request()->is('admin/employees*') || request()->is('admin/leaves*') || request()->is('admin/permissions*') || request()->is('admin/penalties*') || request()->is('admin/rewards*') || request()->is('admin/advances*') ? 'active open' : '' }}">
                <a href="javascript:void(0);" class="menu-link menu-toggle">
                    <i class="fa-solid fa-id-card"></i>
                    <div>{{ __('global.hr') }}</div>
                </a>
                <ul class="menu-sub">
                    @if (admin_can('employee_show'))
                        <li class="menu-item {{ request()->is('admin/employees*') ? 'active' : '' }}">
                            <a href="{{ route('admin.employees') }}" class="menu-link">
                                <div>{{ __('employee.title') }}</div>
                            </a>
                        </li>
                    @endif
                    @if (admin_can('branch_show'))
                        <li class="menu-item {{ request()->is('admin/branches*') ? 'active' : '' }}">
                            <a href="{{ route('admin.branches') }}" class="menu-link">
                                <div>{{ __('branch.title') }}</div>
                            </a>
                        </li>
                    @endif
                    @if (admin_can('job_title_show'))
                        <li class="menu-item {{ request()->is('admin/job-titles*') ? 'active' : '' }}">
                            <a href="{{ route('admin.job_titles') }}" class="menu-link">
                                <div>{{ __('job_title.title') }}</div>
                            </a>
                        </li>
                    @endif
                    @if (admin_can('leave_show'))
                        <li class="menu-item {{ request()->is('admin/leaves') ? 'active' : '' }}">
                            <a href="{{ route('admin.leaves.index') }}" class="menu-link">
                                <div>{{ __('leave.title') }}</div>
                            </a>
                        </li>
                    @endif
                    @if (admin_can('leave_balance_manage'))
                        <li class="menu-item {{ request()->is('admin/leaves/balances') ? 'active' : '' }}">
                            <a href="{{ route('admin.leaves.balances') }}" class="menu-link">
                                <div>{{ __('leave.balances_title') }}</div>
                            </a>
                        </li>
                    @endif
                    @if (admin_can('permission_show'))
                        <li class="menu-item {{ request()->is('admin/permissions*') ? 'active' : '' }}">
                            <a href="{{ route('admin.permissions.index') }}" class="menu-link">
                                <div>{{ __('permission.title') }}</div>
                            </a>
                        </li>
                    @endif
                    @if (admin_can('penalty_show'))
                        <li class="menu-item {{ request()->is('admin/penalties*') ? 'active' : '' }}">
                            <a href="{{ route('admin.penalties.index') }}" class="menu-link">
                                <div>{{ __('penalty.title') }}</div>
                            </a>
                        </li>
                    @endif
                    @if (admin_can('reward_show'))
                        <li class="menu-item {{ request()->is('admin/rewards*') ? 'active' : '' }}">
                            <a href="{{ route('admin.rewards.index') }}" class="menu-link">
                                <div>{{ __('reward.title') }}</div>
                            </a>
                        </li>
                    @endif
                    @if (admin_can('advance_show'))
                        <li class="menu-item {{ request()->is('admin/advances*') ? 'active' : '' }}">
                            <a href="{{ route('admin.advances.index') }}" class="menu-link">
                                <div>{{ __('advance.title') }}</div>
                            </a>
                        </li>
                    @endif
                </ul>
            </li>
        @endif

        @if (admin_can('payroll_show'))
            <li class="menu-item {{ request()->is('admin/payrolls*') ? 'active' : '' }}">
                <a href="{{ route('admin.payrolls.index') }}" class="menu-link">
                    <i class="fa-solid fa-money-bill-wave"></i>
                    <div>{{ __('payroll.title') }}</div>
                </a>
            </li>
        @endif

        @if (admin_can('expense_show') || admin_can('expense_category_show'))
            <li class="menu-item {{ request()->is('admin/expenses*') || request()->is('admin/expense-categories*') ? 'active open' : '' }}">
                <a href="javascript:void(0);" class="menu-link menu-toggle">
                    <i class="fa-solid fa-file-invoice-dollar"></i>
                    <div>{{ __('expense.title') }}</div>
                </a>
                <ul class="menu-sub">
                    @if (admin_can('expense_show'))
                        <li class="menu-item {{ request()->is('admin/expenses*') ? 'active' : '' }}">
                            <a href="{{ route('admin.expenses.index') }}" class="menu-link">
                                <div>{{ __('expense.title') }}</div>
                            </a>
                        </li>
                    @endif
                    @if (admin_can('expense_category_show'))
                        <li class="menu-item {{ request()->is('admin/expense-categories*') ? 'active' : '' }}">
                            <a href="{{ route('admin.expense-categories.index') }}" class="menu-link">
                                <div>{{ __('expense_category.title') }}</div>
                            </a>
                        </li>
                    @endif
                </ul>
            </li>
        @endif

        @if (admin_can('vendor_show') || admin_can('vendor_subscription_show'))
            <li class="menu-item {{ request()->is('admin/vendors*') || request()->is('admin/vendor-subscriptions*') ? 'active open' : '' }}">
                <a href="javascript:void(0);" class="menu-link menu-toggle">
                    <i class="fa-solid fa-handshake"></i>
                    <div>{{ __('vendor_subscription.title') }}</div>
                </a>
                <ul class="menu-sub">
                    @if (admin_can('vendor_subscription_show'))
                        <li class="menu-item {{ request()->is('admin/vendor-subscriptions*') ? 'active' : '' }}">
                            <a href="{{ route('admin.vendor-subscriptions.index') }}" class="menu-link">
                                <div>{{ __('vendor_subscription.title') }}</div>
                            </a>
                        </li>
                    @endif
                    @if (admin_can('vendor_show'))
                        <li class="menu-item {{ request()->is('admin/vendors*') ? 'active' : '' }}">
                            <a href="{{ route('admin.vendors.index') }}" class="menu-link">
                                <div>{{ __('vendor.title') }}</div>
                            </a>
                        </li>
                    @endif
                </ul>
            </li>
        @endif

        @if (admin_can('sale_show') || admin_can('customer_show') || admin_can('sales_channel_show') || admin_can('customer_source_show'))
            <li class="menu-item {{ request()->is('admin/sales*') || request()->is('admin/customers*') || request()->is('admin/sales-channels*') || request()->is('admin/customer-sources*') ? 'active open' : '' }}">
                <a href="javascript:void(0);" class="menu-link menu-toggle">
                    <i class="fa-solid fa-cart-shopping"></i>
                    <div>{{ __('sale.title') }}</div>
                </a>
                <ul class="menu-sub">
                    @if (admin_can('sale_show'))
                        <li class="menu-item {{ (request()->is('admin/sales') || request()->is('admin/sales/*')) ? 'active' : '' }}">
                            <a href="{{ route('admin.sales.index') }}" class="menu-link">
                                <div>{{ __('sale.title') }}</div>
                            </a>
                        </li>
                    @endif
                    @if (admin_can('sales_channel_show'))
                        <li class="menu-item {{ request()->is('admin/sales-channels*') ? 'active' : '' }}">
                            <a href="{{ route('admin.sales-channels.index') }}" class="menu-link">
                                <div>{{ __('sales_channel.title') }}</div>
                            </a>
                        </li>
                    @endif
                    @if (admin_can('customer_show'))
                        <li class="menu-item {{ request()->is('admin/customers*') ? 'active' : '' }}">
                            <a href="{{ route('admin.customers.index') }}" class="menu-link">
                                <div>{{ __('customer.title') }}</div>
                            </a>
                        </li>
                    @endif
                    @if (admin_can('customer_source_show'))
                        <li class="menu-item {{ request()->is('admin/customer-sources*') ? 'active' : '' }}">
                            <a href="{{ route('admin.customer-sources.index') }}" class="menu-link">
                                <div>{{ __('customer_source.title') }}</div>
                            </a>
                        </li>
                    @endif
                </ul>
            </li>
        @endif

        @if (admin_can('report_employee_statement') || admin_can('report_salary_audit') || admin_can('report_financial_pl') || admin_can('report_documents'))
            <li class="menu-item {{ request()->is('admin/reports*') ? 'active open' : '' }}">
                <a href="javascript:void(0);" class="menu-link menu-toggle">
                    <i class="fa-solid fa-chart-line"></i>
                    <div>{{ __('report.title') }}</div>
                </a>
                <ul class="menu-sub">
                    @if (admin_can('report_financial_pl'))
                        <li class="menu-item {{ request()->is('admin/reports/financial-pl*') ? 'active' : '' }}">
                            <a href="{{ route('admin.reports.financial-pl') }}" class="menu-link">
                                <div>{{ __('report.financial_pl') }}</div>
                            </a>
                        </li>
                    @endif
                    @if (admin_can('report_salary_audit'))
                        <li class="menu-item {{ request()->is('admin/reports/salary-audit*') ? 'active' : '' }}">
                            <a href="{{ route('admin.reports.salary-audit') }}" class="menu-link">
                                <div>{{ __('report.salary_audit') }}</div>
                            </a>
                        </li>
                    @endif
                    @if (admin_can('report_employee_statement'))
                        <li class="menu-item {{ request()->is('admin/reports/employee-statement*') ? 'active' : '' }}">
                            <a href="{{ route('admin.reports.employee-statement') }}" class="menu-link">
                                <div>{{ __('report.employee_statement') }}</div>
                            </a>
                        </li>
                    @endif
                    @if (admin_can('report_documents'))
                        <li class="menu-item {{ request()->is('admin/reports/documents*') ? 'active' : '' }}">
                            <a href="{{ route('admin.reports.documents') }}" class="menu-link">
                                <div>{{ __('report.documents') }}</div>
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

        <li class="menu-item {{ request()->is('admin/system-guide*') ? 'active' : '' }}">
            <a href="{{ route('admin.system-guide') }}" class="menu-link">
                <i class="fa-solid fa-book-open text-primary"></i>
                <div class="fw-bold">دليل استخدام النظام</div>
            </a>
        </li>

        <li class="menu-item">
            <a href="{{ route('admin.logout') }}" class="menu-link">
                <i class="fa-solid fa-right-from-bracket"></i>
                <div>{{ __('auth.logout') }}</div>
            </a>
        </li>
    </ul>
</aside>