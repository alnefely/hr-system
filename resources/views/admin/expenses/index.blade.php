@extends('layouts.admin.app')
@section('title', __('expense.title'))

@section('content')
{{-- Analytics Custody Balance Widgets (No bg-light) --}}

{{--
    بطاقات العهدة (المخصص والمتبقي) بتتعرض بس لما نكون على دورة شهر
    كاملة. مع نطاق تواريخ مخصص بتبقى مضلّلة: العهدة دورتها شهرية بينما
    المنصرف بيتحسب على الفترة المختارة، فطرحهم من بعض بيدي رقم مالوش معنى.
--}}
<div class="row g-4 mb-4">
    @unless($usingCustomRange)
    <div class="col-sm-6 col-xl-4">
        <div class="card h-100 border-start border-primary border-4 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <div class="avatar avatar-md rounded bg-label-primary d-flex align-items-center justify-content-center">
                        <i class="fa-solid fa-vault fs-4"></i>
                    </div>
                    <span class="badge bg-label-primary" data-bs-toggle="tooltip" title="دورة شهر العهدة المحاسبية">{{ $month }}/{{ $year }}</span>
                </div>

                @if($selectedBranch)
                    <h4 class="mb-1 text-primary fw-bold">
                        <span dir="ltr">{{ number_format($allocatedBalance, 2) }}</span> 
                        <small class="fs-6">{{ $branchCurrency }}</small>
                    </h4>
                @else
                    @if(count($allocatedByCurrency) > 1)
                        <div class="mb-2">
                            <small class="text-muted d-block mb-1 fw-bold">العهدة المخصصة بالعملات:</small>
                            <div class="d-flex flex-wrap gap-2">
                                @foreach($allocatedByCurrency as $curr => $amt)
                                    <span class="badge bg-label-primary fs-6 px-2 py-1" data-bs-toggle="tooltip" title="العهدة المخصصة لعملة {{ $curr }}">
                                        <strong dir="ltr">{{ number_format($amt, 2) }}</strong> {{ $curr }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    @elseif(count($allocatedByCurrency) == 1)
                        @php $firstC = array_key_first($allocatedByCurrency); @endphp
                        <h4 class="mb-1 text-primary fw-bold">
                            <span dir="ltr">{{ number_format($allocatedByCurrency[$firstC], 2) }}</span> 
                            <small class="fs-6">{{ $firstC }}</small>
                        </h4>
                    @else
                        <h4 class="mb-1 text-primary fw-bold"><span dir="ltr">0.00</span> <small class="fs-6">{{ $branchCurrency }}</small></h4>
                    @endif
                @endif

                <p class="mb-0 text-muted small" data-bs-toggle="tooltip" title="الرصيد المخصص للعهدة في هذا الشهر شاملاً أي مبالغ مرحلة من الشهر السابق">
                    {{ __('expense.stats.allocated_balance', ['month' => $month . '/' . $year]) }}
                </p>
                @if($custodyBalance && $custodyBalance->carried_over_amount > 0)
                    <small class="text-success d-block mt-1" data-bs-toggle="tooltip" title="فائض مرحل تلقائياً من إغلاق الشهر السابق"><i class="fa-solid fa-arrow-right-to-bracket me-1"></i> يتضمن مرحل: <span dir="ltr">{{ number_format($custodyBalance->carried_over_amount, 2) }}</span> {{ $branchCurrency }}</small>
                @endif

                <a href="#" class="small text-info d-inline-block mt-2" data-bs-toggle="modal" data-bs-target="#carryExplainerModal">
                    <i class="fa-solid fa-circle-info me-1"></i>{{ __('expense.carry.how') }}
                </a>
            </div>
        </div>
    </div>

    @endunless

    <div class="{{ $usingCustomRange ? 'col-sm-6 col-xl-6' : 'col-sm-6 col-xl-4' }}">
        <div class="card h-100 border-start border-warning border-4 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <div class="avatar avatar-md rounded bg-label-warning d-flex align-items-center justify-content-center">
                        <i class="fa-solid fa-hand-holding-dollar fs-4"></i>
                    </div>
                    <span class="badge bg-label-warning me-1" data-bs-toggle="tooltip" title="طريقة الدفع المحددة في الفلتر">{{ request('payment_method') ? __('expense.enums.' . request('payment_method')) : 'كافة طرق الصرف' }}</span>
                </div>
                
                @if(count($expensesByCurrency) > 1)
                    <div class="mb-2">
                        <small class="text-muted d-block mb-1 fw-bold">إجمالي المنصرف بالعملات:</small>
                        <div class="d-flex flex-wrap gap-2">
                            @foreach($expensesByCurrency as $curr => $totalAmount)
                                <span class="badge bg-label-warning fs-6 px-2 py-1" data-bs-toggle="tooltip" title="إجمالي المصروفات المنصرفة بعملة {{ $curr }}">
                                    <strong dir="ltr">{{ number_format($totalAmount, 2) }}</strong> {{ $curr }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                @elseif(count($expensesByCurrency) == 1)
                    @php $firstCurr = array_key_first($expensesByCurrency); @endphp
                    <h4 class="mb-1 text-warning fw-bold"><span dir="ltr">{{ number_format($expensesByCurrency[$firstCurr], 2) }}</span> <small class="fs-6">{{ $firstCurr }}</small></h4>
                @else
                    <h4 class="mb-1 text-warning fw-bold"><span dir="ltr">0.00</span> <small class="fs-6">{{ $branchCurrency }}</small></h4>
                @endif

                <p class="mb-0 text-muted small fw-semibold" data-bs-toggle="tooltip" title="مجموع كافة الفواتير والمصاريف التشغيلية المنصرفة خلال الفترة المحددة">
                    <i class="fa-solid fa-calendar-check text-warning me-1"></i>
                    إجمالي المنصرف من العهدة
                    <span class="text-heading dir-ltr">({{ $spentPeriodLabel }})</span>
                </p>
            </div>
        </div>
    </div>

    @if($usingCustomRange)
        {{-- مع نطاق مخصص: بطاقة عدد العمليات بدل المتبقي المضلّل --}}
        <div class="col-sm-6 col-xl-6">
            <div class="card h-100 border-start border-info border-4 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="avatar avatar-md rounded bg-label-info d-flex align-items-center justify-content-center">
                            <i class="fa-solid fa-filter-circle-dollar fs-4"></i>
                        </div>
                        <span class="badge bg-label-info" data-bs-toggle="tooltip"
                              title="النتائج مفلترة بنطاق تواريخ مخصص، مش بدورة شهر كاملة">
                            نطاق مخصص
                        </span>
                    </div>

                    <h4 class="mb-1 text-info fw-bold">
                        <span dir="ltr">{{ number_format($expensesCount) }}</span>
                        <small class="fs-6">عملية</small>
                    </h4>

                    <p class="mb-0 text-muted small fw-semibold">
                        <i class="fa-solid fa-list-check text-info me-1"></i>
                        عدد المصروفات في الفترة
                        <span class="text-heading dir-ltr">({{ $spentPeriodLabel }})</span>
                    </p>

                    <small class="text-warning d-block mt-2">
                        <i class="fa-solid fa-circle-info me-1"></i>
                        بطاقات رصيد العهدة مخفية — ارجع لدورة الشهر لعرضها.
                    </small>
                </div>
            </div>
        </div>
    @else
    <div class="col-sm-6 col-xl-4">
        <div class="card h-100 border-start border-{{ $remainingBalance >= 0 ? 'success' : 'danger' }} border-4 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <div class="avatar avatar-md rounded bg-label-{{ $remainingBalance >= 0 ? 'success' : 'danger' }} d-flex align-items-center justify-content-center">
                        <i class="fa-solid fa-scale-balanced fs-4"></i>
                    </div>
                    <span class="badge bg-label-{{ $remainingBalance >= 0 ? 'success' : 'danger' }}" data-bs-toggle="tooltip" title="{{ $remainingBalance >= 0 ? 'يوجد فائض في العهدة المالية' : 'يوجد عجز يستوجب تعزيز العهدة' }}">
                        {{ $remainingBalance >= 0 ? 'فائض عهدة' : 'عجز في العهدة' }}
                    </span>
                </div>

                @if($selectedBranch)
                    <h4 class="mb-1 text-{{ $remainingBalance >= 0 ? 'success' : 'danger' }} fw-bold">
                        <span dir="ltr">{{ $remainingBalance >= 0 ? '+' : '' }}{{ number_format($remainingBalance, 2) }}</span> 
                        <small class="fs-6">{{ $branchCurrency }}</small>
                    </h4>
                @else
                    @if(count($remainingByCurrency) > 1)
                        <div class="mb-2">
                            <small class="text-muted d-block mb-1 fw-bold">الرصيد المتبقي حسب كل عملة:</small>
                            <div class="d-flex flex-wrap gap-2">
                                @foreach($remainingByCurrency as $curr => $rem)
                                    <span class="badge bg-label-{{ $rem >= 0 ? 'success' : 'danger' }} fs-6 px-2 py-1" data-bs-toggle="tooltip" title="{{ $rem >= 0 ? 'فائض عهدة' : 'عجز في العهدة' }}">
                                        <strong dir="ltr">{{ $rem >= 0 ? '+' : '' }}{{ number_format($rem, 2) }}</strong> {{ $curr }}
                                        <small class="ms-1">({{ $rem >= 0 ? 'فائض' : 'عجز' }})</small>
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    @elseif(count($remainingByCurrency) == 1)
                        @php 
                            $firstRemCurr = array_key_first($remainingByCurrency); 
                            $firstRem = $remainingByCurrency[$firstRemCurr]; 
                        @endphp
                        <h4 class="mb-1 text-{{ $firstRem >= 0 ? 'success' : 'danger' }} fw-bold">
                            <span dir="ltr">{{ $firstRem >= 0 ? '+' : '' }}{{ number_format($firstRem, 2) }}</span> 
                            <small class="fs-6">{{ $firstRemCurr }}</small>
                        </h4>
                    @else
                        <h4 class="mb-1 text-success fw-bold"><span dir="ltr">0.00</span> <small class="fs-6">{{ $branchCurrency }}</small></h4>
                    @endif
                @endif

                <p class="mb-0 text-muted small fw-semibold" data-bs-toggle="tooltip" title="معادلة الحساب: الرصيد المخصص للعهدة - إجمالي المنصرف الفعلي لكل عملة">
                    <i class="fa-solid fa-wallet me-1"></i>
                    {{ __('expense.stats.remaining_balance') }}
                    <span class="text-heading">({{ $spentPeriodLabel }})</span>
                </p>
            </div>
        </div>
    </div>

    @endif

    {{-- Multi-Currency Summary Table when more than 1 currency exists --}}
    @if(count($allCurrencies) > 1 && !$usingCustomRange)
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header py-2 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <span class="fw-bold text-heading"><i class="fa-solid fa-coins text-warning me-2"></i> ملخص موازين العهدة والمصروفات حسب العملات:</span>
                    <span class="badge bg-label-secondary">{{ count($allCurrencies) }} عملات نشطة</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered align-middle mb-0 text-center">
                        <thead class="table-dark">
                            <tr>
                                <th>العملة</th>
                                <th>العهدة المخصصة</th>
                                <th>إجمالي المنصرف</th>
                                <th>الرصيد المتبقي</th>
                                <th>الحالة</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($allCurrencies as $c)
                                @php
                                    $cAlloc = $allocatedByCurrency[$c] ?? 0.00;
                                    $cSpent = $expensesByCurrency[$c] ?? 0.00;
                                    $cRem   = $remainingByCurrency[$c] ?? 0.00;
                                @endphp
                                <tr>
                                    <td><strong class="badge bg-label-primary fs-6">{{ $c }}</strong></td>
                                    <td class="fw-bold text-primary"><span dir="ltr">{{ number_format($cAlloc, 2) }}</span> {{ $c }}</td>
                                    <td class="fw-bold text-warning"><span dir="ltr">{{ number_format($cSpent, 2) }}</span> {{ $c }}</td>
                                    <td class="fw-bold text-{{ $cRem >= 0 ? 'success' : 'danger' }}">
                                        <span dir="ltr">{{ $cRem >= 0 ? '+' : '' }}{{ number_format($cRem, 2) }}</span> {{ $c }}
                                    </td>
                                    <td>
                                        @if($cRem > 0)
                                            <span class="badge bg-label-success">فائض عهدة</span>
                                        @elseif($cRem < 0)
                                            <span class="badge bg-label-danger">عجز يستوجب التعزيز</span>
                                        @else
                                            <span class="badge bg-label-secondary">متوازن تماماً</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

</div>

<div class="card mb-4 shadow-sm">
    <div class="card-header d-flex justify-content-between align-items-center border-bottom flex-wrap gap-2">
        <h5 class="card-title mb-0">
            <i class="fa-solid fa-file-invoice-dollar text-primary me-2"></i> {{ __('expense.title') }}
            @if($selectedBranch)
                <span class="badge bg-label-primary fs-6 ms-2">{{ $selectedBranch->name }} ({{ $selectedBranch->currency }})</span>
            @endif
        </h5>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            @if(admin_can('expense_trash'))
                <a href="{{ route('admin.expenses.trash') }}" class="btn btn-label-danger waves-effect">
                    <i class="fa-solid fa-trash me-1"></i> {{ __('expense.trash') }}
                    @if(isset($trashCount) && $trashCount > 0)
                        <span class="badge bg-danger ms-1">{{ $trashCount }}</span>
                    @endif
                </a>
            @endif

            @if(admin_can('expense_balance_manage'))
                <button type="button" class="btn btn-label-success waves-effect" data-bs-toggle="modal" data-bs-target="#manageCustodyBalanceModal">
                    <i class="fa-solid fa-coins me-1"></i> {{ __('expense.actions.manage_balance') }}
                </button>
            @endif
            @if(admin_can('expense_category_show'))
                <a href="{{ route('admin.expense-categories.index') }}" class="btn btn-label-secondary waves-effect">
                    <i class="fa-solid fa-tags me-1"></i> {{ __('expense_category.title') }}
                </a>
            @endif
            <a href="{{ route('admin.expenses.index', array_merge(request()->all(), ['export' => 'excel'])) }}" class="btn btn-label-success waves-effect" title="تصدير السجل إلى ملف Excel">
                <i class="fa-solid fa-file-excel me-1"></i> تصدير Excel
            </a>
            @if(admin_can('expense_create'))
                <a href="{{ route('admin.expenses.create') }}" class="btn btn-primary waves-effect waves-light">
                    <i class="fa-solid fa-plus me-1"></i> {{ __('expense.actions.add_new') }}
                </a>
            @endif
        </div>
    </div>

    {{-- Filter Bar (No bg-light) --}}
    <div class="card-body py-3 border-bottom bg-transparent">

        {{-- شريط الشرح: يوضّح الفرق بين دورة العهدة ونطاق التواريخ --}}
        <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
            <h6 class="mb-0 fw-bold text-heading">
                <i class="fa-solid fa-sliders text-primary me-1"></i>
                فلترة المصروفات
                <a href="javascript:void(0);"
                   class="text-decoration-none ms-1"
                   data-bs-toggle="popover"
                   data-bs-trigger="hover focus"
                   data-bs-placement="bottom"
                   data-bs-html="true"
                   data-bs-custom-class="expense-help-popover"
                   title="<i class='fa-solid fa-circle-question me-1'></i> إزاي تستخدم الفلاتر؟"
                   data-bs-content="@include('admin.expenses.partials.filters-help')">
                    <i class="fa-solid fa-circle-question text-info"></i>
                </a>
            </h6>

            @if($usingCustomRange)
                <span class="badge bg-label-warning">
                    <i class="fa-solid fa-triangle-exclamation me-1"></i>
                    {{ __('expense.filters.multi_month_notice') }}
                </span>
            @endif
        </div>

        <form action="{{ route('admin.expenses.index') }}" method="GET" class="row g-3 align-items-end">
            <div class="col-12 col-sm-6 col-lg-2">
                <label for="expense_filter_branch_id" class="form-label text-muted small mb-1">{{ __('expense.fields.branch') }}</label>
                <select id="expense_filter_branch_id" name="branch_id" class="form-select select2">
                    <option value="">{{ __('expense.filters.all_branches') }}</option>
                    @foreach($branches as $b)
                        <option value="{{ $b->id }}" {{ request('branch_id') == $b->id ? 'selected' : '' }}>{{ $b->name }} ({{ $b->currency }})</option>
                    @endforeach
                </select>
            </div>

            <div class="col-12 col-sm-6 col-lg-2">
                <label for="expense_filter_expense_category_id" class="form-label text-muted small mb-1">{{ __('expense.fields.category') }}</label>
                <select id="expense_filter_expense_category_id" name="expense_category_id" class="form-select select2">
                    <option value="">{{ __('expense.filters.all_categories') }}</option>
                    @foreach($categories as $c)
                        <option value="{{ $c->id }}" {{ request('expense_category_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-12 col-sm-6 col-lg-2">
                <label for="expense_filter_payment_method" class="form-label text-muted small mb-1">{{ __('expense.fields.payment_method') }}</label>
                <select id="expense_filter_payment_method" name="payment_method" class="form-select select2">
                    <option value="">{{ __('expense.filters.all_methods') }}</option>
                    @foreach (['cash', 'bank_transfer', 'card'] as $methodKey)
                        <option value="{{ $methodKey }}" {{ request('payment_method') === $methodKey ? 'selected' : '' }}>
                            {{ __('expense.enums.' . $methodKey) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-12 col-lg-4">
                <label for="expense_filter_date_from" class="form-label text-muted small mb-1">
                    {{ __('expense.filters.period') }}
                    <x-inputs.help :text="__('expense.hints.period')" />
                </label>
                <div class="input-group">
                    <input type="date" name="date_from" id="expense_filter_date_from" class="form-control px-1" value="{{ $dateFrom }}"
                           aria-label="{{ __('expense.filters.period') }} ({{ __('global.export.from') }})" />
                    <input type="date" name="date_to" id="expense_filter_date_to" class="form-control px-1" value="{{ $dateTo }}"
                           aria-label="{{ __('expense.filters.period') }} ({{ __('global.export.to') }})" />
                </div>
            </div>

            <div class="col-12 col-sm-6 col-lg-2 d-flex gap-1">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fa-solid fa-filter me-1"></i> تصفية
                </button>
                <a href="{{ route('admin.expenses.index') }}" class="btn btn-outline-secondary" title="{{ __('global.reset_filter') }}" aria-label="{{ __('global.reset_filter') }}">
                    <i class="fa-solid fa-rotate" aria-hidden="true"></i>
                </a>
            </div>
        </form>
    </div>

    <div class="card-body pt-4">
        @php
            // القيم المتاحة فعلاً — البحث بالاسم زي ما هو مخزّن
            $categoryOptions = $categories->pluck('name', 'name')->all();
            $branchOptions   = $branches->pluck('name', 'name')->all();

            $paymentMethodOptions = collect(['cash', 'bank_transfer', 'card'])
                ->mapWithKeys(fn($m) => [$m => __('expense.enums.' . $m)])->all();
            $columns = [
                ['data' => 'id', 'name' => 'id'],
                ['data' => 'title', 'name' => 'title'],
                ['data' => 'category_name', 'name' => 'category.name', 'searchType' => 'select', 'searchOptions' => $categoryOptions],
                ['data' => 'branch_name', 'name' => 'branch.name', 'searchType' => 'select', 'searchOptions' => $branchOptions],
                ['data' => 'employee_name', 'name' => 'employee.name'],
                ['data' => 'formatted_amount', 'name' => 'amount'],
                ['data' => 'formatted_date', 'name' => 'expense_date', 'searchType' => 'date'],
                ['data' => 'payment_method', 'name' => 'payment_method', 'searchType' => 'select', 'searchOptions' => $paymentMethodOptions],
                ['data' => 'actions', 'name' => 'actions', 'orderable' => false, 'searchable' => false],
            ];
        @endphp

        <x-dataTable.table
            :columns="[
                __('global.id'),
                __('expense.fields.title'),
                __('expense.fields.category'),
                __('expense.fields.branch'),
                __('expense.fields.employee'),
                __('expense.fields.amount'),
                __('expense.fields.expense_date'),
                __('expense.fields.payment_method'),
                __('global.actions'),
            ]"
            :fields="$columns"
            :url="route('admin.expenses.index', request()->query())"
        />
    </div>
</div>

{{-- Modal: Manage Custody Balance (True Multi-Currency) --}}
@if(admin_can('expense_balance_manage'))
    <div class="modal fade" id="manageCustodyBalanceModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form action="{{ route('admin.expenses.update-balance') }}" method="POST" id="manageCustodyBalanceForm">
                    @csrf
                    <div class="modal-header border-bottom">
                        <h5 class="modal-title fw-bold text-success">
                            <i class="fa-solid fa-coins me-2"></i> {{ __('expense.actions.manage_balance') }}
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body pt-4">
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label">السنة <span class="text-danger">*</span></label>
                                <input type="number" name="year" id="custody_modal_year" class="form-control" value="{{ $year }}" required />
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">الشهر <span class="text-danger">*</span></label>
                                <input type="number" name="month" id="custody_modal_month" class="form-control" min="1" max="12" value="{{ $month }}" required />
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">{{ __('expense.fields.branch') }} <span class="text-danger">* (إجباري لتحديد عملة العهدة)</span></label>
                            <select name="branch_id" id="custody_modal_branch_select" class="form-select select2" required>
                                <option value="">-- اختر الفرع --</option>
                                @foreach($branches as $b)
                                    <option value="{{ $b->id }}" data-currency="{{ $b->currency ?? 'EGP' }}" {{ (request('branch_id') == $b->id) ? 'selected' : '' }}>
                                        {{ $b->name }} (عملة: {{ $b->currency ?? 'EGP' }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">
                                {{ __('expense.fields.allocated_amount') }} 
                                (<span class="badge bg-label-primary" id="custody_modal_curr_badge">{{ $branchCurrency }}</span>) 
                                <span class="text-danger">*</span>
                            </label>
                            <input type="number" step="0.01" min="0" name="allocated_amount" id="custody_modal_allocated_input" class="form-control" value="{{ $custodyBalance?->allocated_amount ?? 0 }}" required placeholder="0.00" dir="ltr" />
                        </div>

                        <div id="custody_modal_carried_alert" class="alert alert-info py-2 mb-3 {{ ($custodyBalance && $custodyBalance->carried_over_amount > 0) ? '' : 'd-none' }}">
                            <i class="fa-solid fa-info-circle me-1"></i> المبلغ المرحّل من الشهر السابق: 
                            <strong><span dir="ltr" id="custody_modal_carried_val">{{ number_format($custodyBalance?->carried_over_amount ?? 0, 2) }}</span> <span id="custody_modal_carried_curr">{{ $branchCurrency }}</span></strong>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">{{ __('expense.fields.notes') }}</label>
                            <textarea name="notes" id="custody_modal_notes" class="form-control" rows="2" placeholder="أدخل أي ملاحظات تخص ميزانية العهدة...">{{ $custodyBalance?->notes }}</textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">{{ __('global.cancel') }}</button>
                        <button type="submit" class="btn btn-success"><i class="fa-solid fa-save me-1"></i> {{ __('global.save') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>


@endif

    {{--
        شرح الترحيل التلقائي بأرقام الشهر المعروض — بيفتح من الرابط
        اللي في بطاقة الرصيد المخصص.

        الأرقام من carryBreakdown (كل مصروفات الشهر) مش من البطاقات —
        البطاقات بتتأثر بالفلاتر، والترحيل لأ.
    --}}
    @unless($usingCustomRange)
        @php
            $prevLabel = \Carbon\Carbon::create($year, $month, 1)->subMonth()->format('m/Y');
            $nextLabel = \Carbon\Carbon::create($year, $month, 1)->addMonth()->format('m/Y');
        @endphp
        <div class="modal fade" id="carryExplainerModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header border-bottom">
                        <h5 class="modal-title fw-bold text-info">
                            <i class="fa-solid fa-share me-2"></i>{{ __('expense.carry.title') }}
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body pt-4">
                        <p class="text-muted mb-3">{{ __('expense.carry.intro') }}</p>

                    @forelse ($carryBreakdown as $currency => $row)
                        <div class="d-flex flex-wrap align-items-center gap-2 small mb-2">
                            <span class="badge bg-label-primary">
                                {{ __('expense.carry.allocated') }}
                                <strong dir="ltr">{{ number_format($row['allocated'], 2) }}</strong>
                            </span>
                            <span class="text-muted">+</span>
                            <span class="badge bg-label-info">
                                {{ __('expense.carry.carried_in', ['month' => $prevLabel]) }}
                                <strong dir="ltr">{{ number_format($row['carried'], 2) }}</strong>
                            </span>
                            <span class="text-muted">=</span>
                            <span class="badge bg-label-secondary">
                                {{ __('expense.carry.total') }}
                                <strong dir="ltr">{{ number_format($row['total'], 2) }}</strong>
                            </span>
                            <span class="text-muted">−</span>
                            <span class="badge bg-label-warning">
                                {{ __('expense.carry.spent') }}
                                <strong dir="ltr">{{ number_format($row['spent'], 2) }}</strong>
                            </span>
                            <span class="text-muted">=</span>
                            <span class="badge bg-label-{{ $row['remaining'] > 0 ? 'success' : ($row['remaining'] < 0 ? 'danger' : 'secondary') }}">
                                {{ __('expense.carry.remaining') }}
                                <strong dir="ltr">{{ number_format($row['remaining'], 2) }}</strong>
                                {{ $currency }}
                            </span>

                            <i class="fa-solid {{ app()->getLocale() === 'ar' ? 'fa-arrow-left' : 'fa-arrow-right' }} text-muted mx-1"></i>

                            @if ($row['remaining'] > 0)
                                <span class="text-success fw-semibold">
                                    {{ __('expense.carry.goes_to', ['month' => $nextLabel]) }}
                                </span>
                            @elseif ($row['remaining'] < 0)
                                <span class="text-danger fw-semibold">
                                    {{ __('expense.carry.deficit', ['month' => $nextLabel]) }}
                                </span>
                            @else
                                <span class="text-muted">{{ __('expense.carry.nothing') }}</span>
                            @endif
                        </div>
                    @empty
                        <p class="small text-muted mb-2">
                            <i class="fa-solid fa-circle-info me-1"></i>{{ __('expense.carry.no_balance') }}
                        </p>
                    @endforelse

                    <ul class="small text-muted mb-0 ps-3">
                        <li>{{ __('expense.carry.rule_all_expenses') }}</li>
                        <li>{{ __('expense.carry.rule_live') }}</li>
                        <li>{{ __('expense.carry.rule_branch') }}</li>
                    </ul>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">{{ __('global.close') }}</button>
                    </div>
                </div>
            </div>
        </div>
    @endunless
@endsection

@push('js')
<script>
$(document).ready(function() {
    function loadCustodyDetailsForModal() {
        var branchId = $('#custody_modal_branch_select').val();
        var year = $('#custody_modal_year').val();
        var month = $('#custody_modal_month').val();
        var selectedOpt = $('#custody_modal_branch_select option:selected');
        var curr = selectedOpt.data('currency') || 'EGP';

        $('#custody_modal_curr_badge').text(curr);
        $('#custody_modal_carried_curr').text(curr);

        if (!branchId) return;

        $.ajax({
            url: "{{ route('admin.expenses.get-custody-balance') }}",
            method: 'GET',
            data: {
                branch_id: branchId,
                year: year,
                month: month
            },
            success: function(res) {
                if (res.success) {
                    $('#custody_modal_curr_badge').text(res.currency);
                    $('#custody_modal_carried_curr').text(res.currency);
                    $('#custody_modal_allocated_input').val(res.allocated_amount);
                    $('#custody_modal_notes').val(res.notes);

                    if (res.carried_over_amount > 0) {
                        $('#custody_modal_carried_val').text(parseFloat(res.carried_over_amount).toFixed(2));
                        $('#custody_modal_carried_alert').removeClass('d-none');
                    } else {
                        $('#custody_modal_carried_alert').addClass('d-none');
                    }
                }
            }
        });
    }

    $('#custody_modal_branch_select').on('change', loadCustodyDetailsForModal);
    $('#custody_modal_year, #custody_modal_month').on('change', loadCustodyDetailsForModal);
});
</script>
@endpush
