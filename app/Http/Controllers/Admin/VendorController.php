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
                ->addColumn('actions', function ($row) {
                    $html = '<div class="d-inline-flex gap-1">';
                    if (admin_can('vendor_update')) {
                        $html .= '<button type="button" class="btn btn-sm btn-icon btn-label-primary edit-vendor-btn" 
                            data-id="' . $row->id . '" 
                            data-code="' . e($row->code) . '" 
                            data-name="' . e($row->name) . '" 
                            data-company="' . e($row->company_name) . '" 
                            data-contact="' . e($row->contact_person) . '" 
                            data-phone="' . e($row->phone) . '" 
                            data-email="' . e($row->email) . '" 
                            data-tax="' . e($row->tax_number) . '" 
                            data-active="' . ($row->is_active ? '1' : '0') . '" 
                            data-notes="' . e($row->notes) . '" 
                            title="' . __('global.edit') . '">
                            <i class="fa-solid fa-pen-to-square"></i>
                        </button>';
                    }
                    if (admin_can('vendor_delete')) {
                        $html .= '<form action="' . route('admin.vendors.destroy', $row->id) . '" method="POST" class="d-inline">
                            ' . csrf_field() . method_field('DELETE') . '
                            <button type="submit" class="btn btn-sm btn-icon btn-label-danger" title="' . __('global.delete') . '">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </form>';
                    }
                    $html .= '</div>';
                    return $html;
                })
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
