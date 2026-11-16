<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CustomerSourceRequest;
use App\Models\CustomerSource;
use Illuminate\Http\Request;

/**
 * إدارة مصادر العملاء.
 *
 * نفس نمط قنوات البيع: شاشة واحدة فيها الجدول والنافذة المنبثقة،
 * وسلة محذوفات منفصلة.
 */
class CustomerSourceController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = CustomerSource::withCount('customers');

            return datatables($query)
                ->addColumn('status_badge', function ($row) {
                    return $row->is_active
                        ? '<span class="badge bg-label-success">' . __('global.active') . '</span>'
                        : '<span class="badge bg-label-danger">' . __('global.inactive') . '</span>';
                })
                ->addColumn('customers_badge', function ($row) {
                    // العدد رابط لقائمة العملاء مفلترة على المصدر ده،
                    // عشان المستخدم يشوف مين جه منين مش رقم مجرّد.
                    if (! $row->customers_count) {
                        return '<span class="badge bg-label-secondary">0</span>';
                    }

                    return '<a href="' . route('admin.customers.index', ['customer_source_id' => $row->id]) . '"
                        class="badge bg-label-primary text-decoration-none"
                        data-bs-toggle="tooltip" title="' . __('customer_source.view_customers') . '">
                        <span dir="ltr">' . $row->customers_count . '</span> ' . __('customer_source.customers_count') . '
                        <i class="fa-solid fa-arrow-left fa-xs ms-1"></i>
                    </a>';
                })
                ->addColumn('actions', fn ($row) => view('admin.customer_sources.partials.actions', ['row' => $row])->render())
                ->rawColumns(['status_badge', 'customers_badge', 'actions'])
                ->make(true);
        }

        $sourcesCount = CustomerSource::count();
        $trashCount   = CustomerSource::onlyTrashed()->count();

        return view('admin.customer-sources.index', compact('sourcesCount', 'trashCount'));
    }

    public function store(CustomerSourceRequest $request)
    {
        $data = $request->validated();
        $data['is_active']  = $request->boolean('is_active', true);
        $data['sort_order'] = (int) $request->input('sort_order', 0);

        CustomerSource::create($data);

        return redirect()->route('admin.customer-sources.index')
            ->with('success', __('customer_source.messages.created'));
    }

    public function update(CustomerSourceRequest $request, CustomerSource $customerSource)
    {
        $data = $request->validated();
        $data['is_active']  = $request->boolean('is_active');
        $data['sort_order'] = (int) $request->input('sort_order', 0);

        $customerSource->update($data);

        return redirect()->route('admin.customer-sources.index')
            ->with('success', __('customer_source.messages.updated'));
    }

    public function destroy(CustomerSource $customerSource)
    {
        // المصدر المرتبط بعملاء مايتحذفش — الحذف هيسيب العملاء
        // من غير مصدر ويضيّع معلومة مالهاش مصدر تاني.
        if ($customerSource->customers()->exists()) {
            return redirect()->back()
                ->with('error', __('customer_source.messages.has_customers'));
        }

        $customerSource->delete();

        return redirect()->route('admin.customer-sources.index')
            ->with('success', __('customer_source.messages.deleted'));
    }

    public function trash(Request $request)
    {
        if ($request->ajax()) {
            return datatables(CustomerSource::onlyTrashed())
                ->addColumn('deleted_at', fn ($row) => $row->deleted_at ? $row->deleted_at->format('Y-m-d H:i') : '-')
                ->make(true);
        }

        return view('admin.customer-sources.trash');
    }

    public function restore(int $id)
    {
        CustomerSource::onlyTrashed()->findOrFail($id)->restore();

        return redirect()->back()->with('success', __('global.restore'));
    }

    public function forceDelete(int $id)
    {
        CustomerSource::onlyTrashed()->findOrFail($id)->forceDelete();

        return redirect()->back()->with('success', __('global.force_delete'));
    }
}
