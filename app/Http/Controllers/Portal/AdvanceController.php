<?php

namespace App\Http\Controllers\Portal;

use App\Enums\AdvanceStatus;
use App\Exceptions\InvalidStateException;
use App\Http\Requests\Portal\PortalAdvanceRequest;
use App\Services\HR\AdvanceService;

/** سلفي: الطلب ومتابعة الأقساط والمتبقي. */
class AdvanceController extends PortalController
{
    public function index()
    {
        $employee = $this->employee()->loadMissing('branch:id,currency');

        return view('portal.advances.index', [
            'employee' => $employee,
            'advances' => $employee->advances()->withCount('repayments')->paginate(15),
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
