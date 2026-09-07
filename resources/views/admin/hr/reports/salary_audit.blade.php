@extends('layouts.admin.app')
@section('title', __('report.salary_audit'))

@section('content')
<div class="card mb-4 border-0 shadow-sm">
    <div class="card-header border-bottom d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">
            <i class="fa-solid fa-file-invoice-dollar text-primary me-2"></i> {{ __('report.salary_audit') }}
        </h5>
        @if($selectedEmployee && $auditData)
            <div class="d-flex gap-2">
                <a href="{{ route('admin.reports.salary-audit', array_merge(request()->all(), ['export' => 'excel'])) }}" class="btn btn-success waves-effect waves-light">
                    <i class="fa-solid fa-file-excel me-1"></i> تصدير Excel
                </a>
                <button type="button" class="btn btn-label-secondary waves-effect" onclick="window.print()">
                    <i class="fa-solid fa-print me-1"></i> طباعة
                </button>
            </div>
        @endif
    </div>
    <div class="card-body pt-4">
        <form action="{{ route('admin.reports.salary-audit') }}" method="GET" class="row g-3 align-items-end">
            <div class="col-md-5">
                <label class="form-label fw-bold">{{ __('reward.fields.employee') }} <span class="text-danger">*</span></label>
                <select name="employee_id" class="form-select select2" required>
                    <option value="">{{ __('global.choose') }}...</option>
                    @foreach($employees as $emp)
                        <option value="{{ $emp->id }}" {{ $selectedEmployeeId == $emp->id ? 'selected' : '' }}>
                            {{ $emp->name }} ({{ $emp->employee_code }}) - {{ $emp->branch?->name ?? '' }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-bold">الشهر</label>
                <select name="month" class="form-select select2">
                    @for($m = 1; $m <= 12; $m++)
                        @php $mVal = str_pad($m, 2, '0', STR_PAD_LEFT); @endphp
                        <option value="{{ $mVal }}" {{ $month == $mVal ? 'selected' : '' }}>
                            {{ __('global.months.' . $mVal) }}
                        </option>
                    @endfor
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label fw-bold">السنة</label>
                <select name="year" class="form-select select2">
                    @for($y = date('Y') - 3; $y <= date('Y') + 1; $y++)
                        <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endfor
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100 d-flex align-items-center justify-content-center" style="height: 38px;">
                    <i class="fa-solid fa-magnifying-glass me-1"></i> عرض التقرير
                </button>
            </div>
        </form>
    </div>
</div>

@if($selectedEmployee && $auditData)
    {{-- Employee Summary Details Card --}}
    <div class="card mb-4 border-0 shadow-sm">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-md-7">
                    <h5 class="fw-bold text-primary mb-1">
                        <i class="fa-solid fa-user-tie me-2"></i> {{ $selectedEmployee->name }}
                        <span class="badge bg-label-secondary fs-6 ms-2" dir="ltr">{{ $selectedEmployee->employee_code }}</span>
                    </h5>
                    <div class="text-muted d-flex flex-wrap gap-3 mt-2">
                        <span><i class="fa-solid fa-building me-1"></i> الفرع: <strong>{{ $selectedEmployee->branch?->name ?? '-' }}</strong></span>
                        <span><i class="fa-solid fa-briefcase me-1"></i> المسمى: <strong>{{ $selectedEmployee->jobTitle?->name ?? '-' }}</strong></span>
                        <span><i class="fa-solid fa-calendar me-1"></i> فترة التدقيق: <strong>{{ $auditData['startDate'] }} إلى {{ $auditData['endDate'] }}</strong></span>
                    </div>
                </div>
                <div class="col-md-5 text-md-end mt-3 mt-md-0">
                    <div class="d-inline-block text-start p-3 rounded border">
                        <small class="text-muted d-block">الراتب الأساسي المعتمد:</small>
                        <span class="fs-5 fw-bold text-dark"><span dir="ltr">{{ number_format($auditData['basic_salary'], 2) }}</span> {{ $auditData['currency'] }}</span>
                        <small class="text-muted d-block mt-1">سعر اليوم: <span dir="ltr">{{ number_format($auditData['daily_rate'], 2) }}</span> {{ $auditData['currency'] }}</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <small class="text-muted fw-semibold d-block mb-1">إجمالي المستحقات والإضافات</small>
                            <h3 class="mb-0 fw-bold text-success">
                                <span dir="ltr">+{{ number_format($auditData['gross_salary'], 2) }}</span>
                                <small class="fs-6">{{ $auditData['currency'] }}</small>
                            </h3>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-success"><i class="fa-solid fa-arrow-trend-up"></i></span>
                        </div>
                    </div>
                    <div class="mt-3 pt-2 border-top small text-muted">
                        يشمل الأساسي (<span dir="ltr">{{ number_format($auditData['basic_salary'], 2) }}</span>) + بدلات ثابتة (<span dir="ltr">{{ number_format($auditData['total_fixed_allowances'], 2) }}</span>) + مكافآت استثنائية (<span dir="ltr">{{ number_format($auditData['total_rewards'], 2) }}</span>)
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <small class="text-muted fw-semibold d-block mb-1">إجمالي الاستقطاعات والخصومات</small>
                            <h3 class="mb-0 fw-bold text-danger">
                                <span dir="ltr">-{{ number_format($auditData['total_deductions'], 2) }}</span>
                                <small class="fs-6">{{ $auditData['currency'] }}</small>
                            </h3>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-danger"><i class="fa-solid fa-arrow-trend-down"></i></span>
                        </div>
                    </div>
                    <div class="mt-3 pt-2 border-top small text-muted">
                        يشمل جزاءات (<span dir="ltr">{{ number_format($auditData['total_penalties'], 2) }}</span>) + تجاوز إذن (<span dir="ltr">{{ number_format($auditData['perm_excess_cost'], 2) }}</span>) + إجازات (<span dir="ltr">{{ number_format($auditData['unpaid_leave_cost'], 2) }}</span>) + سداد سلف (<span dir="ltr">{{ number_format($auditData['total_advances_repaid'], 2) }}</span>)
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <small class="text-muted fw-semibold d-block mb-1">صافي الراتب المستحق للصرف</small>
                            <h3 class="mb-0 fw-bold text-primary">
                                <span dir="ltr">{{ number_format($auditData['net_estimated_pay'], 2) }}</span>
                                <small class="fs-6">{{ $auditData['currency'] }}</small>
                            </h3>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-primary"><i class="fa-solid fa-wallet"></i></span>
                        </div>
                    </div>
                    <div class="mt-3 pt-2 border-top small text-muted">
                        الصافي النهائي للشهر المحدد بعد احتساب كافة الحركات المالية
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Breakdown Items Table --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header border-bottom d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">
                <i class="fa-solid fa-list-check me-2"></i> سجل وتدقيق الحركات المالية للشهر ({{ $auditData['breakdown']->count() }} حركة)
            </h5>
        </div>
        <div class="table-responsive text-nowrap">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-dark">
                    <tr>
                        <th style="width: 50px;">#</th>
                        <th>التاريخ</th>
                        <th>نوع الحركة</th>
                        <th>البيان والتفاصيل</th>
                        <th class="text-center">الأثر المالي</th>
                        <th class="text-end">المبلغ</th>
                        <th>ملاحظات / السبب</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($auditData['breakdown'] as $idx => $item)
                        <tr>
                            <td>{{ $idx + 1 }}</td>
                            <td><span dir="ltr">{{ $item['date'] }}</span></td>
                            <td>
                                @if($item['type'] === 'base')
                                    <span class="badge bg-label-dark">أساسي</span>
                                @elseif($item['type'] === 'fixed_allowance')
                                    <span class="badge bg-label-info">بدل ثابت</span>
                                @elseif($item['type'] === 'reward')
                                    <span class="badge bg-label-success">مكافأة / حافز</span>
                                @elseif($item['type'] === 'penalty')
                                    <span class="badge bg-label-danger">جزاء / خصم</span>
                                @elseif($item['type'] === 'advance_repayment')
                                    <span class="badge bg-label-warning">سداد سلفة</span>
                                @elseif($item['type'] === 'unpaid_leave')
                                    <span class="badge bg-label-secondary">إجازة بدون أجر</span>
                                @elseif($item['type'] === 'permission_excess')
                                    <span class="badge bg-label-danger">تجاوز ساعات</span>
                                @endif
                            </td>
                            <td class="fw-semibold">{{ $item['title'] }}</td>
                            <td class="text-center">
                                @if($item['direction'] === 'plus')
                                    <span class="badge bg-label-success"><i class="fa-solid fa-plus me-1"></i> استحقاق</span>
                                @else
                                    <span class="badge bg-label-danger"><i class="fa-solid fa-minus me-1"></i> استقطاع</span>
                                @endif
                            </td>
                            <td class="text-end fw-bold">
                                @if($item['direction'] === 'plus')
                                    <span class="text-success"><span dir="ltr">+{{ number_format($item['amount'], 2) }}</span> {{ $auditData['currency'] }}</span>
                                @else
                                    <span class="text-danger"><span dir="ltr">-{{ number_format($item['amount'], 2) }}</span> {{ $auditData['currency'] }}</span>
                                @endif
                            </td>
                            <td class="text-muted"><small>{{ $item['notes'] }}</small></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">لا توجد حركات مسجلة لهذا الشهر</td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot class="border-top-2">
                    <tr class="fw-bold">
                        <td colspan="4" class="text-end fs-6">صافي الراتب المستحق للصرف لشهر {{ $month }}/{{ $year }}:</td>
                        <td class="text-center"><span class="badge bg-label-primary fs-6">الصافي</span></td>
                        <td class="text-end fs-5 text-primary">
                            <span dir="ltr">{{ number_format($auditData['net_estimated_pay'], 2) }}</span> {{ $auditData['currency'] }}
                        </td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
@elseif($selectedEmployeeId)
    <div class="alert alert-warning border-0">
        <i class="fa-solid fa-circle-exclamation me-2"></i> لم يتم العثور على بيانات تدقيق للموظف المحدد في هذه الفترة.
    </div>
@else
    <div class="card border-0 shadow-sm text-center py-5">
        <div class="card-body">
            <div class="avatar avatar-xl mx-auto mb-3">
                <span class="avatar-initial rounded-circle bg-label-primary"><i class="fa-solid fa-magnifying-glass fs-3"></i></span>
            </div>
            <h5 class="fw-bold">يرجى اختيار موظف والشهر المطلوب لمعاينة كشف وسجل المرتب والتدقيق المالي</h5>
            <p class="text-muted mb-0">سيعرض التقرير كافة الاستحقاقات، البدلات الثابتة، المكافآت، الخصومات والجزاءات، وأقساط السلف بالتفصيل والأثر المالي.</p>
        </div>
    </div>
@endif
@endsection
