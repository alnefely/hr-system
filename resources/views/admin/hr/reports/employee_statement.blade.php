@extends('layouts.admin.app')
@section('title', 'كشف حساب وتقارير الموظف الشاملة')

@section('content')
<div class="row g-4 mb-4">
    {{-- Filter Card --}}
    <div class="col-12 d-print-none">
        <div class="card shadow-sm border-0">
            <div class="card-header border-bottom py-3">
                <h5 class="card-title mb-0 fw-bold">
                    <i class="fa-solid fa-file-user text-primary me-2"></i> تقرير وكشف حساب الموظف (الإجازات، الأذونات، والجزاءات)
                </h5>
            </div>
            <div class="card-body pt-4">
                <form action="{{ route('admin.reports.employee-statement') }}" method="GET" class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label for="admin_hr_reports_employe_f_employee_id" class="form-label fw-bold mb-1 d-block">اختيار الموظف <span class="text-danger">*</span></label>
                        <select id="admin_hr_reports_employe_f_employee_id" name="employee_id" class="form-select select2" required style="width: 100%;">
                            <option value="">-- اختر الموظف لعرض التقرير --</option>
                            @foreach($employees as $emp)
                                <option value="{{ $emp->id }}" {{ request('employee_id') == $emp->id ? 'selected' : '' }}>
                                    {{ $emp->name }} ({{ $emp->employee_code }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="admin_hr_reports_employe_f_from_date" class="form-label fw-bold mb-1 d-block">من تاريخ</label>
                        <input id="admin_hr_reports_employe_f_from_date" type="date" name="from_date" class="form-control h-38" value="{{ $fromDate }}">
                    </div>
                    <div class="col-md-3">
                        <label for="admin_hr_reports_employe_f_to_date" class="form-label fw-bold mb-1 d-block">إلى تاريخ</label>
                        <input id="admin_hr_reports_employe_f_to_date" type="date" name="to_date" class="form-control h-38" value="{{ $toDate }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label d-block mb-1">&nbsp;</label>
                        <button type="submit" class="btn btn-primary w-100 d-flex align-items-center justify-content-center h-38">
                            <i class="fa-solid fa-filter me-1"></i> {{ __('global.filter') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @if($selectedEmployee && $stats)
        {{-- Employee Info Summary Banner (No bg-light) --}}
        <div class="col-12">
            <div class="card bg-label-primary border-0 shadow-sm">
                <div class="card-body p-4 d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <div>
                        <h4 class="mb-1 text-primary fw-bold">
                            <i class="fa-solid fa-user-tie me-2"></i> {{ $selectedEmployee->name }}
                        </h4>
                        <p class="mb-0 text-muted">
                            كود الموظف: <strong class="text-heading">{{ $selectedEmployee->employee_code }}</strong> | 
                            الفرع: <strong class="text-heading">{{ $selectedEmployee->branch?->name ?? 'غير محدد' }}</strong> | 
                            الراتب الأساسي: <strong class="text-primary"><span dir="ltr">{{ number_format($selectedEmployee->basic_salary, 2) }}</span> {{ $selectedEmployee->branch?->currency ?? 'EGP' }}</strong> | 
                            أجر الساعة: <strong class="text-info"><span dir="ltr">{{ number_format($stats['hourly_rate'], 2) }}</span> {{ $selectedEmployee->branch?->currency ?? 'EGP' }}</strong>
                        </p>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('admin.reports.employee-statement', array_merge(request()->all(), ['export' => 'excel'])) }}" class="btn btn-success text-white fw-bold shadow-sm d-print-none">
                            <i class="fa-solid fa-file-excel me-1"></i> تصدير Excel
                        </a>
                        <button onclick="window.print()" class="btn btn-primary text-white fw-bold shadow-sm d-print-none">
                            <i class="fa-solid fa-print me-1"></i> طباعة كشف الموظف
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- KPI Cards --}}
        <div class="col-md-3">
            <div class="card h-100 shadow-sm border-0 border-top border-3 border-success">
                <div class="card-body">
                    <small class="text-muted d-block mb-1">إجمالي أيام الإجازات (خلال الفترة)</small>
                    <h3 class="fw-bold text-success mb-2">{{ $stats['total_days'] }} يوم</h3>
                    <div class="small text-muted border-top pt-2">
                        <span>سنوية: <strong>{{ $stats['annual_days'] }}</strong></span> | 
                        <span>عارضة: <strong>{{ $stats['casual_days'] }}</strong></span> | 
                        <span>مرضية: <strong>{{ $stats['sick_days'] }}</strong></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card h-100 shadow-sm border-0 border-top border-3 border-info">
                <div class="card-body">
                    <small class="text-muted d-block mb-1">ساعات الاستئذان المعتمدة</small>
                    <h3 class="fw-bold text-info mb-2">{{ $stats['perm_hours'] }} ساعة</h3>
                    <div class="small text-muted border-top pt-2">
                        <span>مجاني: <strong>{{ min($stats['perm_hours'], $selectedEmployee->monthly_permission_hours_limit ?? 4) }} س</strong></span> | 
                        <span>خاضع للخصم: <strong class="text-danger">{{ $stats['excess_hours'] }} س</strong></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card h-100 shadow-sm border-0 border-top border-3 border-danger">
                <div class="card-body">
                    <small class="text-muted d-block mb-1">إجمالي الجزاءات الإدارية</small>
                    <h3 class="fw-bold text-danger mb-2"><span dir="ltr">{{ number_format($stats['penalties_cost'], 2) }}</span> {{ $selectedEmployee->branch?->currency ?? 'EGP' }}</h3>
                    <div class="small text-muted border-top pt-2">
                        <span>خصم أيام: <strong>{{ $stats['penalties_days'] }} يوم</strong></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card h-100 shadow-sm border-0 border-top border-3 border-dark">
                <div class="card-body">
                    <small class="text-muted d-block mb-1">إجمالي المبالغ الخاضعة للخصم</small>
                    <h3 class="fw-bold text-heading mb-2"><span dir="ltr">{{ number_format($stats['total_deductions_cost'], 2) }}</span> {{ $selectedEmployee->branch?->currency ?? 'EGP' }}</h3>
                    <div class="small text-muted border-top pt-2">
                        <span>خصم الجزاءات + الإجازات بدون أجر + الاستئذان</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Detailed Timeline Table --}}
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header border-bottom py-3">
                    <h6 class="card-title mb-0 fw-bold">
                        <i class="fa-solid fa-list-check me-2 text-primary"></i> السجل الزمني التفصيلي للموظف خلال الفترة
                    </h6>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>التاريخ</th>
                                <th>نوع البيان</th>
                                <th>التفاصيل والمقدار</th>
                                <th>السبب / البيان</th>
                                <th>الحالة</th>
                                <th class="text-end">قيمة الخصم الفعلي</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($timeline as $item)
                                <tr>
                                    <td><strong class="text-heading">{{ $item['date'] }}</strong></td>
                                    <td>{{ $item['title'] }}</td>
                                    <td><span class="badge bg-label-secondary">{{ $item['detail'] }}</span></td>
                                    <td>{{ $item['reason'] }}</td>
                                    <td><span class="badge {{ $item['badge'] }}">{{ $item['status'] }}</span></td>
                                    <td class="text-end">
                                        @if($item['cost'] > 0)
                                            <strong class="text-danger"><span dir="ltr">{{ number_format($item['cost'], 2) }}</span> {{ $selectedEmployee->branch?->currency ?? 'EGP' }}</strong>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">لا يوجد إجازات أو أذونات أو جزاءات مسجلة للموظف في هذه الفترة.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection
