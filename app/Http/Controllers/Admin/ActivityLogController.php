<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ActivityEvent;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Admin;
use App\Services\Export\ExcelExportService;
use App\Support\BranchScopeResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * عرض سجل حركات النظام.
 *
 * الشاشة دي للقراءة فقط — مفيش إنشاء ولا تعديل ولا حذف من الواجهة،
 * عشان السجل يفضل مصدر موثوق للتدقيق. الحذف بيتم بأمر artisan بس.
 */
class ActivityLogController extends Controller
{
    /** السجل الشامل لكل الموديولات، مع فلاتر. */
    public function index(Request $request)
    {
        $query = $this->filtered($request);

        if ($request->ajax() || $request->wantsJson()) {
            return $this->datatable($query);
        }

        if ($request->get('export') === 'excel') {
            return $this->export($request, $query);
        }

        return view('admin.activity_logs.index', [
            'subjects' => activity_subjects(),
            'events'   => activity_events(),
            'admins'   => Admin::orderBy('name')->get(['id', 'name']),
            'stats'    => $this->stats(),
        ]);
    }

    /** سجل موديول واحد (كل حركات المبيعات مثلاً). */
    public function subject(Request $request, string $type)
    {
        $subjects = config('activity.subjects', []);
        $class = $this->resolveSubjectClass($type, $subjects);

        abort_if($class === null, 404);

        // مشرف الفرع: حركات سجل واحد بس، ولازم السجل نفسه يكون في فرعه.
        // من غير ده /activity-logs/subject/Employee كان بيعرض حركات موظفين
        // كل الفروع برواتبهم.
        if (BranchScopeResolver::branchId() !== null && $request->filled('subject_id')) {
            abort_unless($this->subjectVisible($class, $request->input('subject_id')), 404);
        }

        $request->merge(['subject_type' => $class]);
        $query = $this->filtered($request);

        if ($request->ajax() || $request->wantsJson()) {
            return $this->datatable($query);
        }

        if ($request->get('export') === 'excel') {
            return $this->export($request, $query);
        }

        return view('admin.activity_logs.index', [
            'subjects'       => activity_subjects(),
            'events'         => activity_events(),
            'admins'         => Admin::orderBy('name')->get(['id', 'name']),
            'stats'          => $this->stats($class),
            'lockedSubject'  => $class,
        ]);
    }

    /** تفاصيل حركة واحدة: القيم قبل وبعد. */
    public function show(ActivityLog $activityLog)
    {
        abort_unless($this->logVisible($activityLog), 404);

        $activityLog->load('causer:id,name,email');

        if (request()->ajax() || request()->wantsJson()) {
            return response()->json([
                'id'            => $activityLog->id,
                'event'         => $activityLog->event,
                'event_label'   => activity_events()[$activityLog->event] ?? $activityLog->event,
                'event_badge'   => activity_event_badge($activityLog->event),
                'subject_label' => activity_subject_label($activityLog->subject_type),
                'subject_name'  => $activityLog->subject_label,
                'subject_id'    => $activityLog->subject_id,
                'causer_name'   => $activityLog->causer_name ?? __('activity.system'),
                'created_at'    => $activityLog->created_at?->format('Y-m-d H:i:s'),
                'ip_address'    => $activityLog->ip_address,
                'url'           => $activityLog->url,
                'method'        => $activityLog->method,
                'user_agent'    => $activityLog->user_agent,
                'changes'       => collect($activityLog->changes_list)->map(fn ($row) => [
                    'label' => $row['label'],
                    'field' => $row['field'],
                    'old'   => activity_value($row['old']),
                    'new'   => activity_value($row['new']),
                ])->all(),
            ]);
        }

        return view('admin.activity_logs.show', compact('activityLog'));
    }

    // ========================================================
    //  Helpers
    // ========================================================

