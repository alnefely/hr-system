{{--
    محتوى شاشة تفاصيل المهمة — مشترك بين لوحة الإدارة والبوابة.

    المتغيرات (من TaskViewData::build + الكنترولر):
      $task, $abilities, $transitions, $names, $expected, $health, $blocked, $actor
      $routes      بادئة أسماء المسارات: 'admin.tasks' أو 'portal.tasks'
      $assignable  الموظفين المتاحين للتكليف (لنافذة تعديل المكلّفين)
      $editUrl / $deleteUrl / $backUrl / $subtaskUrl
--}}
@include('tasks.partials.styles')
@php
    $statusEnum = $task->statusEnum();
    $priority = \App\Enums\TaskPriority::from($task->priority);
    $isOpen = $statusEnum->isOpen();
    $manual = $task->progress_source === \App\Enums\TaskProgressSource::Manual->value;
    $activeAssignments = $task->assignments->where('status', \App\Enums\TaskAssigneeStatus::Active->value);
    $actorName = function ($model) use ($actor) {
        if ($model === null) {
            return __('task.labels_text.system');
        }

        return $model::class === $actor::class && $model->getKey() === $actor->getKey()
            ? __('task.labels_text.you')
            : $model->name;
    };
    $namesOf = fn (array $ids) => collect($ids)->map(fn ($id) => $names[$id] ?? '#' . $id)->implode('، ');
@endphp

{{-- الرأس: العنوان والحالة وأزرار الإجراءات --}}
<div class="card mb-4">
    <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
            <div class="flex-grow-1" style="min-width: 0">
                <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                    <span class="badge bg-label-secondary" dir="ltr">{{ $task->code }}</span>
                    {!! $statusEnum->badgeHtml() !!}
                    {!! $priority->badgeHtml() !!}
                    @if ($task->recurrence_id)
                        <span class="badge bg-label-info"><i class="fa-solid fa-repeat me-1"></i>{{ __('task.recurrence.badge') }}</span>
                    @endif
                    @foreach ($task->labels as $label)
                        <span class="task-label" style="background: {{ $label->cssColor() }}">{{ $label->name }}</span>
                    @endforeach
                </div>
                <h4 class="mb-1 text-break">{{ $task->title }}</h4>
                <div class="task-meta d-flex flex-wrap gap-3">
                    @if ($task->project)
                        <span><i class="fa-solid fa-diagram-project me-1"></i>{{ $task->project->name }}</span>
                    @endif
                    @if ($task->parent)
                        <a href="{{ route($routes . '.show', $task->parent->id) }}"><i class="fa-solid fa-turn-up me-1"></i>{{ $task->parent->title }}</a>
                    @endif
                    <span><i class="fa-regular fa-user me-1"></i>{{ $actorName($task->creator) }}</span>
                </div>
            </div>
            <div class="d-flex flex-wrap gap-2">
                @if ($abilities['update'] && $editUrl)
                    <a href="{{ $editUrl }}" class="btn btn-label-primary"><i class="fa-solid fa-pen-to-square me-1"></i>{{ __('task.actions.edit') }}</a>
                @endif
                @if ($abilities['delete'] && $deleteUrl)
                    <form action="{{ $deleteUrl }}" method="POST" class="d-inline">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-label-danger" data-confirm-text="{{ $task->title }}">
                            <i class="fa-solid fa-trash me-1"></i>{{ __('task.actions.delete') }}
                        </button>
                    </form>
                @endif
                <a href="{{ $backUrl }}" class="btn btn-label-secondary"><i class="fa-solid fa-arrow-right icon-back me-1"></i>{{ __('task.actions.back') }}</a>
            </div>
        </div>

        @if ($blocked)
            <div class="alert alert-warning d-flex align-items-start gap-2 mt-3 mb-0">
                <i class="fa-solid fa-lock mt-1"></i>
                <div>
                    <strong>{{ __('task.labels_text.blocked') }}:</strong>
                    @foreach ($task->dependencies->where('status', '!=', 'completed') as $dep)
                        <a href="{{ route($routes . '.show', $dep->id) }}" class="ms-1">{{ $dep->title }}</a>@if (! $loop->last)،@endif
                    @endforeach
                </div>
            </div>
        @endif

        @if ($transitions !== [])
            <div class="task-status-actions d-flex flex-wrap gap-2 mt-3 pt-3 border-top">
                @foreach ($transitions as $t)
                    @php $locked = $blocked && in_array($t['status'], ['in_progress', 'in_review', 'completed'], true); @endphp
                    @if ($t['note'])
                        {{-- التغييرات اللي محتاجة ملاحظة بتفتح نافذة --}}
                        <button type="button" class="btn {{ $t['class'] }}" @disabled($locked)
                                data-status-modal data-status="{{ $t['status'] }}" data-label="{{ $t['label'] }}" data-required="{{ $t['note'] === 'required' ? 1 : 0 }}">
                            <i class="fa-solid {{ $t['icon'] }} me-1"></i>{{ $t['label'] }}
                        </button>
                    @else
                        <form action="{{ route($routes . '.status', $task->id) }}" method="POST" class="d-inline-flex">
                            @csrf
                            <input type="hidden" name="status" value="{{ $t['status'] }}">
                            <button type="submit" class="btn {{ $t['class'] }} w-100" @disabled($locked)>
                                <i class="fa-solid {{ $t['icon'] }} me-1"></i>{{ $t['label'] }}
                            </button>
                        </form>
                    @endif
                @endforeach
                @if ($abilities['decline'] && \Illuminate\Support\Facades\Route::has($routes . '.decline'))
                    <button type="button" class="btn btn-label-danger" data-bs-toggle="modal" data-bs-target="#declineTaskModal">
                        <i class="fa-solid fa-hand me-1"></i>{{ __('task.actions.decline') }}
                    </button>
                @endif
            </div>
        @endif
    </div>
