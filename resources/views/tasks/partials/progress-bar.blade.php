{{--
    شريط نسبة الإنجاز.
      $task     المهمة
      $compact  للجداول والكروت (من غير علامة المستهدف وتفاصيل)
    اللون حسب مقارنة الفعلي بالمستهدف: أخضر سابق/في الموعد، أصفر/أحمر متأخر.
--}}
@php
    $compact ??= false;
    $value = (int) $task->progress;
    $done = $task->status === \App\Enums\TaskStatus::Completed->value;
    $health = $done ? null : $task->progressHealth();
    $expected = $done || $compact ? null : $task->expectedProgress();
    $color = match (true) {
        $done                  => 'bg-success',
        $health === 'behind'   => 'bg-danger',
        $health === 'ahead'    => 'bg-success',
        $health === 'on_track' => 'bg-primary',
        default                => 'bg-primary',
    };
@endphp
<div class="d-flex align-items-center gap-2" style="min-width: {{ $compact ? '6.5rem' : 'auto' }}">
    <div class="task-progress flex-grow-1 {{ $compact ? '' : 'task-progress-lg' }}"
         role="progressbar" aria-valuenow="{{ $value }}" aria-valuemin="0" aria-valuemax="100"
         aria-label="{{ __('task.fields.progress') }}">
        <div class="bar {{ $color }}" style="width: {{ $value }}%"></div>
        @if ($expected !== null)
            <span class="expected" style="inset-inline-start: {{ $expected }}%"
                  data-bs-toggle="tooltip" title="{{ __('task.labels_text.expected_hint', ['expected' => $expected]) }}"></span>
        @endif
    </div>
    <span class="small fw-semibold text-nowrap" dir="ltr">{{ $value }}%</span>
</div>
@if (! $compact && $health)
    <div class="small mt-1 {{ $health === 'behind' ? 'text-danger' : ($health === 'ahead' ? 'text-success' : 'text-body-secondary') }}">
        <i class="fa-solid {{ $health === 'behind' ? 'fa-triangle-exclamation' : 'fa-circle-check' }} me-1"></i>
        {{ __('task.progress_health.' . $health) }}
        @if ($expected !== null)
            · {{ __('task.labels_text.expected_hint', ['expected' => $expected]) }}
        @endif
    </div>
@endif
