{{--
    صور مصغّرة للمكلّفين (الحروف الأولى من الاسم) — أخف من الصور الحقيقية
    في الجداول، والاسم كامل في التلميح.
--}}
@php
    $max ??= 4;
    $colors = ['primary', 'success', 'info', 'warning', 'danger', 'secondary'];
@endphp
@if ($employees->isEmpty())
    <span class="text-body-secondary small">{{ __('task.labels_text.no_assignees') }}</span>
@else
    <div class="d-flex align-items-center avatar-group">
        @foreach ($employees->take($max) as $employee)
            @php $initials = mb_substr(trim($employee->name), 0, 1) . mb_substr(trim(\Illuminate\Support\Str::after(trim($employee->name), ' ')), 0, 1); @endphp
            <div class="avatar pull-up" data-bs-toggle="tooltip" title="{{ $employee->name }}">
                <span class="avatar-initial rounded-circle bg-label-{{ $colors[$employee->id % count($colors)] }}">{{ $initials }}</span>
            </div>
        @endforeach
        @if ($employees->count() > $max)
            <div class="avatar" data-bs-toggle="tooltip" title="{{ $employees->slice($max)->pluck('name')->implode('، ') }}">
                <span class="avatar-initial rounded-circle bg-label-secondary">+{{ $employees->count() - $max }}</span>
            </div>
        @endif
    </div>
@endif