</div>

<div class="row g-4">
    {{-- على الموبايل: النسبة والمكلّفين الأول (أكتر حاجة الموظف بيستخدمها) --}}
    <div class="col-lg-8 order-2 order-lg-1">
        {{-- الوصف --}}
        @if ($task->description)
            <div class="card mb-4">
                <div class="card-header"><h6 class="mb-0 fw-bold"><i class="fa-solid fa-align-right me-2 text-primary"></i>{{ __('task.fields.description') }}</h6></div>
                <div class="card-body task-description">{{ $task->description }}</div>
            </div>
        @endif

        {{-- الخطوات --}}
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold"><i class="fa-regular fa-square-check me-2 text-primary"></i>{{ __('task.fields.checklist') }}</h6>
                @if ($task->checklist_count > 0)
                    <span class="small text-body-secondary">{{ __('task.labels_text.steps_done', ['done' => $task->completed_checklist_count, 'total' => $task->checklist_count]) }}</span>
                @endif
            </div>
            <div class="card-body">
                @forelse ($task->checklistItems as $item)
                    <div class="task-checklist d-flex align-items-start gap-2 py-2 {{ $item->is_done ? 'done' : '' }} {{ ! $loop->last ? 'border-bottom' : '' }}">
                        @if ($abilities['work'])
                            <form action="{{ route($routes . '.checklist.toggle', [$task->id, $item->id]) }}" method="POST" class="m-0" data-no-confirm>
                                @csrf @method('PATCH')
                                <input type="checkbox" class="form-check-input" id="check_{{ $item->id }}" @checked($item->is_done) onchange="this.form.submit()" aria-label="{{ $item->title }}">
                            </form>
                        @else
                            <input type="checkbox" class="form-check-input" id="check_{{ $item->id }}" @checked($item->is_done) disabled>
                        @endif
                        <label for="check_{{ $item->id }}" class="flex-grow-1 text-break">{{ $item->title }}</label>
                        @if ($abilities['work'])
                            <form action="{{ route($routes . '.checklist.destroy', [$task->id, $item->id]) }}" method="POST" class="m-0">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-icon btn-text-danger" aria-label="{{ __('global.delete') }}" data-confirm-text="{{ $item->title }}">
                                    <i class="fa-solid fa-xmark"></i>
                                </button>
                            </form>
                        @endif
                    </div>
                @empty
                    <p class="text-body-secondary mb-0 small">{{ __('task.labels_text.no_steps') }}</p>
                @endforelse

                @if ($abilities['work'] && $isOpen)
                    <form action="{{ route($routes . '.checklist.store', $task->id) }}" method="POST" class="d-flex gap-2 mt-3" data-no-confirm>
                        @csrf
                        <input type="text" name="title" class="form-control" maxlength="500" required placeholder="{{ __('task.actions.add_step') }}..." aria-label="{{ __('task.actions.add_step') }}">
                        <button type="submit" class="btn btn-primary text-nowrap"><i class="fa-solid fa-plus"></i><span class="d-none d-sm-inline ms-1">{{ __('task.actions.add_step') }}</span></button>
                    </form>
                @endif
            </div>
        </div>

        {{-- المهام الفرعية (المهمة الرئيسية بس — مستوى واحد) --}}
        @if (! $task->parent_id)
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h6 class="mb-0 fw-bold"><i class="fa-solid fa-sitemap me-2 text-primary"></i>{{ __('task.fields.subtasks') }}</h6>
                    @if ($abilities['work'] && $isOpen && $subtaskUrl)
                        <a href="{{ $subtaskUrl }}" class="btn btn-sm btn-label-primary"><i class="fa-solid fa-plus me-1"></i>{{ __('task.actions.add_subtask') }}</a>
                    @endif
                </div>
                <div class="list-group list-group-flush">
                    @forelse ($task->subtasks as $sub)
                        <a href="{{ route($routes . '.show', $sub->id) }}" class="list-group-item list-group-item-action d-flex flex-wrap align-items-center gap-3">
                            <div class="flex-grow-1" style="min-width: 10rem">
                                <div class="fw-semibold text-break">{{ $sub->title }}</div>
                                <div class="task-meta" dir="ltr">{{ $sub->code }}</div>
                            </div>
                            @include('tasks.partials.avatars', ['employees' => $sub->assignees, 'max' => 3])
                            <div style="width: 7rem">@include('tasks.partials.progress-bar', ['task' => $sub, 'compact' => true])</div>
                            {!! \App\Enums\TaskStatus::badgeFor($sub->status) !!}
                        </a>
                    @empty
                        <div class="list-group-item text-body-secondary small">{{ __('task.labels_text.no_subtasks') }}</div>
                    @endforelse
                </div>
            </div>
        @endif

        @include('tasks.partials.dependencies')
        @include('tasks.partials.reviews')

        @include('tasks.partials.comments')

        {{-- الخط الزمني --}}
        <div class="card">
            <div class="card-header"><h6 class="mb-0 fw-bold"><i class="fa-solid fa-clock-rotate-left me-2 text-primary"></i>{{ __('task.labels_text.timeline') }}</h6></div>
            <div class="card-body">
                @if ($task->activities->isEmpty())
                    <p class="text-body-secondary small mb-0">{{ __('task.labels_text.no_activity') }}</p>
                @else
                    <ul class="task-timeline">
                        @foreach ($task->activities as $activity)
                            @php
                                $p = $activity->properties ?? [];
                                $text = __('task.activity.' . $activity->event, [
                                    'from'  => isset($p['from']) ? (\App\Enums\TaskStatus::tryFrom((string) $p['from'])?->label() ?? $p['from']) : '',
                                    'to'    => isset($p['to']) ? (\App\Enums\TaskStatus::tryFrom((string) $p['to'])?->label() ?? $p['to']) : '',
                                    'names' => isset($p['names']) ? implode('، ', (array) $p['names']) : $namesOf($p['employee_ids'] ?? []),
                                    'title' => $p['title'] ?? '',
                                    'minutes' => $p['minutes'] ?? '',
                                ]);
                            @endphp
                            <li>
                                <span class="dot"></span>
                                <div class="d-flex flex-wrap justify-content-between gap-1">
                                    <div><strong>{{ $actorName($activity->actor) }}</strong> {{ $text }}</div>
                                    <small class="text-body-secondary text-nowrap" title="{{ $activity->created_at->format('Y-m-d H:i') }}">{{ $activity->created_at->diffForHumans() }}</small>
                                </div>
                                @if (! empty($p['note']) || ! empty($p['reason']) || ! empty($p['excerpt']))
                                    <div class="small text-body-secondary mt-1 text-break">{{ $p['note'] ?? $p['reason'] ?? $p['excerpt'] }}</div>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </div>

    <div class="col-lg-4 order-1 order-lg-2">
        {{-- نسبة الإنجاز --}}
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold"><i class="fa-solid fa-chart-line me-2 text-primary"></i>{{ __('task.fields.progress') }}</h6>
                @unless ($manual)
                    <span class="badge bg-label-info">{{ __('task.labels_text.automatic') }}</span>
                @endunless
            </div>
            <div class="card-body">
                @include('tasks.partials.progress-bar', ['task' => $task])

                @if ($task->next_progress_update_at && $isOpen)
                    <div class="small mt-2 {{ $task->next_progress_update_at->isPast() ? 'text-danger' : 'text-body-secondary' }}">
                        <i class="fa-regular fa-bell me-1"></i>{{ __('task.fields.next_progress_update') }}:
                        <span dir="ltr">{{ $task->next_progress_update_at->format('Y-m-d') }}</span>
                    </div>
                @endif

                @if ($abilities['work'] && $manual && $isOpen)
                    <form action="{{ route($routes . '.progress', $task->id) }}" method="POST" class="mt-3 pt-3 border-top" data-no-confirm>
                        @csrf
                        <label for="task_progress_input" class="form-label d-flex justify-content-between">
                            <span>{{ __('task.actions.update_progress') }}</span>
                            <strong dir="ltr"><output id="task_progress_value">{{ $task->progress }}</output>%</strong>
                        </label>
                        {{-- --fill بيلوّن الشريط لحد القيمة (custom.css: .form-range-fill) --}}
                        <input type="range" class="form-range form-range-fill" min="0" max="100" step="5" name="progress" id="task_progress_input"
                               value="{{ old('progress', $task->progress) }}" style="--fill: {{ (int) old('progress', $task->progress) }}%"
                               oninput="document.getElementById('task_progress_value').value = this.value; this.style.setProperty('--fill', this.value + '%')">
                        <textarea name="note" class="form-control mt-2" rows="2" maxlength="1000" placeholder="{{ __('task.fields.note') }}..." aria-label="{{ __('task.fields.note') }}">{{ old('note') }}</textarea>
                        <button type="submit" class="btn btn-primary w-100 mt-2"><i class="fa-solid fa-floppy-disk me-1"></i>{{ __('task.actions.update_progress') }}</button>
                    </form>
                @endif

                @if ($task->progressUpdates->isNotEmpty())
                    <div class="mt-3 pt-3 border-top">
                        <div class="small fw-bold mb-2">{{ __('task.labels_text.progress_log') }}</div>
                        @foreach ($task->progressUpdates as $update)
                            <div class="d-flex justify-content-between gap-2 small py-1">
                                <span class="text-break">
                                    <strong dir="ltr">{{ $update->previous_progress }}% → {{ $update->progress }}%</strong>
                                    · {{ $actorName($update->author) }}
                                    @if ($update->expected_progress !== null && $update->progress < $update->expected_progress - 10)
                                        <i class="fa-solid fa-triangle-exclamation text-danger" title="{{ __('task.labels_text.expected_hint', ['expected' => $update->expected_progress]) }}"></i>
                                    @endif
                                    @if ($update->note)
                                        <span class="d-block text-body-secondary">{{ $update->note }}</span>
                                    @endif
                                </span>
                                <span class="text-body-secondary text-nowrap">{{ $update->created_at->diffForHumans(short: true) }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        {{-- المكلّفين --}}
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold"><i class="fa-solid fa-users me-2 text-primary"></i>{{ __('task.fields.assignees') }}</h6>
                @if ($abilities['assign'] && $isOpen && $assignable !== null)
                    <button type="button" class="btn btn-sm btn-label-primary" data-bs-toggle="modal" data-bs-target="#assigneesModal">
                        <i class="fa-solid fa-user-pen me-1"></i>{{ __('task.actions.manage_assignees') }}
                    </button>
                @endif
            </div>
            <ul class="list-group list-group-flush">
                @forelse ($task->assignments as $assignment)
                    <li class="list-group-item d-flex justify-content-between align-items-center gap-2">
                        <span class="text-break">{{ $assignment->employee?->name ?? '#' . $assignment->employee_id }}</span>
                        @if ($assignment->status === \App\Enums\TaskAssigneeStatus::Declined->value)
                            <span class="badge bg-label-danger" @if ($assignment->decline_reason) data-bs-toggle="tooltip" title="{{ $assignment->decline_reason }}" @endif>
                                {{ __('task.assignee_status_enums.declined') }}
                            </span>
                        @endif
                    </li>
                @empty
                    <li class="list-group-item text-body-secondary small">{{ __('task.labels_text.no_assignees') }}</li>
                @endforelse
            </ul>
        </div>

        @include('tasks.partials.time')

        @include('tasks.partials.attachments')

        {{-- التفاصيل --}}
        <div class="card">
            <div class="card-header"><h6 class="mb-0 fw-bold"><i class="fa-solid fa-circle-info me-2 text-primary"></i>{{ __('task.labels_text.details') }}</h6></div>
            <ul class="list-group list-group-flush small">
                <li class="list-group-item d-flex justify-content-between gap-2"><span class="text-body-secondary">{{ __('task.fields.due_at') }}</span><span class="text-end">@include('tasks.partials.due', ['task' => $task])</span></li>
                @if ($task->starts_at)
                    <li class="list-group-item d-flex justify-content-between gap-2"><span class="text-body-secondary">{{ __('task.fields.starts_at') }}</span><span dir="ltr">{{ $task->starts_at->format('Y-m-d H:i') }}</span></li>
                @endif
                @if ($task->branch)
                    <li class="list-group-item d-flex justify-content-between gap-2"><span class="text-body-secondary">{{ __('task.fields.branch') }}</span><span>{{ $task->branch->name }}</span></li>
                @endif
                @if ($task->estimated_minutes)
                    <li class="list-group-item d-flex justify-content-between gap-2"><span class="text-body-secondary">{{ __('task.fields.estimated_hours') }}</span><span dir="ltr">{{ round($task->estimated_minutes / 60, 1) }}</span></li>
                @endif
                <li class="list-group-item d-flex justify-content-between gap-2"><span class="text-body-secondary">{{ __('task.fields.progress_update_interval') }}</span>
                    <span>{{ $task->progress_update_interval === 'custom'
                        ? __('task.labels_text.every_days', ['days' => $task->progress_update_every_days])
                        : __('task.progress_interval_enums.' . $task->progress_update_interval) }}</span></li>
                <li class="list-group-item d-flex justify-content-between gap-2"><span class="text-body-secondary">{{ __('task.fields.requires_review') }}</span>
                    <span>{!! $task->requires_review ? '<i class="fa-solid fa-check text-success"></i>' : '<i class="fa-solid fa-minus text-body-secondary"></i>' !!}</span></li>
                <li class="list-group-item d-flex justify-content-between gap-2"><span class="text-body-secondary">{{ __('task.fields.created_at') }}</span><span dir="ltr">{{ $task->created_at->format('Y-m-d H:i') }}</span></li>
                @if ($task->completed_at)
                    <li class="list-group-item d-flex justify-content-between gap-2"><span class="text-body-secondary">{{ __('task.fields.completed_at') }}</span><span dir="ltr">{{ $task->completed_at->format('Y-m-d H:i') }}</span></li>
                @endif
            </ul>
        </div>
    </div>
</div>

{{-- نافذة تعديل المكلّفين --}}
@if ($abilities['assign'] && $isOpen && $assignable !== null)
    <div class="modal fade" id="assigneesModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form action="{{ route($routes . '.assignees', $task->id) }}" method="POST">
                    @csrf @method('PUT')
                    <div class="modal-header border-bottom">
                        <h5 class="modal-title fw-bold"><i class="fa-solid fa-user-pen text-primary me-2"></i>{{ __('task.actions.manage_assignees') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('global.cancel') }}"></button>
                    </div>
                    <div class="modal-body">
                        <label for="assignee_ids_modal" class="form-label">{{ __('task.fields.assignees') }} <x-inputs.tip :text="__('task.hints.assignees')" /></label>
                        <select name="assignee_ids[]" id="assignee_ids_modal" class="form-select select2" multiple data-dropdown-parent="#assigneesModal">
                            @foreach ($assignable as $employee)
                                <option value="{{ $employee->id }}" @selected($activeAssignments->contains('employee_id', $employee->id))>{{ $employee->name }} ({{ $employee->employee_code }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="modal-footer border-top">
                        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">{{ __('global.cancel') }}</button>
                        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-check me-1"></i>{{ __('global.save') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif

{{-- نافذة الاعتذار (البوابة) --}}
@if ($abilities['decline'] && \Illuminate\Support\Facades\Route::has($routes . '.decline'))
    <div class="modal fade" id="declineTaskModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form action="{{ route($routes . '.decline', $task->id) }}" method="POST">
                    @csrf
                    <div class="modal-header border-bottom">
                        <h5 class="modal-title fw-bold text-danger"><i class="fa-solid fa-hand me-2"></i>{{ __('task.actions.decline') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('global.cancel') }}"></button>
                    </div>
                    <div class="modal-body">
                        <label for="decline_reason" class="form-label">{{ __('task.fields.reason') }} <span class="text-danger">*</span></label>
                        <textarea name="reason" id="decline_reason" class="form-control" rows="3" maxlength="1000" required></textarea>
                    </div>
                    <div class="modal-footer border-top">
                        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">{{ __('global.cancel') }}</button>
                        <button type="submit" class="btn btn-danger"><i class="fa-solid fa-check me-1"></i>{{ __('task.actions.decline') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif

{{-- نافذة ملاحظة تغيير الحالة (تسليم، اعتماد، إرجاع بسبب، إلغاء) --}}
@if ($transitions !== [])
    <div class="modal fade" id="statusNoteModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form action="{{ route($routes . '.status', $task->id) }}" method="POST" data-no-confirm>
                    @csrf
                    <input type="hidden" name="status" value="">
                    <div class="modal-header border-bottom">
                        <h5 class="modal-title fw-bold" data-title></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('global.cancel') }}"></button>
                    </div>
                    <div class="modal-body">
                        <label for="status_note" class="form-label" data-note-label
                               data-required-text="{{ __('task.review.return_reason') }}" data-optional-text="{{ __('task.review.optional_note') }}"></label>
                        <textarea name="note" id="status_note" class="form-control" rows="3" maxlength="1000"></textarea>
                    </div>
                    <div class="modal-footer border-top">
                        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">{{ __('global.cancel') }}</button>
                        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-check me-1"></i>{{ __('task.review.confirm') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @push('js')
    <script>
        (function () {
            const modalEl = document.getElementById('statusNoteModal');
            if (!modalEl) return;
            const form = modalEl.querySelector('form');
            const note = form.querySelector('textarea');
            const label = form.querySelector('[data-note-label]');
            document.querySelectorAll('[data-status-modal]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    const required = btn.dataset.required === '1';
                    form.querySelector('[name=status]').value = btn.dataset.status;
                    modalEl.querySelector('[data-title]').textContent = btn.dataset.label;
                    label.textContent = required ? label.dataset.requiredText : label.dataset.optionalText;
                    note.required = required;
                    note.value = '';
                    bootstrap.Modal.getOrCreateInstance(modalEl).show();
                    setTimeout(() => note.focus(), 300);
                });
            });
        })();
    </script>
    @endpush
@endif

{{-- تحديث لحظي: إشعار جديد عن المهمة المفتوحة ← شريط "فيه جديد" بزرار تحديث --}}
<div class="position-fixed bottom-0 start-50 translate-middle-x mb-3 d-none" style="z-index: 1090" id="taskLiveBanner">
    <div class="alert alert-primary shadow d-flex align-items-center gap-3 mb-0 py-2">
        <i class="fa-solid fa-bolt"></i>
        <span class="small" data-text></span>
        <a href="{{ url()->current() }}" class="btn btn-sm btn-primary">{{ __('task.labels_text.refresh') }}</a>
    </div>
</div>
@push('js')
<script>
    (function () {
        const banner = document.getElementById('taskLiveBanner');
        const taskUrl = @json(route($routes . '.show', $task->id, false));
        ['admin:notification', 'portal:notification'].forEach(function (name) {
            document.addEventListener(name, function (e) {
                const n = e.detail || {};
                if (!n.url || n.url.split('#')[0] !== taskUrl) return;
                banner.querySelector('[data-text]').textContent = n.title || '';
                banner.classList.remove('d-none');
            });
        });
    })();
</script>
@endpush
