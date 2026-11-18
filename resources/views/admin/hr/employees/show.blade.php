@extends('layouts.admin.app')
@section('title', $employee->name . ' - ' . __('employee.profile'))

@section('content')
<div class="row">
    {{-- كارت ملخص الموظف السريع --}}
    <div class="col-xl-4 col-lg-5 col-md-5 mb-4">
        <div class="card h-100">
            <div class="card-body text-center">
                <div class="avatar avatar-xl mx-auto mb-3">
                    <img src="{{ $employee->avatar_url }}" alt="{{ $employee->name }}" class="rounded-circle w-100 h-100 object-fit-cover shadow-sm">
                </div>
                <h4 class="mb-1 fw-bold">{{ $employee->name }}</h4>
                <span class="badge bg-label-primary fs-6 mb-2">{{ $employee->jobTitle?->name ?? '-' }}</span>
                <p class="text-muted small mb-3"><i class="fa-solid fa-hashtag me-1"></i>{{ $employee->employee_code }}</p>

                <div class="d-flex justify-content-center gap-2 mb-4">
                    @php
                        $statusClass = match ($employee->status) {
                            'active'     => 'bg-success',
                            'on_leave'   => 'bg-warning',
                            'terminated' => 'bg-danger',
                            default      => 'bg-secondary',
                        };
                    @endphp
                    <span class="badge {{ $statusClass }}">{{ __('employee.statuses.' . $employee->status) }}</span>
                    <span class="badge bg-label-info">{{ __('employee.contract_types.' . $employee->contract_type) }}</span>
                </div>

                <div class="d-flex justify-content-center gap-2">
                    @if (admin_can('employee_update'))
                        <a href="{{ route('admin.employees.edit', $employee->id) }}" class="btn btn-primary btn-sm">
                            <i class="fa-solid fa-edit me-1"></i> {{ __('global.edit') }}
                        </a>
                    @endif
                    <a href="{{ route('admin.employees') }}" class="btn btn-label-secondary btn-sm">
                        <i class="fa-solid fa-arrow-right me-1"></i> {{ __('employee.back') }}
                    </a>
                </div>
            </div>

            <hr class="my-0">

            <div class="card-body">
                <h6 class="text-muted text-uppercase fs-7 mb-3">{{ __('employee.sections.personal') }}</h6>
                <ul class="list-unstyled mb-0">
                    <li class="d-flex align-items-center mb-3">
                        <i class="fa-solid fa-location-dot me-2 text-primary"></i>
                        <span class="fw-medium me-2">{{ __('employee.fields.branch') }}:</span>
                        <span>{{ $employee->branch?->name }} ({{ $employee->branch?->country }})</span>
                    </li>
                    <li class="d-flex align-items-center mb-3">
                        <i class="fa-solid fa-phone me-2 text-primary"></i>
                        <span class="fw-medium me-2">{{ __('employee.fields.phone') }}:</span>
                        <span dir="ltr" class="d-inline-block text-start" style="unicode-bidi: isolate;">{{ $employee->phone }}</span>
                    </li>
                    @if ($employee->alt_phone)
                    <li class="d-flex align-items-center mb-3">
                        <i class="fa-solid fa-mobile-screen me-2 text-primary"></i>
                        <span class="fw-medium me-2">{{ __('employee.fields.alt_phone') }}:</span>
                        <span dir="ltr" class="d-inline-block text-start" style="unicode-bidi: isolate;">{{ $employee->alt_phone }}</span>
                    </li>
                    @endif
                    @if ($employee->personal_email)
                    <li class="d-flex align-items-center mb-3">
                        <i class="fa-solid fa-envelope me-2 text-primary"></i>
                        <span class="fw-medium me-2">{{ __('employee.fields.personal_email') }}:</span>
                        <span>{{ $employee->personal_email }}</span>
                    </li>
                    @endif
                    <li class="d-flex align-items-center mb-3">
                        <i class="fa-solid fa-calendar-check me-2 text-primary"></i>
                        <span class="fw-medium me-2">{{ __('employee.fields.hire_date') }}:</span>
                        <span>{{ $employee->hire_date?->format('Y-m-d') }}</span>
                    </li>
                    @if ($employee->manager)
                    <li class="d-flex align-items-center mb-3">
                        <i class="fa-solid fa-user-tie me-2 text-primary"></i>
                        <span class="fw-medium me-2">{{ __('employee.fields.manager') }}:</span>
                        <a href="{{ route('admin.employees.show', $employee->manager_id) }}">{{ $employee->manager->name }}</a>
                    </li>
                    @endif
                    @if ($employee->admin)
                    <li class="d-flex align-items-center">
                        <i class="fa-solid fa-shield-halved me-2 text-success"></i>
                        <span class="fw-medium me-2">{{ __('admin.title') }}:</span>
                        <span class="badge bg-label-success">{{ $employee->admin->email }}</span>
                    </li>
                    @endif
                </ul>
            </div>
        </div>
    </div>

    {{-- التبويبات والمحتوى التفصيلي --}}
    <div class="col-xl-8 col-lg-7 col-md-7">
        <div class="nav-align-top mb-4">
            <ul class="nav nav-tabs nav-fill" role="tablist" id="employeeProfileTabs">
                <li class="nav-item">
                    <button type="button" class="nav-link active" role="tab" data-bs-toggle="tab" data-bs-target="#tab-general" id="btn-tab-general">
                        <i class="fa-solid fa-circle-info me-1"></i> {{ __('employee.tabs.general') }}
                    </button>
                </li>

                @if (admin_can('employee_salary_view'))
                <li class="nav-item">
                    <button type="button" class="nav-link" role="tab" data-bs-toggle="tab" data-bs-target="#tab-salary" id="btn-tab-salary">
                        <i class="fa-solid fa-money-bill-wave me-1"></i> {{ __('employee.tabs.salaries') }}
                        @if ($employee->salaries->count() > 0)
                            <span class="badge rounded-pill bg-label-primary ms-1">{{ $employee->salaries->count() }}</span>
                        @endif
                    </button>
                </li>
                @endif

                <li class="nav-item">
                    <button type="button" class="nav-link" role="tab" data-bs-toggle="tab" data-bs-target="#tab-docs" id="btn-tab-docs">
                        <i class="fa-solid fa-folder-open me-1"></i> {{ __('employee.tabs.documents') }}
                        @if ($employee->documents->count() > 0)
                            <span class="badge rounded-pill bg-label-info ms-1">{{ $employee->documents->count() }}</span>
                        @endif
                    </button>
                </li>

                <li class="nav-item">
                    <button type="button" class="nav-link" role="tab" data-bs-toggle="tab" data-bs-target="#tab-skills" id="btn-tab-skills">
                        <i class="fa-solid fa-graduation-cap me-1"></i> {{ __('employee.tabs.skills') }}
                        @if ($employee->skills->count() > 0)
                            <span class="badge rounded-pill bg-label-success ms-1">{{ $employee->skills->count() }}</span>
                        @endif
                    </button>
                </li>
            </ul>

            <div class="tab-content border-top-0 p-4">
                {{-- 1. تبويب البيانات الأساسية --}}
                <div class="tab-pane fade show active" id="tab-general" role="tabpanel">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <h6 class="fw-bold text-primary mb-3"><i class="fa-solid fa-id-card me-1"></i> {{ __('employee.sections.personal') }}</h6>
                            <table class="table table-sm table-borderless">
                                <tr><th class="w-40 text-muted">{{ __('employee.fields.national_id') }}:</th><td>{{ $employee->national_id ?: '-' }}</td></tr>
                                <tr><th class="text-muted">{{ __('employee.fields.birth_date') }}:</th><td>{{ $employee->birth_date ? $employee->birth_date->format('Y-m-d') : '-' }}</td></tr>
                                <tr><th class="text-muted">{{ __('employee.fields.gender') }}:</th><td>{{ __('employee.genders.' . $employee->gender) }}</td></tr>
                                <tr><th class="text-muted">{{ __('employee.fields.marital_status') }}:</th><td>{{ $employee->marital_status ? __('employee.marital_statuses.' . $employee->marital_status) : '-' }}</td></tr>
                                <tr><th class="text-muted">{{ __('employee.fields.nationality') }}:</th><td>{{ $employee->nationality ?: '-' }}</td></tr>
                                <tr><th class="text-muted">{{ __('employee.fields.address') }}:</th><td>{{ $employee->address ?: '-' }}</td></tr>
                            </table>
                        </div>

                        <div class="col-md-6">
                            <h6 class="fw-bold text-primary mb-3"><i class="fa-solid fa-briefcase me-1"></i> {{ __('employee.sections.job') }}</h6>
                            <table class="table table-sm table-borderless">
                                <tr><th class="w-40 text-muted">{{ __('employee.fields.weekly_hours') }}:</th><td><span class="badge bg-label-secondary">{{ $employee->weekly_hours }} {{ __('ساعة') }}</span></td></tr>
                                <tr><th class="text-muted">{{ __('employee.fields.probation_end_date') }}:</th><td>{{ $employee->probation_end_date ? $employee->probation_end_date->format('Y-m-d') : '-' }}</td></tr>
                                @if ($employee->status === 'terminated')
                                    <tr><th class="text-muted text-danger">{{ __('employee.fields.termination_date') }}:</th><td>{{ $employee->termination_date?->format('Y-m-d') }}</td></tr>
                                    <tr><th class="text-muted text-danger">{{ __('employee.fields.termination_reason') }}:</th><td>{{ $employee->termination_reason }}</td></tr>
                                @endif
                            </table>

                            <h6 class="fw-bold text-primary mt-3 mb-2"><i class="fa-solid fa-phone-volume me-1"></i> {{ __('employee.sections.emergency') }}</h6>
                            <table class="table table-sm table-borderless">
                                <tr><th class="w-40 text-muted">{{ __('employee.fields.emergency_name') }}:</th><td>{{ $employee->emergency_contact_name ?: '-' }}</td></tr>
                                <tr>
                                    <th class="text-muted">{{ __('employee.fields.emergency_phone') }}:</th>
                                    <td>
                                        @if($employee->emergency_contact_phone)
                                            <span dir="ltr" class="d-inline-block text-start" style="unicode-bidi: isolate;">{{ $employee->emergency_contact_phone }}</span>
                                        @else
                                            -
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </div>

                        {{-- قسم الحساب البنكي (صلاحية خاصة) --}}
                        @if (admin_can('employee_bank_view'))
                        <div class="col-12">
                            <div class="border rounded p-3 bg-transparent">
                                <h6 class="fw-bold text-primary mb-3">
                                    <i class="fa-solid fa-building-columns me-1"></i> {{ __('employee.sections.financial') }}
                                    <span class="badge bg-label-info ms-2"><i class="fa-solid fa-lock me-1"></i>{{ __('role.action.bank_view') }}</span>
                                </h6>
                                <div class="row g-3">
                                    <div class="col-md-3"><strong>{{ __('employee.fields.bank_name') }}:</strong> <div>{{ $employee->bank_name ?: '-' }}</div></div>
                                    <div class="col-md-3"><strong>{{ __('employee.fields.bank_account_number') }}:</strong> <div>{{ $employee->bank_account_number ?: '-' }}</div></div>
                                    <div class="col-md-3"><strong>{{ __('employee.fields.iban') }}:</strong> <div dir="ltr">{{ $employee->iban ?: '-' }}</div></div>
                                    <div class="col-md-3"><strong>{{ __('employee.fields.insurance_number') }}:</strong> <div>{{ $employee->insurance_number ?: '-' }}</div></div>
                                </div>
                            </div>
                        </div>
                        @endif

                        {{-- قسم إعدادات الموارد البشرية والدوام --}}
                        <div class="col-12">
                            <div class="border rounded p-3 bg-transparent">
                                <h6 class="fw-bold text-info mb-3">
                                    <i class="fa-solid fa-sliders me-1"></i> {{ __('employee.sections.hr_settings') }}
                                </h6>
                                <div class="row g-3">
                                    <div class="col-md-2 col-sm-6"><strong>{{ __('employee.fields.annual_leave_entitlement') }}:</strong> <div><span class="badge bg-label-primary">{{ $employee->annual_leave_entitlement ?? 21 }} {{ __('يوم') }}</span></div></div>
                                    <div class="col-md-2 col-sm-6"><strong>{{ __('employee.fields.monthly_permission_hours_limit') }}:</strong> <div><span class="badge bg-label-info">{{ $employee->monthly_permission_hours_limit ?? 4 }} {{ __('ساعة') }}</span></div></div>
                                    <div class="col-md-2 col-sm-6"><strong>{{ __('employee.fields.daily_work_hours') }}:</strong> <div><span class="badge bg-label-secondary">{{ $employee->daily_work_hours ?? 8 }} {{ __('ساعة') }}</span></div></div>
                                    <div class="col-md-3 col-sm-6"><strong>سعر يوم العمل (Daily Rate):</strong> <div><span class="fw-bold text-success" dir="ltr">{{ number_format($employee->daily_rate, 2) }}</span> {{ $employee->branch?->currency ?? 'EGP' }} @if($employee->custom_daily_rate) <span class="badge bg-label-warning text-xs">مخصص</span> @else <span class="badge bg-label-secondary text-xs">تلقائي</span> @endif</div></div>
                                    <div class="col-md-3 col-sm-6"><strong>{{ __('employee.fields.custom_hourly_rate') }}:</strong> <div><span class="fw-bold text-primary" dir="ltr">{{ number_format($employee->hourly_rate, 2) }}</span> {{ $employee->branch?->currency ?? 'EGP' }} @if($employee->custom_hourly_rate) <span class="badge bg-label-warning text-xs">مخصص</span> @endif</div></div>
                                </div>
                            </div>
                        </div>

                        {{-- دخول بوابة الموظف --}}
                        @include('admin.hr.employees.partials.portal-access', ['employee' => $employee])

                        @if ($employee->notes)
                        <div class="col-12">
                            <h6 class="fw-bold text-muted mb-1"><i class="fa-solid fa-note-sticky me-1"></i> {{ __('employee.sections.notes') }}</h6>
                            <div class="alert alert-secondary mb-0">{{ $employee->notes }}</div>
                        </div>
                        @endif
                    </div>
                </div>

                {{-- 2. تبويب سجل الرواتب والبدلات الشامل --}}
                @if (admin_can('employee_salary_view'))
                <div class="tab-pane fade" id="tab-salary" role="tabpanel">
                    @php
                        $latestSalary = $employee->currentSalary;
                        $basicAmount  = $latestSalary ? (float)$latestSalary->basic_salary : (float)($employee->basic_salary ?? 0);
                        $totalAllowances = (float)$employee->allowances->sum('amount');
                        $grossTotal   = $basicAmount + $totalAllowances;
                        $curr         = $latestSalary?->currency ?? $employee->branch?->currency ?? 'EGP';
                    @endphp

                    {{-- كروت الملخص المالي --}}
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <div class="card border shadow-none bg-label-primary h-100">
                                <div class="card-body p-3 text-center">
                                    <span class="text-muted d-block mb-1">{{ __('salary.fields.basic_salary') }}</span>
                                    <h4 class="fw-bold mb-0 text-primary">{{ number_format($basicAmount, 2) }} <small class="fs-6">{{ $curr }}</small></h4>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card border shadow-none bg-label-info h-100">
                                <div class="card-body p-3 text-center">
                                    <span class="text-muted d-block mb-1">{{ __('employee.allowances.title') }}</span>
                                    <h4 class="fw-bold mb-0 text-info">+ {{ number_format($totalAllowances, 2) }} <small class="fs-6">{{ $curr }}</small></h4>
                                    <small class="text-muted">({{ $employee->allowances->count() }} بدلات معتمدة)</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card border shadow-none bg-label-success h-100">
                                <div class="card-body p-3 text-center">
                                    <span class="text-muted d-block mb-1">إجمالي الراتب الشهري (شامل البدلات)</span>
                                    <h4 class="fw-bold mb-0 text-success">= {{ number_format($grossTotal, 2) }} <small class="fs-6">{{ $curr }}</small></h4>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- تفاصيل البدلات والثوابت الشهرية المعتمدة --}}
                    <div class="card border shadow-none mb-4">
                        <div class="card-header d-flex justify-content-between align-items-center py-2 px-3 border-bottom">
                            <h6 class="fw-bold mb-0 text-primary">
                                <i class="fa-solid fa-coins me-1"></i> تفاصيل البدلات والثوابت الشهرية المعتمدة
                            </h6>
                            @if (admin_can('employee_update'))
                                <a href="{{ route('admin.employees.edit', $employee->id) }}" class="btn btn-sm btn-outline-primary waves-effect">
                                    <i class="fa-solid fa-pen-to-square me-1"></i> تعديل البدلات
                                </a>
                            @endif
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 50px;">#</th>
                                        <th>{{ __('employee.allowances.name_header') }}</th>
                                        <th class="text-center" style="width: 160px;">النسبة من الأساسي</th>
                                        <th class="text-end" style="width: 200px;">{{ __('employee.allowances.amount_header') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($employee->allowances as $idx => $alw)
                                        <tr>
                                            <td>{{ $idx + 1 }}</td>
                                            <td class="fw-medium">{{ $alw->name }}</td>
                                            <td class="text-center text-muted">
                                                @if ($basicAmount > 0)
                                                    <span class="badge bg-label-secondary">{{ number_format(($alw->amount / $basicAmount) * 100, 1) }}%</span>
                                                @else
                                                    -
                                                @endif
                                            </td>
                                            <td class="text-end fw-bold text-primary">{{ number_format($alw->amount, 2) }} {{ $curr }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-center py-3 text-muted">
                                                <i class="fa-solid fa-coins me-1"></i> لا توجد بدلات شهرية ثابتة مسجلة لهذا الموظف
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                                @if ($employee->allowances->count() > 0)
                                <tfoot class="table-light">
                                    <tr>
                                        <th colspan="3" class="text-end fw-bold">إجمالي البدلات الشهرية:</th>
                                        <th class="text-end fw-bold text-success">{{ number_format($totalAllowances, 2) }} {{ $curr }}</th>
                                    </tr>
                                </tfoot>
                                @endif
                            </table>
                        </div>
                    </div>

                    {{-- سجل تدرج وتعديل الراتب --}}
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h6 class="mb-0 fw-bold"><i class="fa-solid fa-clock-rotate-left me-1"></i> سجل تدرج وتعديل الراتب</h6>
                            <small class="text-muted">{{ __('salary.fields.employee') }}: {{ $employee->name }}</small>
                        </div>
                        @if (admin_can('salary_create'))
                            <button type="button" class="btn btn-primary btn-sm waves-effect" data-bs-toggle="modal" data-bs-target="#addSalaryModal">
                                <i class="fa-solid fa-plus me-1"></i> {{ __('salary.add_new') }}
                            </button>
                        @endif
                    </div>

                    @if ($employee->salaries->isEmpty())
                        <div class="text-center py-4 text-muted border rounded">
                            <i class="fa-solid fa-money-bill-transfer fs-1 mb-2"></i>
                            <p class="mb-0">لا توجد حركات سابقة مسجلة في سجل الرواتب</p>
                        </div>
                    @else
                        <div class="table-responsive border rounded">
                            <table class="table table-hover table-striped mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>{{ __('salary.fields.basic_salary') }}</th>
                                        <th>{{ __('salary.fields.allowances') }}</th>
                                        <th>{{ __('salary.fields.total_salary') }}</th>
                                        <th>{{ __('salary.fields.effective_date') }}</th>
                                        <th>{{ __('salary.fields.change_reason') }}</th>
                                        <th>{{ __('salary.fields.approved_by') }}</th>
                                        <th>{{ __('salary.fields.notes') }}</th>
                                        @if (admin_can('salary_delete'))
                                            <th class="text-center">{{ __('global.actions') }}</th>
                                        @endif
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($employee->salaries as $salary)
                                        <tr>
                                            <td class="fw-bold">{{ number_format($salary->basic_salary, 2) }} {{ $salary->currency }}</td>
                                            <td>{{ number_format($salary->allowances, 2) }} {{ $salary->currency }}</td>
                                            <td class="fw-bold text-success">{{ number_format($salary->total_salary, 2) }} {{ $salary->currency }}</td>
                                            <td>{{ $salary->effective_date?->format('Y-m-d') }}</td>
                                            <td><span class="badge bg-label-info">{{ __('salary.reasons.' . $salary->change_reason) }}</span></td>
                                            <td>{{ $salary->approver?->name ?? '-' }}</td>
                                            <td><small class="text-muted">{{ $salary->notes ?: '-' }}</small></td>
                                            @if (admin_can('salary_delete'))
                                                <td class="text-center">
                                                    <form action="{{ route('admin.salaries.destroy', $salary->id) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-icon btn-label-danger waves-effect btn-swal-confirm"
                                                                data-title="{{ __('global.confirm_delete') }}"
                                                                data-text="هل أنت متأكد من حذف سجل الراتب؟"
                                                                data-icon="warning"
                                                                data-btn-text="{{ __('global.delete') }}"
                                                                title="{{ __('global.delete') }}">
                                                            <i class="fa-solid fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </td>
                                            @endif
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
                @endif

                {{-- 3. تبويب المستندات الرسمية --}}
                <div class="tab-pane fade" id="tab-docs" role="tabpanel">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h5 class="mb-0 fw-bold">{{ __('document.title') }}</h5>
                            <small class="text-muted">{{ __('document.fields.employee') }}: {{ $employee->name }}</small>
                        </div>
                        <div class="d-flex gap-2">
                            @if (admin_can('employee_edit'))
                                <a href="{{ route('admin.employees.edit', $employee->id) }}#documents_section" class="btn btn-primary btn-sm waves-effect">
                                    <i class="fa-solid fa-pen-to-square me-1"></i> إدارة ورفع المستندات من ملف الموظف
                                </a>
                            @endif
                            @if (admin_can('document_create'))
                                <button type="button" class="btn btn-outline-primary btn-sm waves-effect" data-bs-toggle="modal" data-bs-target="#addDocModal">
                                    <i class="fa-solid fa-upload me-1"></i> رفع سريع
                                </button>
                            @endif
                        </div>
                    </div>

                    @if ($employee->documents->isEmpty())
                        <div class="text-center py-5 text-muted border rounded">
                            <i class="fa-solid fa-folder-closed fs-1 mb-2"></i>
                            <p class="mb-0">{{ __('document.messages.empty_trash') }}</p>
                        </div>
                    @else
                        <div class="table-responsive border rounded">
                            <table class="table table-hover table-striped mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>{{ __('document.fields.document_type') }}</th>
                                        <th>{{ __('document.fields.title') }}</th>
                                        <th>{{ __('document.fields.issue_date') }}</th>
                                        <th>{{ __('document.fields.expiry_date') }}</th>
                                        <th>{{ __('document.fields.verified') }}</th>
                                        <th class="text-center">{{ __('global.actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($employee->documents as $doc)
                                        <tr>
                                            <td>
                                                @php
                                                    // fallback: لو النوع مش في القائمة، نعرض "مستندات أخرى"
                                                    // بدل ما يظهر مفتاح الترجمة الخام للمستخدم
                                                    $typeKey   = 'document.types.' . $doc->document_type;
                                                    $typeLabel = __($typeKey) === $typeKey
                                                        ? __('document.types.other')
                                                        : __($typeKey);
                                                @endphp
                                                <span class="badge bg-label-primary">{{ $typeLabel }}</span>
                                            </td>
                                            <td>
                                                <a href="{{ $doc->file_path }}" target="_blank" class="fw-bold">
                                                    <i class="fa-solid fa-file-arrow-down me-1"></i> {{ $doc->title ?: basename($doc->file_path) }}
                                                </a>
                                            </td>
                                            <td>{{ $doc->issue_date?->format('Y-m-d') ?? '-' }}</td>
                                            <td>
                                                @if ($doc->expiry_date)
                                                    <span class="badge {{ $doc->is_expired ? 'bg-label-danger' : ($doc->is_expiring_soon ? 'bg-label-warning' : 'bg-label-secondary') }}">
                                                        {{ $doc->expiry_date->format('Y-m-d') }}
                                                    </span>
                                                @else
                                                    -
                                                @endif
                                            </td>
                                            <td>
                                                @if ($doc->is_verified)
                                                    <span class="badge bg-label-success" data-bs-toggle="tooltip" data-bs-placement="top" title="{{ __('document.fields.verified_by') }}: {{ $doc->verifier?->name ?? '-' }} ({{ $doc->verified_at ? \Carbon\Carbon::parse($doc->verified_at)->format('Y-m-d') : '' }})">
                                                        <i class="fa-solid fa-check me-1"></i> {{ __('document.verified') }}
                                                    </span>
                                                    @if ($doc->verifier)
                                                        <small class="d-block text-muted mt-1" style="font-size: 0.75rem;">
                                                            <i class="fa-solid fa-user-check me-1"></i> {{ $doc->verifier->name }}
                                                        </small>
                                                    @endif
                                                @else
                                                    <span class="badge bg-label-warning"><i class="fa-solid fa-hourglass-half me-1"></i> {{ __('document.unverified') }}</span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                <a href="{{ $doc->file_path }}" target="_blank" class="btn btn-sm btn-icon btn-label-info me-1 waves-effect" title="{{ __('document.view_file') }}">
                                                    <i class="fa-solid fa-eye"></i>
                                                </a>
                                                @if (admin_can('document_update'))
                                                    <form action="{{ route('admin.documents.toggle-verification', $doc->id) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        @if ($doc->is_verified)
                                                            <button type="submit" class="btn btn-sm btn-icon btn-label-warning me-1 waves-effect btn-swal-confirm" 
                                                                    data-title="إلغاء الاعتماد؟" 
                                                                    data-text="هل أنت متأكد من إلغاء اعتماد هذا المستند وإعادته لقيد المراجعة؟"
                                                                    data-icon="warning"
                                                                    data-btn-text="نعم، إلغاء الاعتماد"
                                                                    title="إلغاء الاعتماد">
                                                                <i class="fa-solid fa-rotate-left"></i>
                                                            </button>
                                                        @else
                                                            <button type="submit" class="btn btn-sm btn-icon btn-label-success me-1 waves-effect btn-swal-confirm" 
                                                                    data-title="اعتماد المستند؟" 
                                                                    data-text="هل أنت متأكد من التحقق واعتماد هذا المستند رسميًا؟"
                                                                    data-icon="question"
                                                                    data-btn-text="نعم، اعتمد المستند"
                                                                    title="اعتماد المستند">
                                                                <i class="fa-solid fa-check-double"></i>
                                                            </button>
                                                        @endif
                                                    </form>
                                                @endif
                                                @if (admin_can('document_delete'))
                                                    <form action="{{ route('admin.documents.destroy', $doc->id) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-icon btn-label-danger waves-effect btn-swal-confirm"
                                                                data-title="{{ __('global.confirm_delete') }}"
                                                                data-text="سيتم نقل المستند لسلة المحذوفات"
                                                                data-icon="warning"
                                                                data-btn-text="{{ __('global.delete') }}"
                                                                title="{{ __('global.delete') }}">
                                                            <i class="fa-solid fa-trash"></i>
                                                        </button>
                                                    </form>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>

                {{-- 4. تبويب المهارات والخبرات --}}
                <div class="tab-pane fade" id="tab-skills" role="tabpanel">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h5 class="mb-0 fw-bold">{{ __('skill.title') }}</h5>
                            <small class="text-muted">{{ __('skill.fields.employee') }}: {{ $employee->name }}</small>
                        </div>
                        @if (admin_can('skill_create'))
                            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addSkillModal">
                                <i class="fa-solid fa-plus me-1"></i> {{ __('skill.add_new') }}
                            </button>
                        @endif
                    </div>

                    @if ($employee->skills->isEmpty())
                        <div class="text-center py-5 text-muted border rounded">
                            <i class="fa-solid fa-graduation-cap fs-1 mb-2"></i>
                            <p class="mb-0">{{ __('skill.messages.empty_trash') }}</p>
                        </div>
                    @else
                        <div class="table-responsive border rounded">
                            <table class="table table-hover table-striped mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>{{ __('skill.fields.skill_name') }}</th>
                                        <th>{{ __('skill.fields.proficiency_level') }}</th>
                                        <th>{{ __('skill.fields.years_experience') }}</th>
                                        <th>{{ __('skill.fields.evaluation_source') }}</th>
                                        <th>{{ __('skill.fields.is_core') }}</th>
                                        @if (admin_can('skill_delete'))
                                            <th class="text-center">{{ __('global.actions') }}</th>
                                        @endif
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($employee->skills as $skill)
                                        <tr>
                                            <td class="fw-bold">{{ $skill->skill_name }}</td>
                                            <td>
                                                @php
                                                    $lvlClass = match ($skill->proficiency_level) {
                                                        'expert'       => 'bg-label-success',
                                                        'advanced'     => 'bg-label-primary',
                                                        'intermediate' => 'bg-label-info',
                                                        default        => 'bg-label-secondary',
                                                    };
                                                @endphp
                                                <span class="badge {{ $lvlClass }}">{{ __('skill.levels.' . $skill->proficiency_level) }}</span>
                                            </td>
                                            <td>{{ $skill->years_of_experience }} {{ __('سنة') }}</td>
                                            <td><span class="badge bg-label-secondary">{{ __('skill.sources.' . $skill->evaluation_source) }}</span></td>
                                            <td>
                                                @if ($skill->is_core)
                                                    <span class="badge bg-success">{{ __('skill.core') }}</span>
                                                @else
                                                    <span class="badge bg-label-secondary">{{ __('skill.secondary') }}</span>
                                                @endif
                                            </td>
                                            @if (admin_can('skill_delete'))
                                                <td class="text-center">
                                                    <form action="{{ route('admin.skills.destroy', $skill->id) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="button" class="btn btn-sm btn-icon btn-label-danger waves-effect btn-swal-confirm" title="{{ __('global.delete') }}" data-title="تأكيد الحذف" data-text="{{ __('global.confirm_delete') }}" data-icon="warning" data-btn-text="نعم، احذف" data-btn-class="btn-danger">
                                                            <i class="fa-solid fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </td>
                                            @endif
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Modals لإضافة سجلات جديدة مباشرة من الملف الشخصي --}}

{{-- نوافذ دخول بوابة الموظف --}}
@include('admin.hr.employees.partials.portal-access-modals', ['employee' => $employee])

{{-- 1. Modal إضافة تعديل راتب — أرقامه ($curr وغيرها) بتتحسب في تبويب الرواتب، فمحتاج صلاحية عرضه --}}
@if (admin_can('salary_create') && admin_can('employee_salary_view'))
<div class="modal fade" id="addSalaryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form action="{{ route('admin.salaries.store') }}" method="POST" class="modal-content">
            @csrf
            <input type="hidden" name="employee_id" value="{{ $employee->id }}">
            <input type="hidden" name="currency" value="{{ $curr }}">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('salary.add_new') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-label-primary p-2 mb-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <span>إجمالي البدلات الثابتة الحالية:</span>
                        <strong class="text-primary">{{ number_format($totalAllowances, 2) }} {{ $curr }}</strong>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">{{ __('salary.fields.basic_salary') }} <span class="text-danger">*</span> <x-inputs.tip :text="__('salary.hints.basic_salary')" /></label>
                    <input type="number" step="0.01" name="basic_salary" id="modal_basic_salary" class="form-control" value="{{ $basicAmount }}" required dir="ltr">
                </div>
                <div class="mb-3">
                    <label class="form-label">{{ __('salary.fields.allowances') }} <x-inputs.tip :text="__('salary.hints.allowances')" /></label>
                    <input type="number" step="0.01" name="allowances" id="modal_allowances" class="form-control" value="{{ $totalAllowances }}" dir="ltr">
                </div>
                <div class="mb-3">
                    <div class="p-2 border rounded text-center">
                        <span class="text-muted small">إجمالي الراتب الناتج:</span>
                        <h5 class="fw-bold mb-0 text-success" id="modal_total_salary_display">{{ number_format($basicAmount + $totalAllowances, 2) }} {{ $curr }}</h5>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">{{ __('salary.fields.effective_date') }} <span class="text-danger">*</span> <x-inputs.tip :text="__('salary.hints.effective_date')" /></label>
                    <input type="date" name="effective_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">{{ __('salary.fields.change_reason') }} <span class="text-danger">*</span> <x-inputs.tip :text="__('salary.hints.change_reason')" /></label>
                    <select name="change_reason" class="form-select select2" required>
                        @foreach (__('salary.reasons') as $key => $reason)
                            <option value="{{ $key }}">{{ $reason }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">{{ __('salary.fields.notes') }}</label>
                    <textarea name="notes" class="form-control" rows="2"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary waves-effect waves-light"><i class="fa-solid fa-save me-1"></i> {{ __('global.save') }}</button>
                <button type="button" class="btn btn-label-secondary waves-effect" data-bs-dismiss="modal">{{ __('global.cancel') }}</button>
            </div>
        </form>
    </div>
</div>
@endif

{{-- 2. Modal رفع مستند --}}
@if (admin_can('document_create'))
<div class="modal fade" id="addDocModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form action="{{ route('admin.documents.store') }}" method="POST" class="modal-content">
            @csrf
            <input type="hidden" name="employee_id" value="{{ $employee->id }}">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('document.add_new') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">{{ __('document.fields.document_type') }}</label>
                    <select name="document_type" class="form-select select2" required>
                        @foreach (__('document.types') as $key => $type)
                            <option value="{{ $key }}">{{ $type }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">
                        {{ __('document.fields.title') }}
                        <span class="text-muted">({{ __('global.optional') }})</span>
                        <i class="fa-solid fa-circle-info text-primary"
                           data-bs-toggle="tooltip"
                           title="{{ __('document.hints.title') }}"></i>
                    </label>
                    <input type="text" name="title" class="form-control"
                           placeholder="{{ __('document.placeholders.title') }}">
                </div>
                <div class="mb-3">
                    <x-inputs.lfm name="file_path" type="file" :label="__('document.fields.file')" required />
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label">{{ __('document.fields.issue_date') }} <x-inputs.tip :text="__('document.hints.issue_date')" /></label>
                        <input type="date" name="issue_date" class="form-control">
                    </div>
                    <div class="col-6">
                        <label class="form-label">{{ __('document.fields.expiry_date') }}</label>
                        <input type="date" name="expiry_date" class="form-control">
                    </div>
                </div>
                <div class="mb-3 form-check form-switch">
                    <input type="checkbox" name="is_verified" value="1" id="is_verified_doc" class="form-check-input" checked>
                    <label class="form-check-label" for="is_verified_doc">{{ __('document.fields.verified') }} <x-inputs.tip :text="__('document.hints.verified')" /></label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary waves-effect waves-light"><i class="fa-solid fa-upload me-1"></i> {{ __('global.save') }}</button>
                <button type="button" class="btn btn-label-secondary waves-effect" data-bs-dismiss="modal">{{ __('global.cancel') }}</button>
            </div>
        </form>
    </div>
</div>
@endif

{{-- 3. Modal إضافة مهارة --}}
@if (admin_can('skill_create'))
<div class="modal fade" id="addSkillModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form action="{{ route('admin.skills.store') }}" method="POST" class="modal-content">
            @csrf
            <input type="hidden" name="employee_id" value="{{ $employee->id }}">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('skill.add_new') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">{{ __('skill.fields.skill_name') }} <span class="text-danger">*</span></label>
                    <input type="text" name="skill_name" class="form-control" placeholder="مثال: تحليل مالي، تصميم، برمجة PHP" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">{{ __('skill.fields.proficiency_level') }} <span class="text-danger">*</span> <x-inputs.tip :text="__('skill.hints.proficiency_level')" /></label>
                    <select name="proficiency_level" class="form-select select2" required>
                        @foreach (__('skill.levels') as $key => $lvl)
                            <option value="{{ $key }}">{{ $lvl }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">{{ __('skill.fields.years_experience') }}</label>
                    <input type="number" name="years_of_experience" class="form-control" value="1" min="0" max="60">
                </div>
                <div class="mb-3">
                    <label class="form-label">{{ __('skill.fields.evaluation_source') }} <span class="text-danger">*</span> <x-inputs.tip :text="__('skill.hints.evaluation_source')" /></label>
                    <select name="evaluation_source" class="form-select select2" required>
                        @foreach (__('skill.sources') as $key => $src)
                            <option value="{{ $key }}">{{ $src }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3 form-check form-switch">
                    <input type="checkbox" name="is_core" value="1" id="is_core_skill" class="form-check-input">
                    <label class="form-check-label" for="is_core_skill">{{ __('skill.fields.is_core') }} <x-inputs.tip :text="__('skill.hints.is_core')" /></label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary waves-effect waves-light"><i class="fa-solid fa-save me-1"></i> {{ __('global.save') }}</button>
                <button type="button" class="btn btn-label-secondary waves-effect" data-bs-dismiss="modal">{{ __('global.cancel') }}</button>
            </div>
        </form>
    </div>
</div>
@endif

@push('js')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const empStorageKey = 'sbbs_emp_tab_{{ $employee->id }}';

        // Function to activate tab cleanly
        function activateTab(targetId) {
            if (!targetId) return;
            if (!targetId.startsWith('#')) targetId = '#' + targetId;
            const triggerEl = document.querySelector(`button[data-bs-target="${targetId}"]`);
            if (triggerEl) {
                const tab = bootstrap.Tab.getOrCreateInstance(triggerEl);
                tab.show();
            }
        }

        // 1. Check priority: URL hash > sessionStorage
        let activeTab = window.location.hash;
        if (!activeTab || !document.querySelector(`button[data-bs-target="${activeTab}"]`)) {
            const saved = sessionStorage.getItem(empStorageKey);
            if (saved && document.querySelector(`button[data-bs-target="${saved}"]`)) {
                activeTab = saved;
            }
        }

        if (activeTab) {
            activateTab(activeTab);
        }

        // 2. Save active tab on change & update hash cleanly without page jump
        const tabList = document.querySelectorAll('#employeeProfileTabs button[data-bs-toggle="tab"]');
        tabList.forEach(function (tabEl) {
            tabEl.addEventListener('shown.bs.tab', function (e) {
                const target = e.target.getAttribute('data-bs-target');
                if (target) {
                    sessionStorage.setItem(empStorageKey, target);
                    if (window.history.replaceState) {
                        window.history.replaceState(null, null, target);
                    } else {
                        window.location.hash = target;
                    }
                }
            });
        });

        // 3. Dynamic Calculation in Add Salary Modal
        const basicInput = document.getElementById('modal_basic_salary');
        const allowancesInput = document.getElementById('modal_allowances');
        const totalDisplay = document.getElementById('modal_total_salary_display');
        const curr = '{{ $curr ?? '' }}';

        function calcModalTotal() {
            const b = parseFloat(basicInput?.value || 0);
            const a = parseFloat(allowancesInput?.value || 0);
            if (totalDisplay) {
                totalDisplay.textContent = (b + a).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' ' + curr;
            }
        }

        if (basicInput && allowancesInput) {
            basicInput.addEventListener('input', calcModalTotal);
            allowancesInput.addEventListener('input', calcModalTotal);
        }
    });
</script>
@endpush
@endsection
