<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CustomerRequest;
use App\Models\Customer;
use App\Support\CodeSequence;
use App\Models\CustomerSource;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = Customer::withCount('sales')->with('customerSource:id,name');

            // بييجي من شاشة المصادر لما المستخدم يدوس على عدد العملاء
            if ($request->filled('customer_source_id')) {
                $query->where('customer_source_id', $request->customer_source_id);
            }

            return datatables($query)
                ->addColumn('phone_formatted', fn($row) => $row->phone ? '<span dir="ltr">' . e($row->phone) . '</span>' : '-')
                ->addColumn('status_badge', function ($row) {
                    return $row->is_active
                        ? '<span class="badge bg-label-success">' . __('global.active') . '</span>'
                        : '<span class="badge bg-label-danger">' . __('global.inactive') . '</span>';
                })
                ->addColumn('sales_badge', function ($row) {
                    return '<span class="badge bg-label-primary"><span dir="ltr">' . $row->sales_count . '</span> ' . __('customer.sales_count') . '</span>';
                })
                ->addColumn('actions', function ($row) {
                    $html = '<div class="d-inline-flex gap-1">';
                    if (admin_can('customer_update')) {
                        $html .= '<a href="' . route('admin.customers.edit', $row->id) . '" class="btn btn-sm btn-icon btn-label-primary" title="' . __('global.edit') . '">
                            <i class="fa-solid fa-pen-to-square"></i>
                        </a>';
                    }
                    if (admin_can('customer_delete')) {
                        $html .= '<form action="' . route('admin.customers.destroy', $row->id) . '" method="POST" class="d-inline">
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
                ->rawColumns(['phone_formatted', 'status_badge', 'sales_badge', 'actions'])
                ->make(true);
        }

        $customersCount = Customer::count();
        $activeCustomersCount = Customer::where('is_active', true)->count();

        $trashCount = Customer::onlyTrashed()->count();
        return view('admin.customers.index', compact('customersCount', 'activeCustomersCount', 'trashCount'));
    }

    public function create()
    {
        $nextCode = CodeSequence::next(Customer::class, 'code', 'CUST-');
        $sources  = CustomerSource::active()->get(['id', 'name']);

        return view('admin.customers.create', compact('nextCode', 'sources'));
    }

    public function store(CustomerRequest $request)
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active', true);

        Customer::create($data);

        return redirect()->route('admin.customers.index')->with('success', __('customer.messages.created'));
    }

    public function edit(Customer $customer)
    {
        $sources = CustomerSource::active()->get(['id', 'name']);

        // لو مصدر العميل اتعطّل بنضيفه للقائمة عشان مايتشالش منه
        // من غير ما حد ياخد باله عند أول حفظ.
        if ($customer->customer_source_id && ! $sources->contains('id', $customer->customer_source_id)) {
            $sources->push($customer->customerSource);
        }

        return view('admin.customers.edit', compact('customer', 'sources'));
    }

    public function update(CustomerRequest $request, Customer $customer)
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active');

        $customer->update($data);

        return redirect()->route('admin.customers.index')->with('success', __('customer.messages.updated'));
    }

    public function destroy(Customer $customer)
    {
        if ($customer->sales()->exists()) {
            return redirect()->back()->with('error', 'لا يمكن حذف العميل لوجود فواتير ومبيعات مسجلة له.');
        }

        $customer->delete();

        return redirect()->route('admin.customers.index')->with('success', __('customer.messages.deleted'));
    }
    public function trash(Request $request)
    {
        if ($request->ajax()) {
            $query = Customer::onlyTrashed();
            return datatables($query)
                ->addColumn('phone_formatted', fn($row) => $row->phone ? '<span dir="ltr">' . e($row->phone) . '</span>' : '-')
                ->addColumn('deleted_at', fn($row) => $row->deleted_at ? $row->deleted_at->format('Y-m-d H:i') : '-')
                ->addColumn('customer_type', function ($row) {
                    $key = 'customer.enums.' . $row->customer_type;

                    return __($key) === $key ? $row->customer_type : __($key);
                })
                ->rawColumns(['phone_formatted'])
                ->make(true);
        }
        return view('admin.customers.trash');
    }

    public function restore(int $id)
    {
        $cust = Customer::onlyTrashed()->findOrFail($id);
        $cust->restore();
        return redirect()->back()->with('success', __('global.restore'));
    }

    public function forceDelete(int $id)
    {
        $cust = Customer::onlyTrashed()->findOrFail($id);
        $cust->forceDelete();
        return redirect()->back()->with('success', __('global.force_delete'));
    }
}
