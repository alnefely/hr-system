<?php

namespace App\Http\Controllers\Portal;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * مصروفات عهدتي — للعرض بس.
 *
 * المصروفات اللي اتسجّلت في لوحة الإدارة والموظف ده هو "المسؤول عن العهدة"
 * فيها. التسجيل والتعديل من الإدارة بس (المصروف بيأثر على رصيد عهدة الفرع).
 * الفترة الافتراضية الشهر الحالي، والإجمالي لكل عملة لوحدها (المصروف ممكن
 * يكون بعملة الفرع أو غيرها).
 */
class ExpenseController extends PortalController
{
    public function index(Request $request)
    {
        $period = $this->period(now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString());
        $inPeriod = fn () => $this->inPeriod($this->employee()->expenses()->reorder(), $period);

        if ($request->ajax()) {
            return datatables($inPeriod()->select('expenses.*')->with('category:id,name'))
                ->editColumn('expense_date', fn ($e) => $e->expense_date?->format('Y-m-d'))
                ->editColumn('title', fn ($e) => '<div class="fw-medium">' . e($e->title) . '</div>'
                    . ($e->notes ? '<small class="text-muted">' . e(\Illuminate\Support\Str::limit($e->notes, 80)) . '</small>' : ''))
                ->addColumn('category_name', fn ($e) => e($e->category?->name ?? '—'))
                ->editColumn('amount', fn ($e) => '<span dir="ltr" class="text-nowrap fw-bold">' . number_format((float) $e->amount, 2) . ' ' . e($e->currency) . '</span>')
                ->editColumn('payment_method', fn ($e) => e(__('expense.enums.' . $e->payment_method)))
                ->addColumn('invoice', function ($e) {
                    $html = $e->invoice_number ? '<span dir="ltr">' . e($e->invoice_number) . '</span>' : '';

                    if ($e->attachment) {
                        // نفس منطق لوحة الإدارة: رابط كامل من مدير الملفات أو مسار تخزين
                        $url = str_starts_with($e->attachment, 'http') ? $e->attachment : Storage::url($e->attachment);
                        $html .= ' <a href="' . e($url) . '" target="_blank" rel="noopener" class="ms-1" title="' . e(__('expense.fields.view_attachment')) . '"><i class="fa-solid fa-paperclip"></i></a>';
                    }

                    return $html ?: '<span class="text-muted">—</span>';
                })
                ->rawColumns(['title', 'amount', 'invoice'])
                ->make(true);
        }

        return view('portal.expenses.index', [
            'period' => $period,
            'totals' => $inPeriod()
                ->selectRaw('currency, SUM(amount) AS total, COUNT(*) AS cnt')
                ->groupBy('currency')
                ->get(),
        ]);
    }
}
