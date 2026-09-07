<?php

namespace App\Http\Controllers\Admin;

use App\Enums\NotificationType;
use App\Http\Controllers\Controller;
use App\Models\AdminNotification;
use Illuminate\Http\Request;

/**
 * إشعارات المشرف الحالي.
 *
 * كل الدوال هنا مقيّدة بـ admin_id بتاع المستخدم المسجّل — مفيش
 * مشرف يقدر يشوف أو يعدّل إشعارات غيره حتى لو غيّر الرقم في الرابط.
 */
class NotificationController extends Controller
{
    /** شاشة كل الإشعارات مع فلاتر. */
    public function index(Request $request)
    {
        $adminId = admin()?->id;

        $notifications = AdminNotification::forAdmin($adminId)
            ->ofType($request->input('type'))
            ->when($request->input('status') === 'unread', fn ($q) => $q->unread())
            ->when($request->input('status') === 'read', fn ($q) => $q->read())
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.notifications.index', [
            'notifications' => $notifications,
            'types'         => NotificationType::options(),
            'unreadCount'   => AdminNotification::forAdmin($adminId)->unread()->count(),
        ]);
    }

    /** آخر الإشعارات للجرس (JSON). */
    public function latest()
    {
        $adminId = admin()?->id;

        $notifications = AdminNotification::forAdmin($adminId)
            ->latest('id')
            ->limit(10)
            ->get()
            ->map(fn (AdminNotification $n) => $n->toPayload());

        return response()->json([
            'notifications' => $notifications,
            'unread_count'  => AdminNotification::forAdmin($adminId)->unread()->count(),
        ]);
    }

    /** تعليم إشعار واحد كمقروء ثم التحويل لرابطه. */
    public function read(Request $request, AdminNotification $notification)
    {
        abort_if($notification->admin_id !== admin()?->id, 404);

        $notification->markAsRead();

        if ($request->wantsJson()) {
            return response()->json([
                'success'      => true,
                'unread_count' => AdminNotification::forAdmin(admin()?->id)->unread()->count(),
            ]);
        }

        $fallback = route('admin.notifications.index');
        $target   = $notification->url ?: $fallback;

        // الرابط بيودّي لنفس الصفحة اللي إحنا فيها => التحويل هيبان
        // كأنه ريلود من غير أي نتيجة، فبنوضّح للمستخدم بدل الصمت.
        if ($this->isSamePage($target, $request)) {
            return redirect($fallback)->with('info', __('notification.no_extra_details'));
        }

        return redirect($target);
    }

    /** مقارنة المسار بغض النظر عن الدومين والـ query. */
    private function isSamePage(string $target, Request $request): bool
    {
        $referer = $request->headers->get('referer');

        if (!$referer) {
            return false;
        }

        return rtrim(parse_url($target, PHP_URL_PATH) ?? '', '/')
            === rtrim(parse_url($referer, PHP_URL_PATH) ?? '', '/');
    }

    /** تعليم كل إشعارات المشرف كمقروءة. */
    public function readAll(Request $request)
    {
        AdminNotification::forAdmin(admin()?->id)
            ->unread()
            ->update(['read_at' => now()]);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'unread_count' => 0]);
        }

        return redirect()->back()->with('success', __('notification.marked_all'));
    }
}
