@extends('layouts.portal.app')
@section('title', __('task.time.timesheet'))

@section('content')
@php
    $max = max(1, $byDay->max() ?? 1);
@endphp
<div class="card mb-4">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2 border-bottom">
        <h5 class="mb-0 fw-bold"><i class="fa-solid fa-stopwatch text-primary me-2"></i>{{ __('task.time.timesheet') }}</h5>
        <div class="d-flex flex-wrap align-items-center gap-2">
            <a href="{{ route('portal.time.index', ['week' => $week->copy()->subWeek()->toDateString()]) }}" class="btn btn-icon btn-label-secondary" aria-label="{{ __('task.time.prev_week') }}"><i class="fa-solid fa-chevron-right icon-back"></i></a>
            <span class="fw-semibold text-nowrap" dir="ltr">{{ $week->format('Y-m-d') }} → {{ $week->copy()->addDays(6)->format('Y-m-d') }}</span>
            <a href="{{ route('portal.time.index', ['week' => $week->copy()->addWeek()->toDateString()]) }}" class="btn btn-icon btn-label-secondary" aria-label="{{ __('task.time.next_week') }}"><i class="fa-solid fa-chevron-left icon-back"></i></a>
            <a href="{{ route('portal.time.index') }}" class="btn btn-sm btn-label-primary text-nowrap">{{ __('task.time.this_week') }}</a>
        </div>
    </div>
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <span class="text-body-secondary">{{ __('task.time.total') }}</span>
            <span class="h4 mb-0">{{ \App\Support\Duration::format($total) }}</span>
        </div>
        {{-- أعمدة الأيام --}}
        <div class="d-flex align-items-end gap-2" style="height: 9rem">
            @foreach ($days as $day)
                @php $m = (int) ($byDay[$day->toDateString()] ?? 0); @endphp
                <div class="flex-fill d-flex flex-column align-items-center justify-content-end h-100" title="{{ \App\Support\Duration::format($m) }}">
                    <small class="text-body-secondary mb-1" style="font-size: .7rem">{{ $m ? \App\Support\Duration::format($m) : '' }}</small>
                    <div class="w-100 rounded-top {{ $day->isToday() ? 'bg-primary' : 'bg-label-primary' }}" style="height: {{ $m ? max(4, round($m / $max * 100)) : 2 }}%; max-width: 3rem"></div>
                    <small class="mt-1 {{ $day->isToday() ? 'text-primary fw-bold' : 'text-body-secondary' }}">{{ $day->translatedFormat('D') }}</small>
                </div>
            @endforeach
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-header"><h6 class="mb-0 fw-bold">{{ __('task.time.by_task') }}</h6></div>
            <ul class="list-group list-group-flush">
                @forelse ($byTask as $row)
                    <li class="list-group-item d-flex justify-content-between gap-2">
                        @if ($row['task'])
                            <a href="{{ route('portal.tasks.show', $row['task']->id) }}" class="text-break">{{ $row['task']->title }}</a>
                        @else
                            <span>—</span>
                        @endif
                        <strong class="text-nowrap">{{ \App\Support\Duration::format($row['minutes']) }}</strong>
                    </li>
                @empty
                    <li class="list-group-item text-body-secondary small">{{ __('task.time.no_entries') }}</li>
                @endforelse
            </ul>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-header"><h6 class="mb-0 fw-bold">{{ __('task.time.entries') }}</h6></div>
            <ul class="list-group list-group-flush small">
                @forelse ($entries as $entry)
                    <li class="list-group-item d-flex justify-content-between gap-2">
                        <span class="text-break">
                            <a href="{{ route('portal.tasks.show', $entry->task_id) }}">{{ $entry->task?->title }}</a>
                            @if ($entry->is_manual)<span class="badge bg-label-secondary">{{ __('task.time.manual') }}</span>@endif
                            @if ($entry->note)<span class="d-block text-body-secondary">{{ $entry->note }}</span>@endif
                        </span>
                        <span class="text-end text-nowrap">
                            <strong>{{ \App\Support\Duration::format($entry->minutes) }}</strong>
                            <span class="d-block text-body-secondary" dir="ltr">{{ $entry->started_at->format('m-d') }}</span>
                        </span>
                    </li>
                @empty
                    <li class="list-group-item text-body-secondary">{{ __('task.time.no_entries') }}</li>
                @endforelse
            </ul>
        </div>
    </div>
</div>
@endsection
