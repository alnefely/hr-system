@extends('layouts.admin.app')
@section('title', __('task.recurrence.title'))

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2 border-bottom">
        <h5 class="card-title mb-0"><i class="fa-solid fa-repeat text-primary me-2"></i>{{ __('task.recurrence.title') }} <x-tasks.help key="recurrences" /></h5>
        <a href="{{ route('admin.tasks.recurrences.create') }}" class="btn btn-primary"><i class="fa-solid fa-plus me-1"></i>{{ __('task.recurrence.add_new') }}</a>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead>
                <tr>
                    <th>{{ __('task.recurrence.task') }}</th>
                    <th>{{ __('task.recurrence.schedule') }}</th>
                    <th>{{ __('task.fields.assignees') }}</th>
                    <th>{{ __('task.recurrence.next_run') }}</th>
                    <th class="text-center">{{ __('task.recurrence.occurrences') }}</th>
                    <th>{{ __('task.fields.status') }}</th>
                    <th class="text-end">{{ __('global.actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rules as $rule)
                    <tr>
                        <td style="min-width: 12rem">
                            <div class="fw-semibold text-break">{{ $rule->blueprint['title'] ?? '—' }}</div>
                            <div class="small text-body-secondary">
                                {!! \App\Enums\TaskPriority::badgeFor($rule->blueprint['priority'] ?? 'medium') !!}
                                @if ($rule->project) · {{ $rule->project->name }} @endif
                            </div>
                        </td>
                        <td class="small" style="min-width: 12rem">{{ $rule->describe() }}</td>
                        <td class="small">
                            {{ collect($rule->blueprint['assignee_ids'] ?? [])->map(fn ($id) => $names[$id] ?? null)->filter()->implode('، ') ?: '—' }}
                        </td>
                        <td class="small text-nowrap" dir="ltr">{{ $rule->next_run_at?->format('Y-m-d H:i') ?? '—' }}</td>
                        <td class="text-center">{{ $rule->occurrences_count }}</td>
                        <td>
                            @if ($rule->is_active)
                                <span class="badge bg-label-success">{{ __('task.recurrence.active') }}</span>
                            @elseif ($rule->ends_on && $rule->ends_on->isPast())
                                <span class="badge bg-label-secondary">{{ __('task.recurrence.ended') }}</span>
                            @else
                                <span class="badge bg-label-warning">{{ __('task.recurrence.paused') }}</span>
                            @endif
                        </td>
                        <td class="text-end text-nowrap">
                            <form action="{{ route('admin.tasks.recurrences.toggle', $rule->id) }}" method="POST" class="d-inline" data-no-confirm>
                                @csrf
                                <button type="submit" class="btn btn-sm btn-icon {{ $rule->is_active ? 'btn-label-warning' : 'btn-label-success' }}"
                                        title="{{ $rule->is_active ? __('task.recurrence.pause') : __('task.recurrence.resume') }}" aria-label="{{ $rule->is_active ? __('task.recurrence.pause') : __('task.recurrence.resume') }}">
                                    <i class="fa-solid {{ $rule->is_active ? 'fa-pause' : 'fa-play' }}"></i>
                                </button>
                            </form>
                            <a href="{{ route('admin.tasks.recurrences.edit', $rule->id) }}" class="btn btn-sm btn-icon btn-label-primary" title="{{ __('global.edit') }}" aria-label="{{ __('global.edit') }}"><i class="fa-solid fa-pen-to-square"></i></a>
                            <form action="{{ route('admin.tasks.recurrences.destroy', $rule->id) }}" method="POST" class="d-inline" data-confirm-text="{{ $rule->blueprint['title'] ?? '' }}">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-icon btn-label-danger" title="{{ __('global.delete') }}" aria-label="{{ __('global.delete') }}"><i class="fa-solid fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-body-secondary py-5">{{ __('task.recurrence.empty') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
