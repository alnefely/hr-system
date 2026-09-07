@extends('layouts.admin.app')
@section('title', __('expense.create_title'))

@section('content')
<div class="row g-4 mb-4">
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-header border-bottom d-flex align-items-center justify-content-between">
                <h5 class="card-title mb-0">
                    <i class="fa-solid fa-plus me-2 text-primary"></i> {{ __('expense.create_title') }}
                </h5>
                <a href="{{ route('admin.expenses.index') }}" class="btn btn-label-secondary waves-effect">
                    <i class="fa-solid fa-arrow-right me-1"></i> {{ __('expense.actions.back_to_list') }}
                </a>
            </div>

            <div class="card-body pt-4">
                <form action="{{ route('admin.expenses.store') }}" method="POST">
                    @csrf
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <x-inputs.field name="title" label="{{ __('expense.fields.title') }}" required placeholder="مثال: فاتورة كهرباء شهر أغسطس / مصاريف مطبخ وضيافة" />
                        </div>

                        <div class="col-md-6">
                            <x-inputs.select2
                                name="expense_category_id"
                                label="{{ __('expense.fields.category') }}"
                                :data="$categories"
                                data_key="name"
                                data_value="id"
                                :value="old('expense_category_id')"
                                placeholder="اختر التصنيف..."
                                :required="true"
                            />
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">{{ __('expense.fields.amount') }} <span class="text-danger">*</span> <x-inputs.help :text="__('expense.hints.amount')" /></label>
                            {{-- input عادي مش x-inputs.field: المكوّن بيلف الحقل في
                                 div وده بيكسر تخطيط input-group ويخلي الحقل غير واضح --}}
                            <div class="input-group">
                                <input
                                    type="number"
                                    step="0.01"
                                    min="0.01"
                                    name="amount"
                                    id="amount"
                                    value="{{ old('amount') }}"
                                    class="form-control @error('amount') is-invalid @enderror"
                                    placeholder="0.00"
                                    dir="ltr"
                                    required />
                                <span class="input-group-text fw-semibold" id="currency-display">---</span>
                            </div>
                            @error('amount')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                            <input type="hidden" name="currency" id="currency-input" value="{{ old('currency') }}" />
                        </div>

                        <div class="col-md-4">
                            <x-inputs.field type="date" name="expense_date" label="{{ __('expense.fields.expense_date') }}" :value="old('expense_date', date('Y-m-d'))" required />
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">{{ __('expense.fields.payment_method') }} <span class="text-danger">*</span> <x-inputs.help :text="__('expense.hints.payment_method')" /></label>
                            <select name="payment_method" class="form-select select2" required>
                                <option value="cash" {{ old('payment_method') == 'cash' ? 'selected' : '' }}>{{ __('expense.enums.cash') }}</option>
                                <option value="bank_transfer" {{ old('payment_method') == 'bank_transfer' ? 'selected' : '' }}>{{ __('expense.enums.bank_transfer') }}</option>
                                <option value="card" {{ old('payment_method') == 'card' ? 'selected' : '' }}>{{ __('expense.enums.card') }}</option>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">{{ __('expense.fields.branch') }} <span class="text-danger">*</span> <x-inputs.help :text="__('expense.hints.branch')" /></label>
                            <select name="branch_id" id="branch-select" class="form-select select2" required>
                                <option value="">-- {{ __('expense.fields.branch') }} --</option>
                                @foreach($branches as $b)
                                    <option value="{{ $b->id }}" data-currency="{{ $b->currency ?? 'EGP' }}" {{ old('branch_id') == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">{{ __('expense.fields.employee') }} <x-inputs.help :text="__('expense.hints.employee')" /></label>
                            <select name="employee_id" class="form-select select2">
                                <option value="">{{ __('expense.fields.select_employee') }}</option>
                                @foreach($employees as $emp)
                                    <option value="{{ $emp->id }}" {{ old('employee_id') == $emp->id ? 'selected' : '' }}>{{ $emp->name }} ({{ $emp->employee_code }})</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-4">
                            <x-inputs.field name="invoice_number" label="{{ __('expense.fields.invoice_number') }}" placeholder="مثال: INV-2026-99" />
                        </div>

                        <div class="col-md-12">
                            <x-inputs.lfm
                                name="attachment"
                                label="{{ __('expense.fields.attachment') }}"
                                type="file"
                                :showClear="true"
                                :value="old('attachment')"
                            />
                        </div>

                        <div class="col-md-12">
                            <label class="form-label">{{ __('expense.fields.notes') }} <x-inputs.help :text="__('expense.hints.notes')" /></label>
                            <textarea name="notes" class="form-control" rows="3" placeholder="اكتب أي ملاحظات إضافية حول المصروف أو الفاتورة...">{{ old('notes') }}</textarea>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 border-top pt-3">
                        <a href="{{ route('admin.expenses.index') }}" class="btn btn-label-secondary">{{ __('global.cancel') }}</a>
                        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save me-1"></i> {{ __('global.save') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('js')
<script>
$(document).ready(function() {
    function updateCurrency() {
        var selected = $('#branch-select option:selected');
        var curr = selected.data('currency') || 'EGP';
        if ($('#branch-select').val()) {
            $('#currency-display').text(curr);
            $('#currency-input').val(curr);
        } else {
            $('#currency-display').text('---');
            $('#currency-input').val('');
        }
    }
    $('#branch-select').on('change', updateCurrency);
    updateCurrency();
});
</script>
@endpush
