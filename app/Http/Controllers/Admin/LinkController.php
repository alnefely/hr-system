<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Link\StoreLinkRequest;
use App\Http\Requests\Admin\Link\UpdateLinkRequest;
use App\Models\Branch;
use App\Models\Link;
use App\Services\Links\LinkService;
use App\Services\Links\ShortCodeGenerator;
use Illuminate\Http\Request;
use InvalidArgumentException;

class LinkController extends Controller
{
    public function __construct(
        protected LinkService $service
    ) {}

    public function index(Request $request)
    {
        // select صريح من جدول الروابط: yajra بيعمل join تلقائي للترتيب
        // بأعمدة العلاقات، و select * ساعتها بيخلي أعمدة الجدول المنضم
        // تدهس أعمدة الرابط (id بالذات) فالأكشنز تبوظ.
        $query = Link::query()
            ->select('links.*')
            ->with(['branch:id,name', 'creator:id,name']);

        if ($request->filled('branch_id')) {
            $query->where('links.branch_id', $request->branch_id);
        }

        if ($request->filled('status')) {
            $this->applyStatusFilter($query, $request->status);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('links.created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('links.created_at', '<=', $request->date_to);
        }

        if ($request->ajax()) {
            return datatables($query)
                ->addColumn('branch_name', fn ($row) => $row->branch?->name ?? __('link.filters.all_branches'))
                ->addColumn('creator_name', fn ($row) => $row->creator?->name ?? '-')
                ->addColumn('short_url', fn ($row) => $this->shortUrlCell($row))
                ->addColumn('destination', fn ($row) => $this->destinationCell($row))
                ->addColumn('status', fn ($row) => $this->statusCell($row))
                // العمود محسوب، و yajra مابيبحثش في المحسوب تلقائياً
                ->filterColumn('status', fn ($q, $keyword) => $this->applyStatusFilter($q, $keyword))
                ->addColumn('clicks', fn ($row) => '<span dir="ltr">' . number_format($row->clicks_count) . '</span>')
                ->addColumn('formatted_date', fn ($row) => $row->created_at?->format('Y-m-d'))
                ->addColumn('actions', fn ($row) => $this->actionsCell($row))
                ->rawColumns(['short_url', 'destination', 'status', 'clicks', 'actions'])
                ->make(true);
        }

        return view('admin.links.index', [
            'branches'   => Branch::accessible()->select('id', 'name')->orderBy('name')->get(),
            'stats'      => $this->stats(),
            'trashCount' => admin_can('link_trash') ? Link::onlyTrashed()->count() : 0,
        ]);
    }

    public function create(ShortCodeGenerator $generator)
    {
        return view('admin.links.create', [
            'branches' => Branch::accessible()->select('id', 'name')->orderBy('name')->get(),

            // كود جاهز في الحقل بدل ما يبقى فاضي: المستخدم يشوف
            // الكود اللي هياخده، ويقدر يمسحه ويكتب بتاعه.
            'suggestedCode' => $generator->generate(),
        ]);
    }

    /** كود جديد للزر اللي جنب الحقل. */
    public function generateCode(ShortCodeGenerator $generator)
    {
        return response()->json(['code' => $generator->generate()]);
    }

    public function store(StoreLinkRequest $request)
    {
        try {
            $link = $this->service->create($request->validated());
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['short_code' => $e->getMessage()]);
        }

        return redirect()
            ->route('admin.links.index')
            ->with('success', __('link.messages.created', ['link' => $link->short_url]));
    }

    public function edit(Link $link)
    {
        return view('admin.links.edit', [
            'link'     => $link,
            'branches' => Branch::accessible()->select('id', 'name')->orderBy('name')->get(),
        ]);
    }

    public function update(UpdateLinkRequest $request, Link $link)
    {
        try {
            $this->service->update($link, $request->validated());
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['short_code' => $e->getMessage()]);
        }

