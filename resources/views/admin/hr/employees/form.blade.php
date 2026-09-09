@php
    $isEdit = isset($employee) && $employee;
@endphp

{{-- 1. البيانات الشخصية --}}
<div class="divider divider-primary text-start my-4">
    <div class="divider-text fw-bold fs-6">
        <i class="fa-solid fa-user me-2"></i> {{ __('employee.sections.personal') }}
    </div>
</div>

<div class="row">
    <div class="col-md-4 mb-3">
        <label for="name" class="form-label">
            {{ __('employee.fields.name') }} <span class="text-danger">*</span>
            <i class="fa-solid fa-circle-question text-muted ms-1 cursor-pointer" data-bs-toggle="tooltip" data-bs-placement="top" title="{{ __('employee.tooltips.name') }}"></i>
        </label>
        <input type="text" value="{{ old('name', $isEdit ? $employee->name : '') }}" 
               class="form-control @error('name') is-invalid @enderror" 
               id="name" name="name" required="required">
        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-4 mb-3">
        <label for="national_id" class="form-label">
            {{ __('employee.fields.national_id') }}
            <i class="fa-solid fa-circle-question text-muted ms-1 cursor-pointer" data-bs-toggle="tooltip" data-bs-placement="top" title="{{ __('employee.tooltips.national_id') }}"></i>
        </label>
        <input type="text" value="{{ old('national_id', $isEdit ? $employee->national_id : '') }}" 
               class="form-control @error('national_id') is-invalid @enderror" 
               id="national_id" name="national_id" dir="ltr">
        @error('national_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-4 mb-3">
        <label for="birth_date" class="form-label">
            {{ __('employee.fields.birth_date') }}
            <i class="fa-solid fa-circle-question text-muted ms-1 cursor-pointer" data-bs-toggle="tooltip" data-bs-placement="top" title="{{ __('employee.tooltips.birth_date') }}"></i>
        </label>
        <input type="date" value="{{ old('birth_date', $isEdit && $employee->birth_date ? $employee->birth_date->format('Y-m-d') : '') }}" 
               class="form-control @error('birth_date') is-invalid @enderror" 
               id="birth_date" name="birth_date">
        @error('birth_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-4 mb-4">
        <label class="form-label">
            {{ __('employee.fields.gender') }}
            <i class="fa-solid fa-circle-question text-muted ms-1 cursor-pointer" data-bs-toggle="tooltip" data-bs-placement="top" title="{{ __('employee.tooltips.gender') }}"></i>
        </label>
        <select name="gender" class="form-select select2 @error('gender') is-invalid @enderror">
            <option value="">{{ __('employee.select_gender') }}</option>
            @foreach (__('employee.genders') as $key => $lbl)
                <option value="{{ $key }}" @selected(old('gender', $isEdit ? $employee->gender : 'male') == $key)>{{ $lbl }}</option>
            @endforeach
        </select>
        @error('gender')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-4 mb-4">
        <label class="form-label">
            {{ __('employee.fields.marital_status') }}
            <i class="fa-solid fa-circle-question text-muted ms-1 cursor-pointer" data-bs-toggle="tooltip" data-bs-placement="top" title="{{ __('employee.tooltips.marital_status') }}"></i>
        </label>
        <select name="marital_status" class="form-select select2 @error('marital_status') is-invalid @enderror">
            <option value="">{{ __('employee.select_marital') }}</option>
            @foreach (__('employee.marital_statuses') as $key => $lbl)
                <option value="{{ $key }}" @selected(old('marital_status', $isEdit ? $employee->marital_status : '') == $key)>{{ $lbl }}</option>
            @endforeach
        </select>
        @error('marital_status')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-4 mb-3">
        <label for="nationality" class="form-label">
            {{ __('employee.fields.nationality') }}
            <i class="fa-solid fa-circle-question text-muted ms-1 cursor-pointer" data-bs-toggle="tooltip" data-bs-placement="top" title="{{ __('employee.tooltips.nationality') }}"></i>
        </label>
        <input type="text" value="{{ old('nationality', $isEdit ? $employee->nationality : '') }}" 
               class="form-control @error('nationality') is-invalid @enderror" 
               id="nationality" name="nationality">
        @error('nationality')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-4 mb-3">
        <x-inputs.lfm
            name="img"
            type="image"
            :label="__('employee.fields.avatar')"
            :value="old('img', $isEdit ? $employee->avatar : null)"
        />
    </div>
</div>

{{-- 2. بيانات التواصل --}}
<div class="divider divider-primary text-start my-4">
    <div class="divider-text fw-bold fs-6">
        <i class="fa-solid fa-address-book me-2"></i> {{ __('employee.sections.contact') }}
    </div>
</div>

<div class="row">
    <x-inputs.phone
        name="phone"
        :label="__('employee.fields.phone')"
        :value="$isEdit ? $employee->phone : null"
        col="4"
        required
    />

    <x-inputs.phone
        name="alt_phone"
        :label="__('employee.fields.alt_phone')"
        :value="$isEdit ? $employee->alt_phone : null"
        col="4"
    />

    <div class="col-md-4 mb-3">
        <label for="email" class="form-label">
            {{ __('employee.fields.personal_email') }}
            <i class="fa-solid fa-circle-question text-muted ms-1 cursor-pointer" data-bs-toggle="tooltip" data-bs-placement="top" title="{{ __('employee.tooltips.personal_email') }}"></i>
        </label>
        <input type="email" value="{{ old('email', $isEdit ? $employee->personal_email : '') }}" 
               class="form-control @error('email') is-invalid @enderror" 
               id="email" name="email" dir="ltr">
        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-4 mb-3">
        <label for="emergency_contact_name" class="form-label">
            {{ __('employee.fields.emergency_contact_name') }}
            <i class="fa-solid fa-circle-question text-muted ms-1 cursor-pointer" data-bs-toggle="tooltip" data-bs-placement="top" title="{{ __('employee.tooltips.emergency_contact_name') }}"></i>
        </label>
        <input type="text" value="{{ old('emergency_contact_name', $isEdit ? $employee->emergency_contact_name : '') }}" 
               class="form-control @error('emergency_contact_name') is-invalid @enderror" 
               id="emergency_contact_name" name="emergency_contact_name">
        @error('emergency_contact_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <x-inputs.phone
        name="emergency_contact_phone"
        :label="__('employee.fields.emergency_contact_phone')"
        :value="$isEdit ? $employee->emergency_contact_phone : null"
        col="4"
    />

    <div class="col-md-12 mb-3">
        <label for="address" class="form-label">
            {{ __('employee.fields.address') }}
            <i class="fa-solid fa-circle-question text-muted ms-1 cursor-pointer" data-bs-toggle="tooltip" data-bs-placement="top" title="{{ __('employee.tooltips.address') }}"></i>
        </label>
        <textarea rows="2" class="form-control @error('address') is-invalid @enderror" 
                  id="address" name="address">{{ old('address', $isEdit ? $employee->address : '') }}</textarea>
        @error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>

{{-- 3. البيانات الوظيفية --}}
<div class="divider divider-primary text-start my-4">
    <div class="divider-text fw-bold fs-6">
        <i class="fa-solid fa-briefcase me-2"></i> {{ __('employee.sections.job') }}
    </div>
</div>

<div class="row">
    <div class="col-md-4 mb-3">
        <label for="code" class="form-label">
            {{ __('employee.fields.code') }}
            <i class="fa-solid fa-circle-question text-muted ms-1 cursor-pointer" data-bs-toggle="tooltip" data-bs-placement="top" title="{{ __('employee.tooltips.code') }}"></i>
        </label>
        <input type="text" value="{{ old('code', $isEdit ? $employee->employee_code : ($nextCode ?? '')) }}" 
               class="form-control @error('code') is-invalid @enderror" 
               id="code" name="code" dir="ltr">
        <div class="form-text text-muted">{{ __('employee.fields.code_hint') }}</div>
        @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-4 mb-4">
        <label class="form-label">
            {{ __('employee.fields.branch') }} <span class="text-danger">*</span>
            <i class="fa-solid fa-circle-question text-muted ms-1 cursor-pointer" data-bs-toggle="tooltip" data-bs-placement="top" title="{{ __('employee.tooltips.branch') }}"></i>
        </label>
        <select name="branch_id" class="form-select select2 @error('branch_id') is-invalid @enderror" required>
            <option value="">{{ __('employee.select_branch') }}</option>
            @foreach ($branches as $branch)
                <option value="{{ $branch->id }}" @selected(old('branch_id', $isEdit ? $employee->branch_id : '') == $branch->id)>
                    {{ $branch->name }}
                </option>
            @endforeach
        </select>
        @error('branch_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-4 mb-4">
        <label class="form-label">
            {{ __('employee.fields.job_title') }} <span class="text-danger">*</span>
            <i class="fa-solid fa-circle-question text-muted ms-1 cursor-pointer" data-bs-toggle="tooltip" data-bs-placement="top" title="{{ __('employee.tooltips.job_title') }}"></i>
        </label>
        <select name="job_title_id" class="form-select select2 @error('job_title_id') is-invalid @enderror" required>
            <option value="">{{ __('employee.select_job') }}</option>
            @foreach ($jobTitles as $job)
                <option value="{{ $job->id }}" @selected(old('job_title_id', $isEdit ? $employee->job_title_id : '') == $job->id)>
                    {{ $job->name }}
                </option>
            @endforeach
        </select>
        @error('job_title_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-4 mb-4">
        <label class="form-label">
            {{ __('employee.fields.manager') }}
            <i class="fa-solid fa-circle-question text-muted ms-1 cursor-pointer" data-bs-toggle="tooltip" data-bs-placement="top" title="{{ __('employee.tooltips.manager') }}"></i>
        </label>
        <select name="manager_id" class="form-select select2 @error('manager_id') is-invalid @enderror">
            <option value="">{{ __('employee.select_manager') }}</option>
            @foreach ($managers as $mgr)
                <option value="{{ $mgr->id }}" @selected(old('manager_id', $isEdit ? $employee->manager_id : '') == $mgr->id)>
                    {{ $mgr->name }}
                </option>
            @endforeach
        </select>
        @error('manager_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-4 mb-3">
        <label for="hired_at" class="form-label">
            {{ __('employee.fields.hired_at') }} <span class="text-danger">*</span>
            <i class="fa-solid fa-circle-question text-muted ms-1 cursor-pointer" data-bs-toggle="tooltip" data-bs-placement="top" title="{{ __('employee.tooltips.hired_at') }}"></i>
        </label>
        <input type="date" value="{{ old('hired_at', $isEdit && $employee->hire_date ? $employee->hire_date->format('Y-m-d') : date('Y-m-d')) }}" 
               class="form-control @error('hired_at') is-invalid @enderror" 
               id="hired_at" name="hired_at" required="required">
        @error('hired_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-4 mb-3">
        <label for="probation_end_at" class="form-label">
            {{ __('employee.fields.probation_end_at') }}
            <i class="fa-solid fa-circle-question text-muted ms-1 cursor-pointer" data-bs-toggle="tooltip" data-bs-placement="top" title="{{ __('employee.tooltips.probation_end_at') }}"></i>
        </label>
        <input type="date" value="{{ old('probation_end_at', $isEdit && $employee->probation_end_date ? $employee->probation_end_date->format('Y-m-d') : '') }}" 
               class="form-control @error('probation_end_at') is-invalid @enderror" 
               id="probation_end_at" name="probation_end_at">
        @error('probation_end_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-4 mb-4">
        <label class="form-label">
            {{ __('employee.fields.employment_type') }}
            <i class="fa-solid fa-circle-question text-muted ms-1 cursor-pointer" data-bs-toggle="tooltip" data-bs-placement="top" title="{{ __('employee.tooltips.employment_type') }}"></i>
        </label>
        <select name="employment_type" class="form-select select2 @error('employment_type') is-invalid @enderror">
            @foreach (__('employee.contract_types') as $key => $lbl)
                <option value="{{ $key }}" @selected(old('employment_type', $isEdit ? $employee->contract_type : 'full_time') == $key)>
                    {{ $lbl }}
                </option>
            @endforeach
        </select>
        @error('employment_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-4 mb-4">
        <label class="form-label">
            {{ __('employee.fields.status') }}
            <i class="fa-solid fa-circle-question text-muted ms-1 cursor-pointer" data-bs-toggle="tooltip" data-bs-placement="top" title="{{ __('employee.tooltips.status') }}"></i>
        </label>
        <select name="status" class="form-select select2 @error('status') is-invalid @enderror">
            @foreach (__('employee.statuses') as $key => $lbl)
                <option value="{{ $key }}" @selected(old('status', $isEdit ? $employee->status : 'active') == $key)>
                    {{ $lbl }}
                </option>
            @endforeach
        </select>
        @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-12 mb-3">
        <label for="notes" class="form-label">
            {{ __('employee.fields.notes') }}
            <i class="fa-solid fa-circle-question text-muted ms-1 cursor-pointer" data-bs-toggle="tooltip" data-bs-placement="top" title="{{ __('employee.tooltips.notes') }}"></i>
        </label>
        <textarea rows="2" class="form-control @error('notes') is-invalid @enderror" 
                  id="notes" name="notes">{{ old('notes', $isEdit ? $employee->notes : '') }}</textarea>
        @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>

{{-- 4. البيانات المالية وإعدادات الراتب --}}
<div class="divider divider-primary text-start my-4">
    <div class="divider-text fw-bold fs-6">
        <i class="fa-solid fa-money-bill-wave me-2"></i> {{ __('employee.sections.financial') }}
    </div>
</div>

<div class="row">
    @if (admin_can('employee_salary_view'))
        <div class="col-md-3 mb-3">
            <label for="salary" class="form-label">
                {{ __('employee.fields.salary') }}
                <i class="fa-solid fa-circle-question text-muted ms-1 cursor-pointer" data-bs-toggle="tooltip" data-bs-placement="top" title="{{ __('employee.tooltips.salary') }}"></i>
            </label>
            <input type="number" step="0.01" 
                   value="{{ old('salary', $isEdit ? $employee->basic_salary : '') }}" 
                   class="form-control @error('salary') is-invalid @enderror" 
                   id="salary" name="salary" dir="ltr">
            @error('salary')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    @endif

    @if (admin_can('employee_bank_view'))
        <div class="col-md-3 mb-3">
            <label for="bank_name" class="form-label">
                {{ __('employee.fields.bank_name') }}
                <i class="fa-solid fa-circle-question text-muted ms-1 cursor-pointer" data-bs-toggle="tooltip" data-bs-placement="top" title="{{ __('employee.tooltips.bank_name') }}"></i>
            </label>
            <input type="text" value="{{ old('bank_name', $isEdit ? $employee->bank_name : '') }}" 
                   class="form-control @error('bank_name') is-invalid @enderror" 
                   id="bank_name" name="bank_name">
            @error('bank_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="col-md-3 mb-3">
            <label for="bank_account" class="form-label">
                {{ __('employee.fields.bank_account') }}
                <i class="fa-solid fa-circle-question text-muted ms-1 cursor-pointer" data-bs-toggle="tooltip" data-bs-placement="top" title="{{ __('employee.tooltips.bank_account') }}"></i>
            </label>
            <input type="text" value="{{ old('bank_account', $isEdit ? $employee->bank_account_number : '') }}" 
                   class="form-control @error('bank_account') is-invalid @enderror" 
                   id="bank_account" name="bank_account" dir="ltr">
            @error('bank_account')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="col-md-3 mb-3">
            <label for="insurance_number" class="form-label">
                {{ __('employee.fields.insurance_number') }}
                <i class="fa-solid fa-circle-question text-muted ms-1 cursor-pointer" data-bs-toggle="tooltip" data-bs-placement="top" title="{{ __('employee.tooltips.insurance_number') }}"></i>
            </label>
            <input type="text" value="{{ old('insurance_number', $isEdit ? $employee->insurance_number : '') }}" 
                   class="form-control @error('insurance_number') is-invalid @enderror" 
                   id="insurance_number" name="insurance_number" dir="ltr">
            @error('insurance_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    @endif

    {{-- إعدادات الموارد البشرية والدوام والإجازات --}}
    <div class="col-12">
        <div class="divider divider-info text-start my-4">
            <div class="divider-text fw-bold fs-6 text-info">
                <i class="fa-solid fa-sliders me-2"></i> {{ __('employee.sections.hr_settings') }}
            </div>
        </div>
    </div>

    <div class="col-md-3 mb-3">
        <label for="annual_leave_entitlement" class="form-label">
            {{ __('employee.fields.annual_leave_entitlement') }}
            <i class="fa-solid fa-circle-question text-muted ms-1 cursor-pointer" data-bs-toggle="tooltip" data-bs-placement="top" title="{{ __('employee.tooltips.annual_leave_entitlement') }}"></i>
        </label>
        <input type="number" step="0.5" 
               value="{{ old('annual_leave_entitlement', $isEdit ? $employee->annual_leave_entitlement : '21') }}" 
               class="form-control @error('annual_leave_entitlement') is-invalid @enderror" 
               id="annual_leave_entitlement" name="annual_leave_entitlement">
        @error('annual_leave_entitlement')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-3 mb-3">
        <label for="monthly_permission_hours_limit" class="form-label">
            {{ __('employee.fields.monthly_permission_hours_limit') }}
            <i class="fa-solid fa-circle-question text-muted ms-1 cursor-pointer" data-bs-toggle="tooltip" data-bs-placement="top" title="{{ __('employee.tooltips.monthly_permission_hours_limit') }}"></i>
        </label>
        <input type="number" step="0.5" 
               value="{{ old('monthly_permission_hours_limit', $isEdit ? $employee->monthly_permission_hours_limit : '4') }}" 
               class="form-control @error('monthly_permission_hours_limit') is-invalid @enderror" 
               id="monthly_permission_hours_limit" name="monthly_permission_hours_limit">
        @error('monthly_permission_hours_limit')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-2 mb-3">
        <label for="daily_work_hours" class="form-label">
            {{ __('employee.fields.daily_work_hours') }}
            <i class="fa-solid fa-circle-question text-muted ms-1 cursor-pointer" data-bs-toggle="tooltip" data-bs-placement="top" title="{{ __('employee.tooltips.daily_work_hours') }}"></i>
        </label>
        <input type="number" step="0.5" 
               value="{{ old('daily_work_hours', $isEdit ? $employee->daily_work_hours : '8') }}" 
               class="form-control @error('daily_work_hours') is-invalid @enderror" 
               id="daily_work_hours" name="daily_work_hours">
        @error('daily_work_hours')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-2 mb-3">
        <label for="custom_daily_rate" class="form-label">
            {{ __('employee.fields.custom_daily_rate') }}
            <i class="fa-solid fa-circle-question text-muted ms-1 cursor-pointer" data-bs-toggle="tooltip" data-bs-placement="top" title="{{ __('employee.tooltips.custom_daily_rate') }}"></i>
        </label>
        <input type="number" step="0.01" 
               value="{{ old('custom_daily_rate', $isEdit ? $employee->custom_daily_rate : '') }}" 
               class="form-control @error('custom_daily_rate') is-invalid @enderror" 
               id="custom_daily_rate" name="custom_daily_rate" 
               placeholder="{{ __('employee.fields.rate_auto_hint') }}" dir="ltr">
        @error('custom_daily_rate')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-2 mb-3">
        <label for="custom_hourly_rate" class="form-label">
            {{ __('employee.fields.custom_hourly_rate') }}
            <i class="fa-solid fa-circle-question text-muted ms-1 cursor-pointer" data-bs-toggle="tooltip" data-bs-placement="top" title="{{ __('employee.tooltips.custom_hourly_rate') }}"></i>
        </label>
        <input type="number" step="0.01" 
               value="{{ old('custom_hourly_rate', $isEdit ? $employee->custom_hourly_rate : '') }}" 
               class="form-control @error('custom_hourly_rate') is-invalid @enderror" 
               id="custom_hourly_rate" name="custom_hourly_rate" 
               placeholder="{{ __('employee.fields.rate_auto_hint') }}" dir="ltr">
        @error('custom_hourly_rate')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    {{-- البدلات والثوابت الشهرية --}}
    @if (admin_can('employee_salary_view'))
        <div class="col-12 mt-3">
            <div class="card border shadow-none">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="fw-bold mb-0 text-primary">
                            <i class="fa-solid fa-plus-circle me-1"></i> {{ __('employee.allowances.title') }}
                            <i class="fa-solid fa-circle-question text-muted ms-1 cursor-pointer" data-bs-toggle="tooltip" data-bs-placement="top" title="{{ __('employee.tooltips.allowances') }}"></i>
                        </h6>
                        <button type="button" class="btn btn-sm btn-outline-primary waves-effect" id="add_emp_allowance_btn">
                            <i class="fa-solid fa-plus me-1"></i> {{ __('employee.allowances.add_btn') }}
                        </button>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered mb-0" id="emp_allowances_table">
                            <thead class="table-light">
                                <tr>
                                    <th>{{ __('employee.allowances.name_header') }}</th>
                                    <th style="width: 30%">{{ __('employee.allowances.amount_header') }}</th>
                                    <th style="width: 10%" class="text-center">{{ __('employee.allowances.delete_header') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $existingAllowances = old('allowances', $isEdit ? $employee->allowances->toArray() : []);
                                @endphp
                                @forelse ($existingAllowances as $idx => $item)
                                    <tr class="allowance-row">
                                        <td>
                                            <input type="text" name="allowances[{{ $idx }}][name]" 
                                                   value="{{ $item['name'] ?? '' }}" class="form-control form-control-sm" required>
                                        </td>
                                        <td>
                                            <input type="number" step="0.01" name="allowances[{{ $idx }}][amount]" 
                                                   value="{{ $item['amount'] ?? '' }}" class="form-control form-control-sm" dir="ltr" required>
                                        </td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-sm btn-label-danger remove-allowance-row waves-effect">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    {{-- يضاف سطر عند الحاجة --}}
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

{{-- 5. حساب الدخول للنظام --}}
<div class="divider divider-primary text-start my-4">
    <div class="divider-text fw-bold fs-6">
        <i class="fa-solid fa-key me-2"></i> {{ __('employee.sections.login_account') }}
    </div>
</div>

<div class="row">
    <div class="col-md-12 mb-3">
        <div class="form-check form-switch">
            <input type="hidden" name="create_account" value="0">
            <input type="checkbox" name="create_account" id="create_account" value="1" 
                   class="form-check-input" @checked(old('create_account', $isEdit && $employee->admin_id ? 1 : 0))>
            <label class="form-check-label fw-bold" for="create_account">
                {{ __('employee.account.create_switch') }}
                <i class="fa-solid fa-circle-question text-muted ms-1 cursor-pointer" data-bs-toggle="tooltip" data-bs-placement="top" title="{{ __('employee.tooltips.create_account') }}"></i>
            </label>
        </div>
    </div>

    <div id="account_fields_container" class="row {{ old('create_account', $isEdit && $employee->admin_id ? 1 : 0) ? '' : 'd-none' }}">
        <div class="col-md-4 mb-3">
            <label for="account_email" class="form-label">
                {{ __('employee.account.email') }}
            </label>
            <input type="email" value="{{ old('account_email', $isEdit && $employee->admin ? $employee->admin->email : '') }}" 
                   class="form-control @error('account_email') is-invalid @enderror" 
                   id="account_email" name="account_email" dir="ltr">
            @error('account_email')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="col-md-4 mb-3">
            <label for="account_password" class="form-label">
                {{ __('employee.account.password') }}
            </label>
            <div class="input-group form-password-toggle">
                <input type="password" value="" class="form-control @error('account_password') is-invalid @enderror" 
                       id="account_password" name="account_password">
                <span class="input-group-text cursor-pointer toggle-password-visibility">
                    <i class="icon-base ti tabler-eye-off"></i>
                </span>
            </div>
            @error('account_password')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>

        <div class="col-md-4 mb-3">
            <label for="account_password_confirmation" class="form-label">
                {{ __('employee.account.password_confirm') }}
            </label>
            <div class="input-group form-password-toggle">
                <input type="password" value="" class="form-control" 
                       id="account_password_confirmation" name="account_password_confirmation">
                <span class="input-group-text cursor-pointer toggle-password-visibility">
                    <i class="icon-base ti tabler-eye-off"></i>
                </span>
            </div>
        </div>

        <div class="col-md-4 mb-4">
            <label class="form-label">
                {{ __('employee.account.role') }}
                <i class="fa-solid fa-circle-question text-muted ms-1 cursor-pointer" data-bs-toggle="tooltip" data-bs-placement="top" title="{{ __('employee.tooltips.role') }}"></i>
            </label>
            <select name="role_id" class="form-select select2 @error('role_id') is-invalid @enderror">
                <option value="">{{ __('employee.select_role') }}</option>
                @foreach ($roles as $role)
                    <option value="{{ $role->id }}" @selected(old('role_id', $isEdit && $employee->admin ? $employee->admin->role_id : '') == $role->id)>
                        {{ $role->name }}
                    </option>
                @endforeach
            </select>
            @error('role_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
</div>

{{-- 6. المستندات والوثائق --}}
<div class="divider divider-primary text-start my-4" id="documents_section">
    <div class="divider-text fw-bold fs-6">
        <i class="fa-solid fa-file-lines me-2"></i> {{ __('employee.sections.documents') }}
    </div>
</div>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h6 class="mb-0 fw-bold">
        <i class="fa-solid fa-folder-open me-2"></i> {{ __('employee.documents.title') }}
    </h6>
    <button type="button" class="btn btn-sm btn-outline-primary waves-effect" id="add_doc_row">
        <i class="fa-solid fa-plus me-1"></i> {{ __('employee.documents.add_btn') }}
    </button>
</div>

<div class="table-responsive mb-4">
    <table class="table table-bordered align-middle" id="documents_table">
        <thead class="table-light">
            <tr>
                <th>{{ __('employee.documents.title') }}</th>
                <th class="text-center" style="width: 60px;">{{ __('employee.documents.delete_header') }}</th>
            </tr>
        </thead>
        <tbody>
            @php
                $existingDocs = old('documents', $isEdit ? $employee->documents->toArray() : []);
            @endphp
            @forelse ($existingDocs as $dIdx => $docItem)
            <tr class="doc-row" data-index="{{ $dIdx }}">
                <td>
                    {{-- الـ id بيخلي الحفظ يحدّث المستند بدل ما يعمل نسخة جديدة --}}
                    <input type="hidden" name="documents[{{ $dIdx }}][id]" value="{{ $docItem['id'] ?? '' }}">
                    <div class="row g-2">

                        {{-- الصف الأول: النوع والوصف --}}
                        <div class="col-md-4">
                            <label class="form-label small text-muted mb-1">
                                {{ __('document.fields.document_type') }}
                            </label>
                            <select name="documents[{{ $dIdx }}][document_type]" class="form-select select2">
                                @foreach (__('document.types') as $typeKey => $typeLabel)
                                    <option value="{{ $typeKey }}" @selected(($docItem['document_type'] ?? 'other') === $typeKey)>{{ $typeLabel }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-8">
                            <label class="form-label small text-muted mb-1">{{ __('document.fields.title') }}</label>
                            <input type="text" name="documents[{{ $dIdx }}][title]"
                                   value="{{ $docItem['title'] ?? '' }}" class="form-control"
                                   placeholder="{{ __('employee.documents.name_placeholder') }}">
                        </div>

                        {{-- الصف التاني: الملف --}}
                        <div class="col-12">
                            <label class="form-label small text-muted mb-1">
                                {{ __('document.fields.file') }} <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <input type="text" name="documents[{{ $dIdx }}][file_path]" id="doc_file_{{ $dIdx }}"
                                       value="{{ $docItem['file_path'] ?? '' }}" class="form-control"
                                       placeholder="{{ __('employee.documents.path_placeholder') }}">
                                <button type="button" class="btn btn-file-manager waves-effect waves-light"
                                        data-input="doc_file_{{ $dIdx }}" data-preview="doc_preview_{{ $dIdx }}">
                                    <i class="fas fa-folder-open me-1"></i> {{ __('employee.documents.choose_file') }}
                                </button>
                                <button type="button" class="btn btn-clear-file waves-effect waves-light"
                                        onclick="clearFileInput('doc_file_{{ $dIdx }}', 'doc_preview_{{ $dIdx }}')">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </div>
                            <div id="doc_preview_{{ $dIdx }}" class="file-preview-container mt-1">
                                @if (!empty($docItem['file_path']))
                                    <a href="{{ $docItem['file_path'] }}" target="_blank" class="badge bg-label-info mt-1">
                                        <i class="fa-solid fa-link me-1"></i> {{ basename($docItem['file_path']) }}
                                    </a>
                                @endif
                            </div>
                        </div>

                        {{-- الصف التالت: التواريخ والاعتماد --}}
                        <div class="col-md-4">
                            <label class="form-label small text-muted mb-1">{{ __('document.fields.issue_date') }}</label>
                            <input type="date" name="documents[{{ $dIdx }}][issue_date]"
                                   value="{{ !empty($docItem['issue_date']) ? \Illuminate\Support\Str::substr($docItem['issue_date'], 0, 10) : '' }}" class="form-control" dir="ltr">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small text-muted mb-1">
                                {{ __('document.fields.expiry_date') }}
                                <i class="fa-solid fa-circle-info text-warning"
                                   data-bs-toggle="tooltip"
                                   title="{{ __('document.hints.expiry') }}"></i>
                            </label>
                            <input type="date" name="documents[{{ $dIdx }}][expiry_date]"
                                   value="{{ !empty($docItem['expiry_date']) ? \Illuminate\Support\Str::substr($docItem['expiry_date'], 0, 10) : '' }}" class="form-control" dir="ltr">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small text-muted mb-1">{{ __('document.fields.is_verified') }}</label>
                            <select name="documents[{{ $dIdx }}][is_verified]" class="form-select select2">
                                <option value="0" @selected(!!empty($docItem['is_verified']))>{{ __('global.no') }}</option>
                                <option value="1" @selected(!empty($docItem['is_verified']))>{{ __('global.yes') }}</option>
                            </select>
                        </div>

                    </div>
                </td>

                <td class="text-center align-middle" style="width: 60px;">
                    <button type="button" class="btn btn-sm btn-label-danger remove-doc-row waves-effect"
                            title="{{ __('employee.documents.delete_header') }}">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </td>
            </tr>
            @empty
            <tr class="doc-row" data-index="0">
                <td>
                    <div class="row g-2">

                        {{-- الصف الأول: النوع والوصف --}}
                        <div class="col-md-4">
                            <label class="form-label small text-muted mb-1">
                                {{ __('document.fields.document_type') }}
                            </label>
                            <select name="documents[0][document_type]" class="form-select select2">
                                @foreach (__('document.types') as $typeKey => $typeLabel)
                                    <option value="{{ $typeKey }}" @selected($typeKey === 'other')>{{ $typeLabel }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-8">
                            <label class="form-label small text-muted mb-1">{{ __('document.fields.title') }}</label>
                            <input type="text" name="documents[0][title]"
                                   value="" class="form-control"
                                   placeholder="{{ __('employee.documents.name_placeholder') }}">
                        </div>

                        {{-- الصف التاني: الملف --}}
                        <div class="col-12">
                            <label class="form-label small text-muted mb-1">
                                {{ __('document.fields.file') }} <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <input type="text" name="documents[0][file_path]" id="doc_file_0"
                                       value="" class="form-control"
                                       placeholder="{{ __('employee.documents.path_placeholder') }}">
                                <button type="button" class="btn btn-file-manager waves-effect waves-light"
                                        data-input="doc_file_0" data-preview="doc_preview_0">
                                    <i class="fas fa-folder-open me-1"></i> {{ __('employee.documents.choose_file') }}
                                </button>
                                <button type="button" class="btn btn-clear-file waves-effect waves-light"
                                        onclick="clearFileInput('doc_file_0', 'doc_preview_0')">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </div>
                            <div id="doc_preview_0" class="file-preview-container mt-1"></div>
                        </div>

                        {{-- الصف التالت: التواريخ والاعتماد --}}
                        <div class="col-md-4">
                            <label class="form-label small text-muted mb-1">{{ __('document.fields.issue_date') }}</label>
                            <input type="date" name="documents[0][issue_date]"
                                   value="" class="form-control" dir="ltr">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small text-muted mb-1">
                                {{ __('document.fields.expiry_date') }}
                                <i class="fa-solid fa-circle-info text-warning"
                                   data-bs-toggle="tooltip"
                                   title="{{ __('document.hints.expiry') }}"></i>
                            </label>
                            <input type="date" name="documents[0][expiry_date]"
                                   value="" class="form-control" dir="ltr">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small text-muted mb-1">{{ __('document.fields.is_verified') }}</label>
                            <select name="documents[0][is_verified]" class="form-select select2">
                                <option value="0" @selected(!false)>{{ __('global.no') }}</option>
                                <option value="1" @selected(false)>{{ __('global.yes') }}</option>
                            </select>
                        </div>

                    </div>
                </td>

                <td class="text-center align-middle" style="width: 60px;">
                    <button type="button" class="btn btn-sm btn-label-danger remove-doc-row waves-effect"
                            title="{{ __('employee.documents.delete_header') }}">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- أزرار الحفظ والإلغاء --}}
<div class="d-flex justify-content-end gap-2 mt-4 border-top pt-3">
    <a href="{{ route('admin.employees') }}" class="btn btn-label-secondary waves-effect">
        <i class="fa-solid fa-xmark me-1"></i> {{ __('global.cancel') }}
    </a>
    <button type="submit" class="btn btn-primary waves-effect waves-light" 
            data-confirm="{{ __('global.confirm_save') }}">
        <i class="fa-solid fa-check me-1"></i> {{ __('global.save') }}
    </button>
</div>

@once
    @push('css')
        <link rel="stylesheet" href="/admin/vendor/libs/select2/select2.css" />
    @endpush
    @push('js')
        <script src="/admin/vendor/libs/select2/select2.js"></script>
    @endpush
@endonce

@push('js')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Initialize Bootstrap Tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });

        // Initialize Select2 on form selects
        if (typeof $.fn.select2 !== 'undefined') {
            $('.select2').select2({
                dir: $('html').attr('dir') || 'rtl',
                width: '100%'
            });
        }

        // 1. Toggle Account Fields
        const createAccountSwitch = document.getElementById('create_account');
        const accountContainer = document.getElementById('account_fields_container');

        if (createAccountSwitch && accountContainer) {
            createAccountSwitch.addEventListener('change', function () {
                if (this.checked) {
                    accountContainer.classList.remove('d-none');
                } else {
                    accountContainer.classList.add('d-none');
                }
            });
        }

        // 2. Toggle Password Visibility
        document.querySelectorAll('.toggle-password-visibility').forEach(function (toggleBtn) {
            toggleBtn.addEventListener('click', function () {
                const input = this.closest('.form-password-toggle').querySelector('input');
                const icon = this.querySelector('i');
                if (input.type === 'password') {
                    input.type = 'text';
                    icon.classList.remove('tabler-eye-off');
                    icon.classList.add('tabler-eye');
                } else {
                    input.type = 'password';
                    icon.classList.remove('tabler-eye');
                    icon.classList.add('tabler-eye-off');
                }
            });
        });

        // 3. Dynamic Allowances Table
        let allowanceIndex = document.querySelectorAll('#emp_allowances_table tbody tr').length;
        const addAllowanceBtn = document.getElementById('add_emp_allowance_btn');
        const allowancesTbody = document.querySelector('#emp_allowances_table tbody');

        if (addAllowanceBtn && allowancesTbody) {
            addAllowanceBtn.addEventListener('click', function () {
                const tr = document.createElement('tr');
                tr.className = 'allowance-row';
                tr.innerHTML = `
                    <td>
                        <input type="text" name="allowances[${allowanceIndex}][name]" class="form-control form-control-sm" placeholder="اسم البدل (مثال: بدل سكن / بدل انتقال)" required>
                    </td>
                    <td>
                        <input type="number" step="0.01" name="allowances[${allowanceIndex}][amount]" class="form-control form-control-sm" placeholder="0.00" dir="ltr" required>
                    </td>
                    <td class="text-center">
                        <button type="button" class="btn btn-sm btn-label-danger remove-allowance-row waves-effect">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </td>
                `;
                allowancesTbody.appendChild(tr);
                allowanceIndex++;
            });

            allowancesTbody.addEventListener('click', function (e) {
                if (e.target.closest('.remove-allowance-row')) {
                    e.target.closest('tr').remove();
                }
            });
        }

        // 4. Dynamic Documents Table
        let docIndex = document.querySelectorAll('#documents_table tbody tr').length;
        const addDocBtn = document.getElementById('add_doc_row');
        const docsTbody = document.querySelector('#documents_table tbody');

        if (addDocBtn && docsTbody) {
            addDocBtn.addEventListener('click', function () {
                const tr = document.createElement('tr');
                tr.className = 'doc-row';
                tr.innerHTML = `
                    <td>
                        <div class="row g-2">

                            <div class="col-md-4">
                                <label class="form-label small text-muted mb-1">
                                    {{ __('document.fields.document_type') }}
                                </label>
                                <select name="documents[${docIndex}][document_type]" class="form-select select2">
                                    @foreach (__('document.types') as $typeKey => $typeLabel)
                                        <option value="{{ $typeKey }}" @selected($typeKey === 'other')>{{ $typeLabel }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-8">
                                <label class="form-label small text-muted mb-1">
                                    {{ __('document.fields.title') }}
                                    <span class="text-muted">({{ __('global.optional') }})</span>
                                    <i class="fa-solid fa-circle-info text-primary"
                                       data-bs-toggle="tooltip"
                                       title="{{ __('document.hints.title') }}"></i>
                                </label>
                                <input type="text" name="documents[${docIndex}][title]" class="form-control"
                                       placeholder="{{ __('employee.documents.name_placeholder') }}">
                            </div>

                            <div class="col-12">
                                <label class="form-label small text-muted mb-1">
                                    {{ __('document.fields.file') }} <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <input type="text" name="documents[${docIndex}][file_path]" id="doc_file_${docIndex}"
                                           class="form-control" placeholder="{{ __('employee.documents.path_placeholder') }}">
                                    <button type="button" class="btn btn-file-manager waves-effect waves-light"
                                            data-input="doc_file_${docIndex}" data-preview="doc_preview_${docIndex}">
                                        <i class="fas fa-folder-open me-1"></i> {{ __('employee.documents.choose_file') }}
                                    </button>
                                    <button type="button" class="btn btn-clear-file waves-effect waves-light"
                                            onclick="clearFileInput('doc_file_${docIndex}', 'doc_preview_${docIndex}')">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </div>
                                <div id="doc_preview_${docIndex}" class="file-preview-container mt-1"></div>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label small text-muted mb-1">{{ __('document.fields.issue_date') }}</label>
                                <input type="date" name="documents[${docIndex}][issue_date]" class="form-control" dir="ltr">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label small text-muted mb-1">{{ __('document.fields.expiry_date') }}</label>
                                <input type="date" name="documents[${docIndex}][expiry_date]" class="form-control" dir="ltr">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label small text-muted mb-1">{{ __('document.fields.is_verified') }}</label>
                                <select name="documents[${docIndex}][is_verified]" class="form-select select2">
                                    <option value="0" selected>{{ __('global.no') }}</option>
                                    <option value="1">{{ __('global.yes') }}</option>
                                </select>
                            </div>

                        </div>
                    </td>

                    <td class="text-center align-middle" style="width: 60px;">
                        <button type="button" class="btn btn-sm btn-label-danger remove-doc-row waves-effect">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </td>
                `;
                docsTbody.appendChild(tr);

                // Re-bind LFM button if LFM is loaded
                if (typeof window.bindLfmButtons === 'function') {
                    window.bindLfmButtons();
                }

                // الصف الجديد لازم يتفعّل عليه select2، الدالة
                // بتتخطى اللي متفعّل قبل كده فمفيش تكرار.
                if (typeof window.initSelect2 === 'function') {
                    window.initSelect2(tr);
                }

                docIndex++;
            });

            docsTbody.addEventListener('click', function (e) {
                if (e.target.closest('.remove-doc-row')) {
                    e.target.closest('tr').remove();
                }
            });
        }

        // Live calculation hints for custom daily and hourly rate
        const salaryInput = document.getElementById('salary');
        const customDailyInput = document.getElementById('custom_daily_rate');
        const customHourlyInput = document.getElementById('custom_hourly_rate');
        const dailyHoursInput = document.getElementById('daily_work_hours');

        function updateRateHints() {
            const salary = parseFloat(salaryInput ? salaryInput.value : 0) || 0;
            const hours = parseFloat(dailyHoursInput ? dailyHoursInput.value : 8) || 8;
            if (salary > 0) {
                const autoDaily = (salary / 30).toFixed(2);
                const autoHourly = (autoDaily / (hours > 0 ? hours : 8)).toFixed(2);
                if (customDailyInput && !customDailyInput.value) {
                    customDailyInput.placeholder = `تلقائي: ${autoDaily}`;
                }
                if (customHourlyInput && !customHourlyInput.value) {
                    customHourlyInput.placeholder = `تلقائي: ${autoHourly}`;
                }
            }
        }
        if (salaryInput) {
            salaryInput.addEventListener('input', updateRateHints);
            if (dailyHoursInput) dailyHoursInput.addEventListener('input', updateRateHints);
            updateRateHints();
        }
    });

    function clearFileInput(inputId, previewId) {
        const input = document.getElementById(inputId);
        const preview = document.getElementById(previewId);
        if (input) input.value = '';
        if (preview) preview.innerHTML = '';
    }
</script>
@endpush
