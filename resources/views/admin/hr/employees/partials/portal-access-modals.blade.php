{{--
    نوافذ دخول بوابة الموظف (تفعيل / إعادة تعيين / إيقاف).

    لازم تتحط برّه التبويبات زي باقي نوافذ الصفحة: جوّه .tab-pane الخلفية
    المعتمة بتغطي النافذة نفسها والصفحة كلها بتقف ومحدش يقدر يضغط حاجة.
--}}
@php($canManage = admin_can('employee_portal_manage'))
@if ($canManage)
    {{-- تفعيل / إعادة تفعيل --}}
    <div class="modal fade" id="portalEnableModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form class="modal-content" method="POST" action="{{ route('admin.employees.portal.enable', $employee->id) }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('portal.admin.enable') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="small text-muted">{{ __('portal.admin.enable_hint') }}</p>
                    @include('admin.hr.employees.partials.portal-password-fields', ['prefix' => 'enable'])
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">{{ __('global.cancel') }}</button>
                    <button type="submit" class="btn btn-success">{{ __('portal.admin.enable') }}</button>
                </div>
            </form>
        </div>
    </div>

    {{-- إعادة تعيين كلمة المرور --}}
    <div class="modal fade" id="portalResetModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form class="modal-content" method="POST" action="{{ route('admin.employees.portal.reset-password', $employee->id) }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('portal.admin.reset') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="small text-muted">{{ __('portal.admin.reset_hint') }}</p>
                    @include('admin.hr.employees.partials.portal-password-fields', ['prefix' => 'reset'])
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">{{ __('global.cancel') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('portal.admin.reset') }}</button>
                </div>
            </form>
        </div>
    </div>

    {{-- إيقاف بسبب --}}
    <div class="modal fade" id="portalDisableModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form class="modal-content" method="POST" action="{{ route('admin.employees.portal.disable', $employee->id) }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('portal.admin.disable') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label" for="portal_disable_reason">{{ __('portal.admin.disable_reason') }} <span class="text-danger">*</span></label>
                    <textarea class="form-control" id="portal_disable_reason" name="reason" rows="3" minlength="3" maxlength="1000" required>{{ old('reason') }}</textarea>
                    <div class="form-text">{{ __('portal.admin.disable_hint') }}</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">{{ __('global.cancel') }}</button>
                    <button type="submit" class="btn btn-danger">{{ __('portal.admin.disable') }}</button>
                </div>
            </form>
        </div>
    </div>
@endif
