<?php

namespace App\Http\Controllers\Portal;

use App\Models\EmployeeNotification;
use Illuminate\Http\Request;

/**
 * إشعاراتي في البوابة — كل الاستعلامات مقيّدة بالموظف الحالي.
 */
class NotificationController extends PortalController
{
    public function index(Request $request)
    {
        $notifications = $this->mine()
            ->when($request->input('status') === 'unread', fn ($q) => $q->unread())
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('portal.notifications.index', [
            'notifications' => $notifications,
            'unreadCount'   => $this->mine()->unread()->count(),
        ]);
    }

    /** آخر الإشعارات للجرس (JSON). */
    public function latest()
    {
        return response()->json([
            'notifications' => $this->mine()->latest('id')->limit(10)->get()->map(fn (EmployeeNotification $n) => $n->toPayload()),
            'unread_count'  => $this->mine()->unread()->count(),
        ]);
    }

    public function read(Request $request, int $notification)
    {
        $notification = $this->mine()->findOrFail($notification);
        $notification->markAsRead();

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'unread_count' => $this->mine()->unread()->count()]);
        }

        return redirect($notification->url ?: route('portal.notifications.index'));
    }

    public function readAll(Request $request)
    {
        $this->mine()->unread()->update(['read_at' => now()]);

        return $request->wantsJson()
            ? response()->json(['success' => true, 'unread_count' => 0])
            : back()->with('success', __('notification.marked_all'));
    }

    private function mine()
    {
        return EmployeeNotification::where('employee_id', $this->employee()->id);
    }
}
