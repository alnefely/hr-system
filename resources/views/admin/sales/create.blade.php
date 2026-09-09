@extends('layouts.admin.app')
@section('title', __('sale.create_title'))

@section('content')
<div class="row g-4 mb-4">
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-header border-bottom d-flex align-items-center justify-content-between">
                <h5 class="card-title mb-0">
                    <i class="fa-solid fa-plus me-2 text-primary"></i> {{ __('sale.create_title') }}
                </h5>
                <a href="{{ route('admin.sales.index') }}" class="btn btn-label-secondary waves-effect">
                    <i class="fa-solid fa-arrow-right me-1"></i> {{ __('sale.actions.back_to_list') }}
                </a>
            </div>

            <div class="card-body pt-4">
                <form action="{{ route('admin.sales.store') }}" method="POST" id="saleForm" enctype="multipart/form-data">
                    @csrf
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label">{{ __('sale.fields.invoice_number') }} <span class="text-danger">*</span> <x-inputs.help :text="__('sale.hints.invoice_number')" /></label>
                            <input type="text" name="invoice_number" class="form-control" value="{{ old('invoice_number', $nextInvoiceNumber) }}" required dir="ltr" />
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">{{ __('sale.fields.customer') }} <x-inputs.help :text="__('sale.hints.customer')" /></label>
                            <select name="customer_id" class="form-select select2">
                                <option value="">{{ __('sale.direct_customer') }}</option>
                                @foreach($customers as $c)
                                    <option value="{{ $c->id }}" {{ old('customer_id') == $c->id ? 'selected' : '' }}>{{ $c->name }} ({{ $c->phone ?? '-' }})</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">{{ __('sale.fields.branch') }} <span class="text-danger">*</span> <x-inputs.help :text="__('sale.hints.branch')" /></label>
                            <select name="branch_id" id="sale_branch_id" class="form-select select2" required>
                                @foreach($branches as $b)
                                    <option value="{{ $b->id }}" data-currency="{{ $b->currency }}" {{ old('branch_id') == $b->id ? 'selected' : '' }}>{{ $b->name }} ({{ $b->currency }})</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">{{ __('sale.fields.currency') }} <span class="text-danger">*</span> <x-inputs.help :text="__('sale.hints.currency')" /></label>
                            <select name="currency" id="sale_currency" class="form-select select2" required>
                                <option value="EGP" {{ old('currency', 'EGP') == 'EGP' ? 'selected' : '' }}>EGP - جنيه مصري</option>
                                <option value="USD" {{ old('currency') == 'USD' ? 'selected' : '' }}>USD - دولار أمريكي</option>
                                <option value="SAR" {{ old('currency') == 'SAR' ? 'selected' : '' }}>SAR - ريال سعودي</option>
                                <option value="EUR" {{ old('currency') == 'EUR' ? 'selected' : '' }}>EUR - يورو</option>
                                <option value="AED" {{ old('currency') == 'AED' ? 'selected' : '' }}>AED - درهم إماراتي</option>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">{{ __('sale.fields.sale_type') }} <span class="text-danger">*</span> <x-inputs.help :text="__('sale.hints.sale_type')" /></label>
                            <select name="sale_type" class="form-select select2" required>
                                <option value="app_service" {{ old('sale_type') == 'app_service' ? 'selected' : '' }}>{{ __('sale.type_enums.app_service') }}</option>
                                <option value="external_project" {{ old('sale_type') == 'external_project' ? 'selected' : '' }}>{{ __('sale.type_enums.external_project') }}</option>
                                <option value="direct_sale" {{ old('sale_type', 'direct_sale') == 'direct_sale' ? 'selected' : '' }}>{{ __('sale.type_enums.direct_sale') }}</option>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">{{ __('sale.fields.sales_channel') }} <x-inputs.help :text="__('sale.hints.sales_channel')" /></label>
                            <select name="sales_channel_id" class="form-select select2">
                                <option value="">اختر قناة البيع / المنصة...</option>
                                @foreach($salesChannels as $ch)
                                    <option value="{{ $ch->id }}" {{ old('sales_channel_id') == $ch->id ? 'selected' : '' }}>{{ $ch->name }} ({{ $ch->type }})</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">{{ __('sale.fields.sale_date') }} <span class="text-danger">*</span> <x-inputs.help :text="__('sale.hints.sale_date')" /></label>
                            <input type="date" name="sale_date" class="form-control" value="{{ old('sale_date', date('Y-m-d')) }}" required />
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">{{ __('sale.fields.payment_status') }} <span class="text-danger">*</span> <x-inputs.help :text="__('sale.hints.payment_status')" /></label>
                            <select name="payment_status" id="sale_payment_status" class="form-select select2" required>
                                <option value="paid" {{ old('payment_status') == 'paid' ? 'selected' : '' }}>{{ __('sale.statuses.paid') }}</option>
                                <option value="partially_paid" {{ old('payment_status') == 'partially_paid' ? 'selected' : '' }}>{{ __('sale.statuses.partially_paid') }}</option>
                                <option value="unpaid" {{ old('payment_status') == 'unpaid' ? 'selected' : '' }}>{{ __('sale.statuses.unpaid') }}</option>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">{{ __('sale.fields.payment_method') }} <span class="text-danger">*</span> <x-inputs.help :text="__('sale.hints.payment_method')" /></label>
                            <select name="payment_method" class="form-select select2" required>
                                <option value="cash" {{ old('payment_method') == 'cash' ? 'selected' : '' }}>{{ __('sale.payment_methods.cash') }}</option>
                                <option value="bank_transfer" {{ old('payment_method') == 'bank_transfer' ? 'selected' : '' }}>{{ __('sale.payment_methods.bank_transfer') }}</option>
                                <option value="card" {{ old('payment_method') == 'card' ? 'selected' : '' }}>{{ __('sale.payment_methods.card') }}</option>
                                <option value="cheque" {{ old('payment_method') == 'cheque' ? 'selected' : '' }}>{{ __('sale.payment_methods.cheque') }}</option>
                            </select>
                        </div>
                    </div>

                    {{-- Items Dynamic Table (No bg-light) --}}
                    <div class="card mb-4 border shadow-none">
                        <div class="card-header border-bottom d-flex justify-content-between align-items-center py-2 bg-transparent">
                            <h6 class="mb-0 fw-bold">
                                <i class="fa-solid fa-list-check me-2 text-primary"></i> بنود الفاتورة والخدمات المباعة
                            </h6>
                            <button type="button" class="btn btn-sm btn-label-primary waves-effect" id="addItemBtn">
                                <i class="fa-solid fa-plus me-1"></i> {{ __('sale.actions.add_item') }}
                            </button>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-bordered align-middle mb-0" id="itemsTable">
                                    <thead>
                                        <tr>
                                            <th style="width: 45%;">{{ __('sale.fields.item_name') }} <span class="text-danger">*</span></th>
                                            <th style="width: 15%;">{{ __('sale.fields.quantity') }} <span class="text-danger">*</span></th>
                                            <th style="width: 20%;">{{ __('sale.fields.unit_price') }} <span class="text-danger">*</span></th>
                                            <th style="width: 20%;">{{ __('sale.fields.total_price') }}</th>
                                            <th style="width: 50px;"></th>
                                        </tr>
                                    </thead>
                                    <tbody id="itemsBody">
                                        <tr class="item-row">
                                            <td>
                                                <input type="text" name="items[0][item_name]" class="form-control" required placeholder="اسم الخدمة أو المنتج المباع..." />
                                            </td>
                                            <td>
                                                <input type="number" step="1" min="1" name="items[0][quantity]" class="form-control item-qty" value="1" required dir="ltr" />
                                            </td>
                                            <td>
                                                <input type="number" step="0.01" min="0" name="items[0][unit_price]" class="form-control item-price" value="0.00" required dir="ltr" />
                                            </td>
                                            <td>
                                                <input type="text" class="form-control item-total" readonly value="0.00" dir="ltr" />
                                                <input type="hidden" name="items[0][total_price]" class="item-total-hidden" value="0.00" />
                                            </td>
                                            <td class="text-center">
                                                <button type="button" class="btn btn-sm btn-icon btn-label-danger remove-item-btn" title="حذف البند">
                                                    <i class="fa-solid fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    {{-- Invoice Totals Calculation Box --}}
                    <div class="row justify-content-end mb-4">
                        <div class="col-md-5">
                            <div class="card border shadow-none p-3">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="text-muted">{{ __('sale.fields.subtotal') }}:</span>
                                    <span class="fw-bold" id="subtotal_display" dir="ltr">0.00</span>
                                    <input type="hidden" name="subtotal" id="subtotal_input" value="0.00" />
                                </div>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="text-muted">{{ __('sale.fields.tax_amount') }}:</span>
                                    <div style="width: 130px;">
                                        <input type="number" step="0.01" min="0" name="tax_amount" id="tax_amount_input" class="form-control form-control-sm text-end" value="0.00" dir="ltr" />
                                    </div>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="text-muted">{{ __('sale.fields.discount_amount') }}:</span>
                                    <div style="width: 130px;">
                                        <input type="number" step="0.01" min="0" name="discount_amount" id="discount_amount_input" class="form-control form-control-sm text-end" value="0.00" dir="ltr" />
                                    </div>
                                </div>
                                <div class="d-flex justify-content-between align-items-center pt-2 border-top mb-3">
                                    <span class="fw-bold fs-6 text-dark">{{ __('sale.fields.total_amount') }}:</span>
                                    <h5 class="mb-0 text-primary fw-bold" id="total_amount_display" dir="ltr">0.00</h5>
                                    <input type="hidden" name="total_amount" id="total_amount_input" value="0.00" />
                                </div>

                                <div class="d-flex justify-content-between align-items-center mb-2" id="paid_wrapper">
                                    <span class="text-muted">{{ __('sale.fields.paid_amount') }}:</span>
                                    <div style="width: 130px;">
                                        <input type="number" step="0.01" min="0" name="paid_amount" id="paid_amount_input" class="form-control form-control-sm text-end" value="0.00" dir="ltr" />
                                    </div>
                                </div>
                                <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                                    <span class="text-muted">{{ __('sale.fields.due_amount') }}:</span>
                                    <span class="fw-bold text-danger" id="due_amount_display" dir="ltr">0.00</span>
                                    <input type="hidden" name="due_amount" id="due_amount_input" value="0.00" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <x-inputs.lfm
                                name="attachment"
                                :label="__('sale.fields.attachment')"
                                :tooltip="__('sale.hints.attachment')"
                                type="file"
                                :showClear="true"
                                :value="old('attachment')"
                            />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('sale.fields.notes') }} <x-inputs.help :text="__('sale.hints.notes')" /></label>
                            <textarea name="notes" class="form-control" rows="2" placeholder="ملاحظات وشروط الفاتورة...">{{ old('notes') }}</textarea>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 border-top pt-3">
                        <a href="{{ route('admin.sales.index') }}" class="btn btn-label-secondary waves-effect">{{ __('global.cancel') }}</a>
                        <button type="submit" class="btn btn-primary waves-effect waves-light">
                            <i class="fa-solid fa-check me-1"></i> {{ __('global.save') }}
                        </button>
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
    var rowIndex = 1;

    function recalculateInvoice() {
        var subtotal = 0;
        $('#itemsBody tr').each(function() {
            var qty = parseFloat($(this).find('.item-qty').val()) || 0;
            var price = parseFloat($(this).find('.item-price').val()) || 0;
            var lineTotal = qty * price;
            $(this).find('.item-total').val(lineTotal.toFixed(2));
            $(this).find('.item-total-hidden').val(lineTotal.toFixed(2));
            subtotal += lineTotal;
        });

        $('#subtotal_display').text(subtotal.toFixed(2));
        $('#subtotal_input').val(subtotal.toFixed(2));

        var tax = parseFloat($('#tax_amount_input').val()) || 0;
        var discount = parseFloat($('#discount_amount_input').val()) || 0;
        var total = Math.max(0, subtotal + tax - discount);

        $('#total_amount_display').text(total.toFixed(2));
        $('#total_amount_input').val(total.toFixed(2));

        var status = $('#sale_payment_status').val();
        var paid = parseFloat($('#paid_amount_input').val()) || 0;

        if (status === 'paid') {
            paid = total;
            $('#paid_amount_input').val(total.toFixed(2));
        } else if (status === 'unpaid') {
            paid = 0;
            $('#paid_amount_input').val('0.00');
        }

        var due = Math.max(0, total - paid);
        $('#due_amount_display').text(due.toFixed(2));
        $('#due_amount_input').val(due.toFixed(2));
    }

    $('#addItemBtn').on('click', function() {
        var tr = `
            <tr class="item-row">
                <td>
                    <input type="text" name="items[${rowIndex}][item_name]" class="form-control" required placeholder="اسم الخدمة أو المنتج..." />
                </td>
                <td>
                    <input type="number" step="1" min="1" name="items[${rowIndex}][quantity]" class="form-control item-qty" value="1" required dir="ltr" />
                </td>
                <td>
                    <input type="number" step="0.01" min="0" name="items[${rowIndex}][unit_price]" class="form-control item-price" value="0.00" required dir="ltr" />
                </td>
                <td>
                    <input type="text" class="form-control item-total" readonly value="0.00" dir="ltr" />
                    <input type="hidden" name="items[${rowIndex}][total_price]" class="item-total-hidden" value="0.00" />
                </td>
                <td class="text-center">
                    <button type="button" class="btn btn-sm btn-icon btn-label-danger remove-item-btn" title="حذف البند">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </td>
            </tr>
        `;
        $('#itemsBody').append(tr);
        rowIndex++;
    });

    $(document).on('click', '.remove-item-btn', function() {
        if ($('#itemsBody tr').length > 1) {
            $(this).closest('tr').remove();
            recalculateInvoice();
        } else {
            alert('يجب أن تحتوي الفاتورة على بند واحد على الأقل.');
        }
    });

    $(document).on('input change', '.item-qty, .item-price, #tax_amount_input, #discount_amount_input, #paid_amount_input', function() {
        recalculateInvoice();
    });

    $('#sale_payment_status').on('change', function() {
        var status = $(this).val();
        if (status === 'partially_paid') {
            $('#paid_amount_input').prop('readonly', false);
        } else if (status === 'paid') {
            $('#paid_amount_input').prop('readonly', true);
        } else {
            $('#paid_amount_input').prop('readonly', true);
        }
        recalculateInvoice();
    });

    $('#sale_branch_id').on('change', function() {
        var curr = $(this).find('option:selected').data('currency');
        if (curr) {
            $('#sale_currency').val(curr).trigger('change');
        }
    });

    recalculateInvoice();
});
</script>
@endpush
