<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\VendorRequest;
use App\Models\Vendor;
use Illuminate\Http\Request;

class VendorController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = Vendor::withCount('subscriptions');

            return datatables($query)
                ->addColumn('phone_formatted', fn($row) => $row->phone ? '<span dir="ltr">' . e($row->phone) . '</span>' : '-')
                ->addColumn('status_badge', function ($row) {
                    return $row->is_active
                        ? '<span class="badge bg-label-success">' . __('global.active') . '</span>'
                        : '<span class="badge bg-label-danger">' . __('global.inactive') . '</span>';
                })
                ->addColumn('subscriptions_badge', function ($row) {
                    return '<span class="badge bg-label-primary"><span dir="ltr">' . $row->subscriptions_count . '</span> ' . __('vendor.subscriptions_count') . '</span>';
                })
                ->addColumn('actions', fn ($row) => view('admin.vendors.partials.actions', ['row' => $row])->render())
                ->rawColumns(['phone_formatted', 'status_badge', 'subscriptions_badge', 'actions'])
                ->make(true);
        }

        $vendorsCount = Vendor::count();
        $activeVendorsCount = Vendor::where('is_active', true)->count();

        $trashCount = Vendor::onlyTrashed()->count();
        return view('admin.vendors.index', compact('vendorsCount', 'activeVendorsCount', 'trashCount'));
    }

    public function store(VendorRequest $request)
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active', true);

        Vendor::create($data);

        return redirect()->route('admin.vendors.index')->with('success', __('vendor.messages.created'));
    }

    public function update(VendorRequest $request, Vendor $vendor)
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active');

        $vendor->update($data);

        return redirect()->route('admin.vendors.index')->with('success', __('vendor.messages.updated'));
    }

    public function destroy(Request $request, Vendor $vendor)
    {
        if ($vendor->subscriptions()->exists()) {
            return redirect()->back()->with('error', 'لا يمكن حذف المورد لوجود اشتراكات وخدمات مرتبطة به.');
        }

        $vendor->delete();

        return redirect()->route('admin.vendors.index')->with('success', __('vendor.messages.deleted'));
    }
    public function trash(Request $request)
    {
        if ($request->ajax()) {
            $query = Vendor::onlyTrashed();
            return datatables($query)
                ->addColumn('phone_formatted', fn($row) => $row->phone ? '<span dir="ltr">' . e($row->phone) . '</span>' : '-')
                ->addColumn('deleted_at', fn($row) => $row->deleted_at ? $row->deleted_at->format('Y-m-d H:i') : '-')
                ->rawColumns(['phone_formatted'])
                ->make(true);
        }
        return view('admin.vendors.trash');
    }

    public function restore(int $id)
    {
        $ven = Vendor::onlyTrashed()->findOrFail($id);
        $ven->restore();
        return redirect()->back()->with('success', __('global.restore'));
    }

    public function forceDelete(int $id)
    {
        $ven = Vendor::onlyTrashed()->findOrFail($id);
        $ven->forceDelete();
        return redirect()->back()->with('success', __('global.force_delete'));
    }
}
