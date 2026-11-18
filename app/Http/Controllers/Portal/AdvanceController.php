<?php

namespace App\Http\Controllers\Portal;

use App\Enums\AdvanceStatus;
use App\Exceptions\InvalidStateException;
use App\Http\Requests\Portal\PortalAdvanceRequest;
use App\Services\HR\AdvanceService;
use Illuminate\Http\Request;

/** سلفي: الطلب ومتابعة الأقساط والمتبقي. */
class AdvanceController extends PortalController
{
    public function index(Request $request)
    {
        $employee = $this->employee()->loadMissing('branch:id,currency');
        $period = $this->period();

        if ($request->ajax()) {
            $money = fn ($v) => '<span dir="ltr" class="text-nowrap">' . number_format((float) $v, 2) . '</span>';

            return datatables($this->inPeriod($employee->advances()->reorder(), $period))
                ->editColumn('advance_date', fn ($a) => $a->advance_date?->format('Y-m-d'))
                ->editColumn('amount', fn ($a) => $money($a->amount))
                ->editColumn('monthly_installment', fn ($a) => $money($a->monthly_installment) . ' <small class="text-muted">&times; ' . (int) $a->installments_count . '</small>')
                ->editColumn('remaining_amount', fn ($a) => '<span dir="ltr" class="text-nowrap fw-bold ' . ((float) $a->remaining_amount > 0 ? 'text-danger' : 'text-success') . '">'
                    . number_format((float) $a->remaining_amount, 2) . '</span>')
                ->addColumn('status_badge', fn ($a) => AdvanceStatus::badgeFor($a->status, 'advance.status_enums'))
                ->addColumn('action', fn ($a) => view('portal.partials.cancel-button', [
                    'pending' => $a->status === AdvanceStatus::Pending->value,
                    'action'  => route('portal.advances.destroy', $a->id),
                ])->render())
                ->rawColumns(['amount', 'monthly_installment', 'remaining_amount', 'status_badge', 'action'])
                ->make(true);
        }

        return view('portal.advances.index', [
            'employee' => $employee,
            'period'   => $period,
        ]);
    }

    public function store(PortalAdvanceRequest $request, AdvanceService $service)
    {
        $service->create([
            'employee_id'  => $this->employee()->id,
            'advance_date' => now()->toDateString(),
        ] + $request->validated());

        return redirect()->route('portal.advances.index')->with('success', __('portal.advances.submitted'));
    }

    public function destroy(int $id, AdvanceService $service)
    {
        $advance = $this->employee()->advances()->whereKey($id)->firstOrFail();

        if ($advance->status !== AdvanceStatus::Pending->value) {
            return back()->with('error', __('portal.requests.cannot_cancel'));
        }

        try {
            $service->delete($advance);
        } catch (InvalidStateException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', __('portal.requests.cancelled'));
    }
}