    /**
     * مشرف الفرع بيشوف: حركات سجل في فرعه (لما بيفتح سجل بعينه)، أو
     * الحركات اللي عملها مشرفين فرعه. السجل مالوش عمود فرع، فده أقرب
     * تقييد من غير ما نقرا كل موديل.
     */
    private function scopeToBranch($query, Request $request)
    {
        $branchId = BranchScopeResolver::branchId();

        if ($branchId === null) {
            return $query;
        }

        if ($request->filled('subject_id') && $request->filled('subject_type')
            && $this->subjectVisible($request->input('subject_type'), $request->input('subject_id'))) {
            return $query;
        }

        return $query->whereIn('causer_id', $this->branchAdminIds($branchId));
    }

    private function branchAdminIds(int $branchId)
    {
        return Admin::query()
            ->whereIn('employee_id', DB::table('employees')->where('branch_id', $branchId)->select('id'))
            ->select('id');
    }

    private function logVisible(ActivityLog $log): bool
    {
        $branchId = BranchScopeResolver::branchId();

        if ($branchId === null) {
            return true;
        }

        return $this->subjectVisible($log->subject_type, $log->subject_id)
            || ($log->causer_id && $this->branchAdminIds($branchId)->whereKey($log->causer_id)->exists());
    }

    /** السجل موجود جوّه نطاق فرع المشرف؟ (عزل الفروع على الموديل نفسه) */
    private function subjectVisible(?string $class, $id): bool
    {
        if (! $class || ! class_exists($class) || ! array_key_exists($class, config('activity.subjects', []))) {
            return false;
        }

        if (! in_array(\App\Models\Concerns\ScopedToBranch::class, class_uses_recursive($class), true)) {
            return false;
        }

        $query = $class::query();

        if (method_exists($class, 'bootSoftDeletes')) {
            $query->withTrashed();
        }

        return $query->whereKey($id)->exists();
    }

    private function filtered(Request $request)
    {
        // الجدول والتصدير بيستخدموا causer_name المتخزّن مع السجل —
        // مالوش لازمة نحمّل علاقة المشرف.
        return $this->scopeToBranch(ActivityLog::query(), $request)
            ->forSubjectType($request->input('subject_type'))
            ->forEvent($request->input('event'))
            ->forCauser($request->input('causer_id'))
            ->betweenDates($request->input('date_from'), $request->input('date_to'))
            ->when($request->filled('subject_id'), fn ($q) => $q->where('subject_id', $request->subject_id))
            ->latest('id');
    }

    private function datatable($query)
    {
        return datatables($query)
            ->addColumn('event_badge', fn($row) => ActivityEvent::fromValue($row->event)?->badgeHtml()
                ?? '<span class="badge bg-label-secondary">' . e($row->event) . '</span>')
            ->addColumn('subject_name', function ($row) {
                $module = e(activity_subject_label($row->subject_type));
                $label = $row->subject_label ? e($row->subject_label) : '#' . $row->subject_id;

                return '<span class="fw-semibold">' . $module . '</span><br>'
                    . '<small class="text-muted">' . $label . '</small>';
            })
            ->addColumn('causer', function ($row) {
                if (!$row->causer_name) {
                    return '<span class="badge bg-label-secondary">' . __('activity.system') . '</span>';
                }

                return e($row->causer_name);
            })
            ->addColumn('changes_count', function ($row) {
                $count = count($row->new_values ?? $row->old_values ?? []);

                return '<span class="badge bg-label-secondary">' . $count . '</span>';
            })
            ->addColumn('created', fn ($row) => '<span dir="ltr">' . $row->created_at?->format('Y-m-d H:i') . '</span>')
            ->addColumn('actions', function ($row) {
                return '<button type="button" class="btn btn-sm btn-icon btn-label-info activity-details-btn"
                            data-url="' . route('admin.activity-logs.show', $row->id) . '"
                            data-bs-toggle="tooltip" title="' . __('activity.actions.details') . '">
                            <i class="fa-solid fa-eye"></i>
                        </button>';
            })
            ->rawColumns(['event_badge', 'subject_name', 'causer', 'changes_count', 'created', 'actions'])
            ->make(true);
    }

