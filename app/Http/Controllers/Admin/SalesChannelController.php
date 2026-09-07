<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SalesChannelRequest;
use App\Models\SalesChannel;
use Illuminate\Http\Request;

class SalesChannelController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = SalesChannel::withCount('sales');

            return datatables($query)
                ->addColumn('type_badge', function ($row) {
                    // النوع بيتخزّن كمفتاح، فبنترجمه. لو قيمة قديمة
                    // مش في القائمة بنعرضها زي ما هي بدل مفتاح خام.
                    $key   = 'sales_channel.type_enums.' . $row->type;
                    $label = __($key) === $key ? $row->type : __($key);

                    return '<span class="badge bg-label-info">' . e($label) . '</span>';
                })
                ->addColumn('status_badge', function ($row) {
                    return $row->is_active
                        ? '<span class="badge bg-label-success">' . __('global.active') . '</span>'
                        : '<span class="badge bg-label-danger">' . __('global.inactive') . '</span>';
                })
                ->addColumn('sales_badge', function ($row) {
                    // العدد بيبقى رابط للفواتير مفلترة على القناة دي،
                    // عشان المستخدم يشوف العمليات مش الرقم بس.
                    if (!$row->sales_count) {
                        return '<span class="badge bg-label-secondary">0</span>';
                    }

                    return '<a href="' . route('admin.sales.index', ['sales_channel_id' => $row->id]) . '"
                        class="badge bg-label-primary text-decoration-none"
                        data-bs-toggle="tooltip" title="' . __('sales_channel.view_sales') . '">
                        <span dir="ltr">' . $row->sales_count . '</span> ' . __('sales_channel.sales_count') . '
                        <i class="fa-solid fa-arrow-left fa-xs ms-1"></i>
                    </a>';
                })
                ->addColumn('actions', function ($row) {
                    $html = '<div class="d-inline-flex gap-1">';
                    if (admin_can('sales_channel_update')) {
                        $html .= '<button type="button" class="btn btn-sm btn-icon btn-label-primary edit-channel-btn" 
                            data-id="' . $row->id . '" 
                            data-code="' . e($row->code) . '" 
                            data-name="' . e($row->name) . '" 
                            data-type="' . e($row->type) . '" 
                            data-active="' . ($row->is_active ? '1' : '0') . '" 
                            data-notes="' . e($row->notes) . '" 
                            title="' . __('global.edit') . '">
                            <i class="fa-solid fa-pen-to-square"></i>
                        </button>';
                    }
                    if (admin_can('sales_channel_delete')) {
                        $html .= '<form action="' . route('admin.sales-channels.destroy', $row->id) . '" method="POST" class="d-inline">
                            ' . csrf_field() . method_field('DELETE') . '
                            <button type="button" class="btn btn-sm btn-icon btn-label-danger waves-effect btn-swal-confirm" 
                                data-title="' . __('global.confirm_delete') . '" 
                                data-text="' . __('global.confirm_delete') . '" 
                                data-icon="warning" 
                                data-btn-text="' . __('global.delete') . '" 
                                data-btn-class="btn-danger" 
                                title="' . __('global.delete') . '">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </form>';
                    }
                    $html .= '</div>';
                    return $html;
                })
                ->rawColumns(['type_badge', 'status_badge', 'sales_badge', 'actions'])
                ->make(true);
        }

        $channelsCount = SalesChannel::count();

        $trashCount = SalesChannel::onlyTrashed()->count();
        return view('admin.sales-channels.index', compact('channelsCount', 'trashCount'));
    }

    public function store(SalesChannelRequest $request)
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active', true);

        SalesChannel::create($data);

        return redirect()->route('admin.sales-channels.index')->with('success', __('sales_channel.messages.created'));
    }

    public function update(SalesChannelRequest $request, SalesChannel $salesChannel)
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active');

        $salesChannel->update($data);

        return redirect()->route('admin.sales-channels.index')->with('success', __('sales_channel.messages.updated'));
    }

    public function destroy(SalesChannel $salesChannel)
    {
        if ($salesChannel->sales()->exists()) {
            return redirect()->back()->with('error', 'لا يمكن حذف قناة البيع لوجود فواتير مبيعات مرتبطة بها.');
        }

        $salesChannel->delete();

        return redirect()->route('admin.sales-channels.index')->with('success', __('sales_channel.messages.deleted'));
    }
    public function trash(Request $request)
    {
        if ($request->ajax()) {
            $query = SalesChannel::onlyTrashed();
            return datatables($query)
                ->addColumn('deleted_at', fn($row) => $row->deleted_at ? $row->deleted_at->format('Y-m-d H:i') : '-')
                ->make(true);
        }
        return view('admin.sales-channels.trash');
    }

    public function restore(int $id)
    {
        $ch = SalesChannel::onlyTrashed()->findOrFail($id);
        $ch->restore();
        return redirect()->back()->with('success', __('global.restore'));
    }

    public function forceDelete(int $id)
    {
        $ch = SalesChannel::onlyTrashed()->findOrFail($id);
        $ch->forceDelete();
        return redirect()->back()->with('success', __('global.force_delete'));
    }
}