        return redirect()
            ->route('admin.links.index')
            ->with('success', __('link.messages.updated'));
    }

    public function destroy(Link $link)
    {
        $link->delete();

        return redirect()->back()->with('success', __('link.messages.deleted'));
    }

    public function trash(Request $request)
    {
        if ($request->ajax()) {
            $query = Link::onlyTrashed()
                ->select('links.*')
                ->with(['branch:id,name', 'creator:id,name']);

            return datatables($query)
                ->addColumn('branch_name', fn ($row) => $row->branch?->name ?? '-')
                ->addColumn('creator_name', fn ($row) => $row->creator?->name ?? '-')
                ->addColumn('clicks', fn ($row) => '<span dir="ltr">' . number_format($row->clicks_count) . '</span>')
                ->addColumn('deleted_at', fn ($row) => $row->deleted_at?->format('Y-m-d H:i') ?? '-')
                ->addColumn('actions', fn ($row) => $this->trashActionsCell($row))
                ->rawColumns(['clicks', 'actions'])
                ->make(true);
        }

        return view('admin.links.trash');
    }

    public function restore(int $id)
    {
        $link = Link::onlyTrashed()->findOrFail($id);
        $link->restore();

        return redirect()->back()->with('success', __('link.messages.restored'));
    }

    public function forceDelete(int $id)
    {
        $link = Link::onlyTrashed()->findOrFail($id);
        $link->forceDelete();

        return redirect()->back()->with('success', __('link.messages.force_deleted'));
    }

    // ========================================================
    //  مساعدات
    // ========================================================

    /**
     * فلتر الحالة.
     *
     * منطق واحد للفلتر الأعلى وللبحث في عمود الجدول — مصدر واحد
     * عشان الاتنين مايختلفوش.
     */
    private function applyStatusFilter($query, string $status): void
    {
        match ($status) {
            'active' => $query->where('links.is_active', true)
                ->where(fn ($q) => $q->whereNull('links.expires_at')
                    ->orWhere('links.expires_at', '>', now()))
                ->where(fn ($q) => $q->whereNull('links.max_clicks')
                    ->orWhereColumn('links.clicks_count', '<', 'links.max_clicks')),

            'inactive' => $query->where('links.is_active', false),

            // المقارنة عمود بعمود بترجع NULL لو max_clicks فاضي، فلازم
            // whereNotNull الأول وإلا الصفوف دي بتتشال من النتيجة.
            'expired' => $query->where('links.is_active', true)
                ->where(fn ($q) => $q->where('links.expires_at', '<=', now())
                    ->orWhere(fn ($s) => $s->whereNotNull('links.max_clicks')
                        ->whereColumn('links.clicks_count', '>=', 'links.max_clicks'))),

            default => null,
        };
    }

    private function stats(): array
    {
        return [
            'total'   => Link::count(),
            'active'  => Link::where('is_active', true)->count(),
            'clicks'  => (int) Link::sum('clicks_count'),
            'expired' => Link::where('is_active', true)
                ->where(fn ($q) => $q->where('expires_at', '<=', now())
                    ->orWhere(fn ($s) => $s->whereNotNull('max_clicks')
                        ->whereColumn('clicks_count', '>=', 'max_clicks')))
                ->count(),
        ];
    }

    private function shortUrlCell(Link $link): string
    {
        $url = e($link->short_url);

        return '<div class="d-flex align-items-center gap-1">'
            . '<a href="' . $url . '" target="_blank" rel="noopener noreferrer"'
            . ' class="fw-semibold text-primary text-nowrap" dir="ltr"'
            . ' title="' . $url . '">'
            . e($link->short_code)
            . '<i class="fa-solid fa-arrow-up-right-from-square fa-xs ms-1 opacity-50"></i>'
            . '</a>'
            . '<button type="button" class="btn btn-sm btn-icon btn-text-secondary btn-copy-link"'
            . ' data-url="' . $url . '" title="' . __('link.actions.copy') . '">'
            . '<i class="fa-solid fa-copy"></i></button>'
            . '</div>';
    }

    /**
     * الوجهة مقطوعة.
     *
     * القطع بـ CSS مش بقص النص: العمود بياخد عرض ثابت مهما كان
     * الرابط، والنص الكامل بيفضل في title للتلميح.
     */
    private function destinationCell(Link $link): string
    {
        $url = e($link->original_url);

        $title = $link->title
            ? '<small class="text-muted d-block text-truncate" style="max-width:280px;">' . e($link->title) . '</small>'
            : '';

        return '<a href="' . $url . '" target="_blank" rel="noopener noreferrer" dir="ltr"'
            . ' class="d-inline-block text-truncate align-middle" style="max-width:280px;"'
            . ' title="' . $url . '">'
            . $url . '</a>' . $title;
    }

    private function statusCell(Link $link): string
    {
        if (! $link->is_active) {
            return '<span class="badge bg-label-secondary">' . __('link.status.inactive') . '</span>';
        }

        if ($link->is_expired) {
            return '<span class="badge bg-label-danger">' . __('link.status.expired') . '</span>';
        }

        return '<span class="badge bg-label-success">' . __('link.status.active') . '</span>';
    }

    private function actionsCell(Link $link): string
    {
        $html = '<div class="d-flex gap-1 justify-content-center">';

        $html .= '<a href="' . e($link->short_url) . '" target="_blank" rel="noopener noreferrer"'
            . ' class="btn btn-sm btn-icon btn-label-info waves-effect" title="' . __('link.actions.open') . '">'
            . '<i class="fa-solid fa-arrow-up-right-from-square"></i></a>';

        if (admin_can('link_stats')) {
            $html .= '<a href="' . route('admin.links.stats', $link->id) . '"'
                . ' class="btn btn-sm btn-icon btn-label-success waves-effect" title="' . __('link.actions.stats') . '">'
                . '<i class="fa-solid fa-chart-line"></i></a>';
        }

        if (admin_can('link_update')) {
            $html .= '<a href="' . route('admin.links.edit', $link->id) . '"'
                . ' class="btn btn-sm btn-icon btn-label-primary waves-effect" title="' . __('global.edit') . '">'
                . '<i class="fa-solid fa-pen-to-square"></i></a>';
        }

        if (admin_can('link_delete')) {
            $html .= '<form action="' . route('admin.links.destroy', $link->id) . '" method="POST" class="d-inline">'
                . csrf_field() . method_field('DELETE')
                . '<button type="button" class="btn btn-sm btn-icon btn-label-danger waves-effect btn-swal-confirm"'
                . ' title="' . __('global.delete') . '"'
                . ' data-title="' . __('link.confirm.delete_title') . '"'
                . ' data-text="' . __('link.confirm.delete_text', ['link' => $link->short_code]) . '"'
                . ' data-icon="warning"'
                . ' data-btn-text="' . __('link.confirm.delete_button') . '"'
                . ' data-btn-class="btn-danger"><i class="fa-solid fa-trash"></i></button>'
                . '</form>';
        }

        return $html . '</div>';
    }

    private function trashActionsCell(Link $link): string
    {
        $html = '<div class="d-flex gap-1 justify-content-center">';

        if (admin_can('link_restore')) {
            $html .= '<form action="' . route('admin.links.restore', $link->id) . '" method="POST" class="d-inline">'
                . csrf_field()
                . '<button type="button" class="btn btn-sm btn-icon btn-label-success waves-effect btn-swal-confirm"'
                . ' title="' . __('global.restore') . '"'
                . ' data-title="' . __('link.confirm.restore_title') . '"'
                . ' data-text="' . __('link.confirm.restore_text', ['link' => $link->short_code]) . '"'
                . ' data-icon="question"'
                . ' data-btn-text="' . __('link.confirm.restore_button') . '"'
                . ' data-btn-class="btn-success"><i class="fa-solid fa-rotate-left"></i></button>'
                . '</form>';
        }

        if (admin_can('link_force_delete')) {
            $html .= '<form action="' . route('admin.links.forceDelete', $link->id) . '" method="POST" class="d-inline">'
                . csrf_field() . method_field('DELETE')
                . '<button type="button" class="btn btn-sm btn-icon btn-label-danger waves-effect btn-swal-confirm"'
                . ' title="' . __('global.force_delete') . '"'
                . ' data-title="' . __('global.confirm_delete') . '"'
                . ' data-text="' . __('global.confirm_delete') . '"'
                . ' data-icon="warning"'
                . ' data-btn-text="' . __('global.force_delete') . '"'
                . ' data-btn-class="btn-danger"><i class="fa-solid fa-trash"></i></button>'
                . '</form>';
        }

        return $html . '</div>';
    }
}
