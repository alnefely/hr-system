{{--
    تقويم المهام حسب موعد التسليم. $weeks, $tasks (مجمّعة باليوم), $month, $routes, $calendarRoute, $query
    الكمبيوتر: شبكة شهر (السبت ← الجمعة). الموبايل: قائمة بالأيام اللي فيها مهام بس.
--}}
@include('tasks.partials.styles')
@once
@push('css')
<style>
    .task-cal { table-layout: fixed; }
    .task-cal td { height: 7.5rem; vertical-align: top; padding: .35rem; }
    .task-cal .other-month { background: var(--bs-tertiary-bg); opacity: .65; }
    .task-cal .today { outline: 2px solid var(--bs-primary); outline-offset: -2px; }
    .cal-task { display: block; font-size: .75rem; padding: .15rem .35rem; border-radius: .25rem; margin-bottom: .2rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; text-decoration: none; }
</style>
@endpush
@endonce
@php
    $prev = $month->copy()->subMonth()->format('Y-m');
    $next = $month->copy()->addMonth()->format('Y-m');
    $dayNames = collect(range(0, 6))->map(fn ($i) => $weeks[0][$i]->translatedFormat('l'));
    $tone = fn ($t) => match (true) {
        $t->status === 'completed' => 'bg-label-success',
        $t->isOverdue()            => 'bg-label-danger',
        $t->status === 'in_review' => 'bg-label-warning',
        default                    => 'bg-label-primary',
    };
@endphp

<div class="card">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2 border-bottom">
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route($calendarRoute, array_merge($query, ['month' => $prev])) }}" class="btn btn-icon btn-label-secondary" aria-label="{{ __('task.views.prev_month') }}"><i class="fa-solid fa-chevron-right icon-back"></i></a>
            <h5 class="mb-0 fw-bold text-nowrap">{{ $month->translatedFormat('F Y') }} <x-tasks.help key="calendar" /></h5>
            <a href="{{ route($calendarRoute, array_merge($query, ['month' => $next])) }}" class="btn btn-icon btn-label-secondary" aria-label="{{ __('task.views.next_month') }}"><i class="fa-solid fa-chevron-left icon-back"></i></a>
        </div>
        <a href="{{ route($calendarRoute, $query) }}" class="btn btn-sm btn-label-primary">{{ __('task.views.today') }}</a>
    </div>

    {{-- الكمبيوتر والتابلت --}}
    <div class="table-responsive d-none d-md-block">
        <table class="table table-bordered task-cal mb-0">
            <thead><tr>@foreach ($dayNames as $name)<th class="text-center small">{{ $name }}</th>@endforeach</tr></thead>
            <tbody>
                @foreach ($weeks as $week)
                    <tr>
                        @foreach ($week as $day)
                            @php $items = $tasks->get($day->toDateString(), collect()); @endphp
                            <td class="{{ $day->month !== $month->month ? 'other-month' : '' }} {{ $day->isToday() ? 'today' : '' }}">
                                <div class="small fw-semibold mb-1 {{ $day->isToday() ? 'text-primary' : 'text-body-secondary' }}">{{ $day->day }}</div>
                                @foreach ($items->take(4) as $t)
                                    <a href="{{ route($routes . '.show', $t->id) }}" class="cal-task {{ $tone($t) }}" title="{{ $t->title }} — {{ $t->due_at->format('H:i') }}">{{ $t->title }}</a>
                                @endforeach
                                @if ($items->count() > 4)
                                    <span class="small text-body-secondary">+{{ $items->count() - 4 }}</span>
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- الموبايل: الأيام اللي فيها مهام بس --}}
    <div class="d-md-none">
        @php $monthDays = $tasks->filter(fn ($items, $date) => \Illuminate\Support\Carbon::parse($date)->month === $month->month)->sortKeys(); @endphp
        @forelse ($monthDays as $date => $items)
            @php $day = \Illuminate\Support\Carbon::parse($date); @endphp
            <div class="px-3 py-2 border-bottom bg-body-tertiary small fw-bold {{ $day->isToday() ? 'text-primary' : '' }}">{{ $day->translatedFormat('l j F') }}</div>
            <div class="list-group list-group-flush">
                @foreach ($items as $t)
                    <a href="{{ route($routes . '.show', $t->id) }}" class="list-group-item list-group-item-action d-flex align-items-center gap-2">
                        <span class="badge {{ $tone($t) }} flex-shrink-0" dir="ltr">{{ $t->due_at->format('H:i') }}</span>
                        <span class="flex-grow-1 text-break">{{ $t->title }}</span>
                        {!! \App\Enums\TaskStatus::badgeFor($t->status) !!}
                    </a>
                @endforeach
            </div>
        @empty
            <div class="text-center text-body-secondary py-5">{{ __('task.views.no_due_this_month') }}</div>
        @endforelse
    </div>
</div>