    private function stats(?string $subjectType = null): array
    {
        // استعلام واحد بدل ٥ COUNT، ومتكاش دقيقتين — الأرقام دي للعرض بس
        // ومش لازم تبقى لحظية.
        $today = today();
        $branchId = BranchScopeResolver::branchId();
        $key = 'activity_logs.stats.' . md5((string) $subjectType) . '.' . $today->toDateString() . '.b' . ($branchId ?? 'all');

        return Cache::remember($key, 120, function () use ($subjectType, $today, $branchId) {
            $row = ActivityLog::query()
                ->when($branchId !== null, fn ($q) => $q->whereIn('causer_id', $this->branchAdminIds($branchId)))
                ->forSubjectType($subjectType)
                ->selectRaw('COUNT(*) as total')
                ->selectRaw('SUM(CASE WHEN created_at >= ? AND created_at < ? THEN 1 ELSE 0 END) as today', [
                    $today->toDateTimeString(),
                    $today->copy()->addDay()->toDateTimeString(),
                ])
                ->selectRaw("SUM(CASE WHEN event = 'created' THEN 1 ELSE 0 END) as created")
                ->selectRaw("SUM(CASE WHEN event = 'updated' THEN 1 ELSE 0 END) as updated")
                ->selectRaw("SUM(CASE WHEN event IN ('deleted', 'force_deleted') THEN 1 ELSE 0 END) as deleted")
                ->toBase()
                ->first();

            return [
                'total'   => (int) ($row->total ?? 0),
                'today'   => (int) ($row->today ?? 0),
                'created' => (int) ($row->created ?? 0),
                'updated' => (int) ($row->updated ?? 0),
                'deleted' => (int) ($row->deleted ?? 0),
            ];
        });
    }

    /** بيقبل اسم الكلاس الكامل أو الاسم المختصر (Sale). */
    private function resolveSubjectClass(string $type, array $subjects): ?string
    {
        $decoded = urldecode($type);

        if (array_key_exists($decoded, $subjects)) {
            return $decoded;
        }

        foreach (array_keys($subjects) as $class) {
            if (strcasecmp(class_basename($class), $decoded) === 0) {
                return $class;
            }
        }

        return null;
    }

    private function export(Request $request, $query)
    {
        $logs = $query->limit(ExcelExportService::MAX_ROWS)->get();

        $headers = [
            __('global.export.row_no'),
            __('activity.fields.event'),
            __('activity.fields.subject'),
            __('activity.fields.subject_name'),
            __('activity.fields.causer'),
            __('activity.fields.changes'),
            __('activity.fields.ip_address'),
            __('global.export.date'),
        ];

        $rows = [];
        $i = 1;

        foreach ($logs as $log) {
            $changes = collect($log->changes_list)
                ->map(fn ($c) => $c['label'] . ': ' . activity_value($c['old']) . ' ← ' . activity_value($c['new']))
                ->implode(' | ');

            $rows[] = [
                $i++,
                activity_events()[$log->event] ?? $log->event,
                activity_subject_label($log->subject_type),
                $log->subject_label ?? ('#' . $log->subject_id),
                $log->causer_name ?? __('activity.system'),
                mb_substr($changes, 0, 400),
                $log->ip_address ?? '-',
                $log->created_at?->format('Y-m-d H:i'),
            ];
        }

        $meta = [
            __('global.export.records_count') => $logs->count(),
            __('global.export.period')        => ($request->date_from ?: '—') . ' → ' . ($request->date_to ?: '—'),
        ];

        return ExcelExportService::download(
            __('activity.title'),
            $headers,
            $rows,
            null,
            'activity_logs_' . date('Ymd_His'),
            $meta
        );
    }
}
