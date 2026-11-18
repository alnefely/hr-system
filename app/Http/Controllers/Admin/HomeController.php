<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Services\Dashboard\DashboardFilter;
use App\Services\Dashboard\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    //
    public function home(Request $request, DashboardService $dashboard)
    {
        $dashboard = $dashboard->withFilter(DashboardFilter::fromRequest($request));

        return view('admin.home.index', array_merge($dashboard->payload(), [
            'branches'   => Branch::accessible()->where('is_active', true)->get(['id', 'name']),
            'currencies' => $dashboard->availableCurrencies(),
        ]));
    }

    /**
     * مسار AJAX موحّد للوحة.
     *
     * الرسوم بتتحدّث من غير إعادة تحميل الصفحة، وبيرجّع بس الأجزاء
     * اللي بتتأثر بالفلتر — العدادات الثابتة مالهاش لازمة هنا.
     */
    public function dashboardData(Request $request, DashboardService $dashboard): JsonResponse
    {
        $payload = $dashboard->withFilter(DashboardFilter::fromRequest($request))->filteredPayload();

        // نفس partials الصفحة: الشكل له مصدر واحد بدل HTML مكتوب يدوي في الـ JS
        $payload['financialHtml'] = view('admin.home.partials.financial', ['financial' => $payload['financial']])->render();
        $payload['topCustomersHtml'] = view('admin.home.partials.top-customers', ['topCustomers' => $payload['topCustomers']])->render();

        return response()->json($payload);
    }

    // error_403
    public function error_403(Request $request)
    {
        return view('admin.errors.403');
    }

    // systemGuide
    public function systemGuide()
    {
        return view('admin.guide.index');
    }
}
