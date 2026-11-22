{{-- موعد التسليم: أحمر لو متأخرة، وتنبيه لو النهاردة. --}}
@if ($task->due_at)
    @php
        $overdue = $task->isOverdue();
        $today = ! $overdue && $task->isOpen() && $task->due_at->isToday();
    @endphp
    <span class="text-nowrap {{ $overdue ? 'text-danger fw-semibold' : ($today ? 'text-warning fw-semibold' : '') }}" dir="ltr">
        {{ $task->due_at->format('Y-m-d') }}
        @if ($task->due_at->format('H:i') !== '00:00')
            <small class="text-body-secondary">{{ $task->due_at->format('H:i') }}</small>
        @endif
    </span>
    @if ($overdue)
        <div class="small text-danger"><i class="fa-solid fa-clock me-1"></i>{{ __('task.labels_text.overdue') }}</div>
    @elseif ($today)
        <div class="small text-warning">{{ __('task.labels_text.due_today') }}</div>
    @endif
@else
    <span class="text-body-secondary small">{{ __('task.labels_text.no_due') }}</span>
@endif
