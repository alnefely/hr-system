@extends('layouts.portal.app')
@section('title', __('portal.nav.payslips'))

@section('content')
@php($currency = $employee->branch?->currency ?? '')
<div class="card">
    <div class="card-header"><h6 class="mb-0 fw-bold"><i class="fa-solid fa-file-invoice-dollar me-2 text-success"></i>{{ __('portal.payslips.title') }}</h6></div>
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead>
                <tr>
                    <th>{{ __('portal.payslips.period') }}</th>
                    <th>{{ __('portal.payslips.basic') }}</th>
                    <th>{{ __('portal.payslips.additions') }}</th>
                    <th>{{ __('portal.payslips.deductions') }}</th>
                    <th>{{ __('portal.payslips.net') }}</th>
                    <th>{{ __('portal.payslips.paid_at') }}</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($items as $item)
                    <tr>
                        <td dir="ltr">{{ sprintf('%02d / %04d', $item->payroll?->month, $item->payroll?->year) }}</td>
                        <td dir="ltr">{{ number_format((float) $item->basic_salary, 2) }}</td>
                        <td dir="ltr" class="text-success">+{{ number_format((float) $item->allowances, 2) }}</td>
                        <td dir="ltr" class="text-danger">-{{ number_format((float) $item->deductions, 2) }}</td>
                        <td dir="ltr" class="fw-bold">{{ number_format((float) $item->net_salary, 2) }} {{ $currency }}</td>
                        <td dir="ltr" class="text-nowrap">{{ $item->paid_at?->format('Y-m-d') }}</td>
                        <td class="text-end">
                            <a href="{{ route('portal.payslips.show', $item->id) }}" target="_blank" class="btn btn-sm btn-label-primary">
                                <i class="fa-solid fa-print me-1"></i>{{ __('portal.payslips.view') }}
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">{{ __('portal.payslips.empty') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($items->hasPages())
        <div class="card-footer">{{ $items->links() }}</div>
    @endif
</div>
@endsection
