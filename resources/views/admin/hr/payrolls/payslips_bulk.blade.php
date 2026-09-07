<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('payroll.payslip.bulk_title') }} - {{ $payroll->title }}</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f6f9;
            color: #333;
        }
        .payslip-card {
            max-width: 800px;
            margin: 30px auto;
            background: #fff;
            padding: 35px;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0,0,0,0.06);
            border: 1px solid #e2e8f0;
            page-break-after: always;
            break-after: page;
        }
        @media print {
            body { background: #fff; }
            .payslip-card {
                box-shadow: none;
                border: none;
                margin: 0;
                padding: 20px;
                max-width: 100%;
                page-break-after: always;
                break-after: page;
            }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3 no-print mt-4" style="max-width: 800px; margin: 0 auto;">
        <div>
            <h5 class="mb-0 fw-bold">{{ __('payroll.payslip.bulk_title') }}</h5>
            <small class="text-muted">{{ __('payroll.fields.items_count', ['count' => $items->count()]) }}</small>
        </div>
        <div>
            <button onclick="window.print()" class="btn btn-primary">
                <i class="fa-solid fa-print me-1"></i> {{ __('payroll.payslip.print_all_btn', ['count' => $items->count()]) }}
            </button>
            <button onclick="window.close()" class="btn btn-secondary me-2">
                <i class="fa-solid fa-xmark me-1"></i> {{ __('global.cancel') }}
            </button>
        </div>
    </div>

    @forelse($items as $item)
        <div class="payslip-card">
            {{-- Payslip Header --}}
            <div class="d-flex justify-content-between align-items-center border-bottom pb-3 mb-4">
                <div>
                    <h3 class="fw-bold text-primary mb-1">{{ __('payroll.payslip.title') }}</h3>
                    <h6 class="text-muted mb-0">{{ $payroll->title }}</h6>
                </div>
                <div class="text-end">
                    <span class="badge bg-label-success fs-6 text-dark border p-2 mb-1 d-inline-block">
                        {{ __('payroll.fields.status') }}: {{ __('payroll.enums.' . $item->status) }}
                    </span>
                    <div class="small text-muted">{{ __('payroll.payslip.export_date') }}: {{ date('Y-m-d') }}</div>
                </div>
            </div>

            {{-- Employee Details Grid (No bg-light) --}}
            <div class="row g-3 border p-3 rounded mb-4">
                <div class="col-6"><strong>{{ __('payroll.payslip.employee_name') }}:</strong> {{ $item->employee?->name }}</div>
                <div class="col-6"><strong>{{ __('payroll.payslip.employee_code') }}:</strong> <span class="badge bg-secondary">{{ $item->employee?->employee_code }}</span></div>
                <div class="col-6"><strong>{{ __('payroll.payslip.job_title') }}:</strong> {{ $item->employee?->jobTitle?->title ?? '-' }}</div>
                <div class="col-6"><strong>{{ __('payroll.payslip.branch') }}:</strong> {{ $item->employee?->branch?->name ?? '-' }}</div>
                <div class="col-6"><strong>{{ __('payroll.payslip.payment_method') }}:</strong> {{ __('payroll.enums.' . $item->payment_method) }}</div>
                <div class="col-6"><strong>{{ __('payroll.payslip.bank_account') }}:</strong> <span dir="ltr">{{ $item->employee?->bank_account_number ?? '-' }}</span></div>
            </div>

            {{-- Detailed Salary Breakdown Table --}}
            <h5 class="fw-bold border-bottom pb-2 mb-3">{{ __('payroll.payslip.breakdown_title') }}</h5>
            <table class="table table-bordered align-middle mb-4">
                <thead>
                    <tr>
                        <th>{{ __('payroll.payslip.item') }}</th>
                        <th class="text-end" style="width: 30%">{{ __('payroll.payslip.amount') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="fw-bold">{{ __('payroll.payslip.basic_salary') }}</td>
                        <td class="text-end fw-bold"><span dir="ltr">{{ number_format($item->basic_salary, 2) }}</span></td>
                    </tr>

                    {{-- Allowance Items --}}
                    @if(is_array($item->allowance_details) && count($item->allowance_details) > 0)
                        @foreach($item->allowance_details as $al)
                            <tr>
                                <td class="text-success"><i class="fa-solid fa-plus text-success me-2"></i> {{ $al['title'] ?? $al['name'] ?? '' }}</td>
                                <td class="text-end text-success fw-bold">+ <span dir="ltr">{{ number_format($al['amount'] ?? 0, 2) }}</span></td>
                            </tr>
                        @endforeach
                    @elseif($item->allowances > 0)
                        <tr>
                            <td class="text-success"><i class="fa-solid fa-plus text-success me-2"></i> {{ __('payroll.payslip.total_allowances') }}</td>
                            <td class="text-end text-success fw-bold">+ <span dir="ltr">{{ number_format($item->allowances, 2) }}</span></td>
                        </tr>
                    @endif

                    {{-- Deduction Items --}}
                    @if(is_array($item->deduction_details) && count($item->deduction_details) > 0)
                        @foreach($item->deduction_details as $ded)
                            <tr>
                                <td class="text-danger"><i class="fa-solid fa-minus text-danger me-2"></i> {{ $ded['title'] ?? $ded['name'] ?? '' }}</td>
                                <td class="text-end text-danger fw-bold">- <span dir="ltr">{{ number_format($ded['amount'] ?? 0, 2) }}</span></td>
                            </tr>
                        @endforeach
                    @elseif($item->deductions > 0)
                        <tr>
                            <td class="text-danger"><i class="fa-solid fa-minus text-danger me-2"></i> {{ __('payroll.payslip.total_deductions') }}</td>
                            <td class="text-end text-danger fw-bold">- <span dir="ltr">{{ number_format($item->deductions, 2) }}</span></td>
                        </tr>
                    @endif

                    <tr class="table-primary fs-5">
                        <td class="fw-bold">{{ __('payroll.payslip.net_payable') }}</td>
                        <td class="text-end fw-bold"><span dir="ltr">{{ number_format($item->net_salary, 2) }}</span> {{ $payroll->branch?->currency ?? 'EGP' }}</td>
                    </tr>
                </tbody>
            </table>

            @if($item->notes)
                <div class="mb-4">
                    <strong>{{ __('payroll.fields.notes') }}:</strong> {{ $item->notes }}
                </div>
            @endif

            {{-- Signatures Footer --}}
            <div class="row text-center mt-5 pt-4 border-top">
                <div class="col-4">
                    <p class="fw-bold mb-4">{{ __('payroll.payslip.recipient_signature') }}</p>
                    <p class="text-muted">...........................</p>
                </div>
                <div class="col-4">
                    <p class="fw-bold mb-4">{{ __('payroll.payslip.accountant_signature') }}</p>
                    <p class="text-muted">...........................</p>
                </div>
                <div class="col-4">
                    <p class="fw-bold mb-4">{{ __('payroll.payslip.manager_signature') }}</p>
                    <p class="text-muted">...........................</p>
                </div>
            </div>
        </div>
    @empty
        <div class="alert alert-warning text-center my-5">
            {{ __('payroll.fields.no_items') }}
        </div>
    @endforelse
</div>

</body>
</html>
