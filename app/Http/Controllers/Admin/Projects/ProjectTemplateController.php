<?php

namespace App\Http\Controllers\Admin\Projects;

use App\Http\Controllers\Controller;
use App\Http\Requests\Projects\ProjectTemplateRequest;
use App\Models\ProjectTemplate;
use App\Support\BranchScopeResolver;

/**
 * قوالب المشاريع: هيكل مهام جاهز بيتنسخ عند إنشاء مشروع.
 *
 * قالب مشرف الكل عام (branch_id = null) لكل الفروع؛ قالب مشرف الفرع
 * لفرعه بس. مشرف الفرع بيشوف العامة ومايعدّلش غير قوالب فرعه.
 */
class ProjectTemplateController extends Controller
{
    public function index()
    {
        return view('admin.project-templates.index', [
            'templates' => ProjectTemplate::query()
                ->when(BranchScopeResolver::branchId(), fn ($q, $b) => $q->where(fn ($w) => $w->whereNull('branch_id')->orWhere('branch_id', $b)))
                ->withCount('projects')
                ->with('branch:id,name')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function create()
    {
        return view('admin.project-templates.form', ['template' => new ProjectTemplate(['is_active' => true, 'structure' => []])]);
    }

    public function store(ProjectTemplateRequest $request)
    {
        $template = ProjectTemplate::create($this->attributes($request) + [
            'creator_type' => admin()::class,
            'creator_id'   => admin()->id,
        ]);

        return redirect()->route('admin.project-templates.edit', $template->id)->with('success', __('project.messages.template_saved'));
    }

    public function edit(ProjectTemplate $projectTemplate)
    {
        $this->ensureEditable($projectTemplate);

        return view('admin.project-templates.form', ['template' => $projectTemplate]);
    }

    public function update(ProjectTemplateRequest $request, ProjectTemplate $projectTemplate)
    {
        $this->ensureEditable($projectTemplate);
        $projectTemplate->update($this->attributes($request, $projectTemplate));

        return redirect()->route('admin.project-templates.edit', $projectTemplate->id)->with('success', __('project.messages.template_saved'));
    }

    public function destroy(ProjectTemplate $projectTemplate)
    {
        $this->ensureEditable($projectTemplate);
        $projectTemplate->delete();

        return redirect()->route('admin.project-templates.index')->with('success', __('project.messages.template_deleted'));
    }

    private function attributes(ProjectTemplateRequest $request, ?ProjectTemplate $existing = null): array
    {
        return [
            'name'        => $request->validated('name'),
            'description' => $request->validated('description'),
            'is_active'   => $request->boolean('is_active'),
            // مشرف الفرع: القالب لفرعه — مشرف الكل: عام (أو بيفضل على فرعه لو بيعدّل قالب فرع)
            'branch_id'   => BranchScopeResolver::branchId() ?? $existing?->branch_id,
            'structure'   => $request->structure(),
        ];
    }

    /** مشرف الفرع مايعدّلش القوالب العامة ولا قوالب فرع تاني. */
    private function ensureEditable(ProjectTemplate $template): void
    {
        $branchId = BranchScopeResolver::branchId();

        abort_if($branchId !== null && (int) $template->branch_id !== $branchId, 404);
    }
}
