<?php

namespace App\Http\Requests\Admin;

use App\Models\Sale;
use App\Rules\AccessibleBranch;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * إعادة حساب المبالغ على السيرفر.
     *
     * إجمالي البند والإجمالي الفرعي والإجمالي النهائي كانوا بييجوا جاهزين
     * من JavaScript ويتحفظوا زي ما هم — أي تعديل في الطلب (أو باج في
     * الواجهة) كان بيسجّل فاتورة أرقامها مش متسقة: بنود مجموعها 1000
     * وإجمالي 100. دلوقتي الكمية × السعر هي المصدر.
     *
     * لو مفيش بنود باسم (فاتورة بمبلغ مباشر) بنحترم الإجمالي الفرعي المُرسل.
     */
    protected function prepareForValidation(): void
    {
        $items = is_array($this->input('items')) ? $this->input('items') : [];
        $hasNamedItems = false;
        $subtotal = 0.0;

        foreach ($items as $key => $item) {
            if (! is_array($item)) {
                continue;
            }

            $quantity = is_numeric($item['quantity'] ?? null) ? (float) $item['quantity'] : 1.0;
            $price    = is_numeric($item['unit_price'] ?? null) ? (float) $item['unit_price'] : 0.0;
            $line     = round($quantity * $price, 2);

            $items[$key]['total_price'] = $line;

            if (! empty($item['item_name'])) {
                $hasNamedItems = true;
                $subtotal += $line;
            }
        }

        $subtotal = $hasNamedItems
            ? round($subtotal, 2)
            : (float) (is_numeric($this->input('subtotal')) ? $this->input('subtotal') : 0);

        $tax      = is_numeric($this->input('tax_amount')) ? (float) $this->input('tax_amount') : 0.0;
        $discount = is_numeric($this->input('discount_amount')) ? (float) $this->input('discount_amount') : 0.0;

        $this->merge([
            'items'        => $items ?: $this->input('items'),
            'subtotal'     => $subtotal,
            'total_amount' => max(0, round($subtotal + $tax - $discount, 2)),
        ]);
    }

    /**
     * تعديل الفاتورة مايقدرش ينزّل الإجمالي تحت المدفوع فعلاً —
     * وإلا recalculatePayments() كانت بتطلّع متبقي صفر ومبلغ مدفوع
     * أكبر من الفاتورة نفسها (فلوس زيادة مش مسنودة بأي بند).
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                $sale = $this->route('sale');

                if (! $sale instanceof Sale || $validator->errors()->isNotEmpty()) {
                    return;
                }

                $paid  = round((float) $sale->payments()->sum('amount'), 2);
                $total = (float) $this->input('total_amount');

                if ($total + 0.001 < $paid) {
                    $validator->errors()->add('total_amount', __('sale.messages.total_below_paid', [
                        'total' => number_format($total, 2),
                        'paid'  => number_format($paid, 2),
                    ]));
                }
            },
        ];
    }

    public function rules(): array
    {
        $saleId = $this->route('sale')?->id ?? $this->route('sale');

        return [
            'invoice_number'   => ['required', 'string', 'max:50', Rule::unique('sales', 'invoice_number')->ignore($saleId)],
            'customer_id'      => ['nullable', 'exists:customers,id'],
            'sale_type'        => ['required', 'in:external_project,app_service,direct_sale'],
            'sales_channel_id' => ['nullable', 'exists:sales_channels,id'],
            'sales_channel'    => ['nullable', 'string', 'max:100'],
            'branch_id'        => ['required', 'exists:branches,id', new AccessibleBranch],
            'sale_date'        => ['required', 'date'],
            'subtotal'         => ['required', 'numeric', 'min:0'],
            'tax_amount'       => ['nullable', 'numeric', 'min:0'],
            'discount_amount'  => ['nullable', 'numeric', 'min:0'],
            'total_amount'     => ['required', 'numeric', 'min:0'],
            'currency'         => ['nullable', 'string', 'max:10'],
            'paid_amount'      => ['nullable', 'numeric', 'min:0'],
            'due_amount'       => ['nullable', 'numeric', 'min:0'],
            'payment_status'   => ['required', 'in:paid,partially_paid,unpaid'],
            'payment_method'   => ['required', 'in:bank_transfer,cash,card,cheque'],
            'attachment'       => ['nullable', 'string', 'max:500', new \App\Rules\SafeFileReference],
            'notes'            => ['nullable', 'string', 'max:1000'],
            'items'            => ['nullable', 'array'],
            'items.*.item_name'=> ['nullable', 'string', 'max:200'],
            'items.*.quantity' => ['nullable', 'numeric', 'min:0.01'],
            'items.*.unit_price'=> ['nullable', 'numeric', 'min:0'],
            'items.*.total_price'=> ['nullable', 'numeric', 'min:0'],
        ];
    }
}
