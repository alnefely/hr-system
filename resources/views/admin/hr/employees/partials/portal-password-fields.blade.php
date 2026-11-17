{{-- كلمة مرور البوابة — مشتركة بين التفعيل وإعادة التعيين (من غير تأكيد ولا شروط صعبة) --}}
<div class="mb-1">
    <label class="form-label" for="{{ $prefix }}_portal_password">{{ __('portal.admin.password') }} <span class="text-danger">*</span></label>
    <div class="input-group input-group-merge form-password-toggle">
        <input type="password" class="form-control" id="{{ $prefix }}_portal_password" name="{{ $name ?? 'password' }}"
               minlength="{{ \App\Models\Employee::PORTAL_PASSWORD_MIN }}" autocomplete="new-password" @if ($required ?? true) required @endif>
        <span class="input-group-text cursor-pointer"><i class="icon-base ti tabler-eye-off"></i></span>
    </div>
    <div class="form-text">{{ __('portal.password.rules') }}</div>
</div>
