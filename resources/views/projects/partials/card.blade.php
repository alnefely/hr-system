{{--
    كارت مشروع — لوحة الإدارة والبوابة. $project (مع manager و members محمّلين)، $url.
    العدادات والنسبة مخزّنة على المشروع (مفيش استعلامات إضافية).
--}}
@php
    $color = preg_match('/^#[0-9a-fA-F]{6}$/', (string) $project->color) ? $project->color : 'var(--bs-primary)';
    $overdue = $project->isOverdue();
@endphp
<div class="card h-100 task-card position-relative" style="border-top: 3px solid {{ $color }}">
    <div class="card-body d-flex flex-column gap-2">
        <div class="d-flex justify-content-between align-items-start gap-2">
            <div class="d-flex flex-wrap gap-1">
                {!! \App\Enums\ProjectStatus::badgeFor($project->status) !!}
                {!! \App\Enums\TaskPriority::badgeFor($project->priority) !!}
            </div>
            <span class="task-meta" dir="ltr">{{ $project->code }}</span>
        </div>
        <a href="{{ $url }}" class="stretched-link text-heading fw-semibold fs-6 text-break">{{ $project->name }}</a>
        <div class="task-meta"><i class="fa-regular fa-user me-1"></i>{{ $project->manager?->name ?? __('project.labels_text.no_manager') }}</div>

        <div class="mt-auto pt-2">
            <div class="d-flex justify-content-between small mb-1">
                <span class="text-body-secondary">{{ __('project.labels_text.tasks_done', ['done' => $project->completed_tasks_count, 'total' => $project->tasks_count]) }}</span>
                <strong dir="ltr">{{ $project->progress }}%</strong>
            </div>
            <div class="task-progress"><div class="bar {{ $project->status === 'completed' ? 'bg-success' : 'bg-primary' }}" style="width: {{ $project->progress }}%"></div></div>
        </div>

        <div class="d-flex justify-content-between align-items-center gap-2 small">
            <span class="{{ $overdue ? 'text-danger fw-semibold' : 'text-body-secondary' }}">
                <i class="fa-regular fa-calendar me-1"></i>
                @if ($project->due_date)
                    <span dir="ltr">{{ $project->due_date->format('Y-m-d') }}</span>
                    @if ($overdue) · {{ __('project.labels_text.overdue') }} @endif
                @else
                    {{ __('project.labels_text.no_due') }}
                @endif
            </span>
            @include('tasks.partials.avatars', ['employees' => $project->members, 'max' => 4])
        </div>
    </div>
</div>
