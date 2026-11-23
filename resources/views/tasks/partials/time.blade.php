{{--
    تتبع الوقت على المهمة: المتوقع مقابل الفعلي، العدّاد، الإدخال اليدوي، والسجل.
    $task (timeEntries محمّلة), $routes, $actor, $abilities, $runningEntry
--}}
@php
    $isEmployee = $actor instanceof \App\Models\Employee;
    $canLog = $abilities['work'] && $task->isOpen();
    $estimate = (int) $task->estimated_minutes;
    $spent = (int) $task->spent_minutes;
    $ratio = $estimate > 0 ? min(100, (int) round($spent / $estimate * 100)) : null;
    $runningHere = $runningEntry && (int) $runningEntry->task_id === (int) $task->id;
    $assigneesForAdmin = ! $isEmployee ? $task->assignments->where('status', 'active') : collect();
@endphp
<div class="card mb-4" id="time">
    <div class="card-header"><h6 class="mb-0 fw-bold"><i class="fa-regular fa-clock me-2 text-primary"></i>{{ __('task.time.title') }} <x-tasks.help key="time" :replace="['hours' => config('tasks.time.max_timer_hours', 12)]" /></h6></div>
    <div class="card-body">
        <div class="d-flex justify-content-between small mb-1">
            <span>{{ __('task.time.spent') }}: <strong>{{ \App\Support\Duration::format($spent) }}</strong></span>
            @if ($estimate)<span class="text-body-secondary">{{ __('task.time.estimated') }}: {{ \App\Support\Duration::format($estimate) }}</span>@endif
        </div>
        @if ($ratio !== null)
            <div class="task-progress"><div class="bar {{ $spent > $estimate ? 'bg-danger' : 'bg-info' }}" style="width: {{ $ratio }}%"></div></div>
            <div class="small mt-1 {{ $spent > $estimate ? 'text-danger' : 'text-body-secondary' }}">
                {{ $spent > $estimate ? __('task.time.over', ['time' => \App\Support\Duration::format($spent - $estimate)]) : __('task.time.remaining', ['time' => \App\Support\Duration::format($estimate - $spent)]) }}
            </div>
        @endif

        @if ($isEmployee && $canLog)
            <div class="mt-3 pt-3 border-top">
                @if ($runningHere)
                    <div class="d-flex align-items-center justify-content-between gap-2">
                        <span class="text-success fw-semibold"><span class="live-dot on me-1"></span>{{ __('task.time.running') }}
                            <span dir="ltr" data-elapsed-since="{{ $runningEntry->started_at->timestamp }}">00:00:00</span></span>
                        <form action="{{ route('portal.time.stop') }}" method="POST" class="m-0" data-no-confirm>
                            @csrf
                            <button type="submit" class="btn btn-sm btn-danger"><i class="fa-solid fa-stop me-1"></i>{{ __('task.time.stop') }}</button>
                        </form>
                    </div>
                @else
                    <form action="{{ route($routes . '.timer.start', $task->id) }}" method="POST" data-no-confirm>
                        @csrf
                        <button type="submit" class="btn btn-success w-100"><i class="fa-solid fa-play me-1"></i>{{ __('task.time.start') }}</button>
                    </form>
                    @if ($runningEntry)
                        <div class="small text-warning mt-1">{{ __('task.time.running_on', ['title' => $runningEntry->task?->title]) }}</div>
                    @endif
                @endif
                <small class="text-body-secondary d-block mt-1">{{ __('task.time.cap_hint', ['hours' => config('tasks.time.max_timer_hours')]) }}</small>
            </div>
        @endif

        @if (($isEmployee && $canLog) || (! $isEmployee && $abilities['update'] && $assigneesForAdmin->isNotEmpty()))
            <details class="mt-3 pt-3 border-top">
                <summary class="small fw-semibold text-primary" style="cursor: pointer">{{ __('task.time.add_manual') }}</summary>
                <form action="{{ route($routes . '.time.store', $task->id) }}" method="POST" class="mt-2" data-no-confirm>
                    @csrf
                    @unless ($isEmployee)
                        <label for="time_employee" class="form-label small mb-1">{{ __('task.time.employee') }}</label>
                        <select name="employee_id" id="time_employee" class="form-select form-select-sm mb-2" required>
                            @foreach ($assigneesForAdmin as $a)
                                <option value="{{ $a->employee_id }}">{{ $a->employee?->name }}</option>
                            @endforeach
                        </select>
                    @endunless
                    <div class="row g-2">
                        <div class="col-4">
                            <label for="time_hours" class="form-label small mb-1">{{ __('task.time.hours') }}</label>
                            <input type="number" name="hours" id="time_hours" min="0" max="24" class="form-control form-control-sm" dir="ltr" value="0">
                        </div>
                        <div class="col-4">
                            <label for="time_minutes" class="form-label small mb-1">{{ __('task.time.minutes') }}</label>
                            <input type="number" name="minutes" id="time_minutes" min="0" max="59" step="5" class="form-control form-control-sm" dir="ltr" value="30">
                        </div>
                        <div class="col-4">
                            <label for="time_date" class="form-label small mb-1">{{ __('task.time.date') }}</label>
                            <input type="date" name="date" id="time_date" class="form-control form-control-sm" dir="ltr" value="{{ today()->toDateString() }}" max="{{ today()->toDateString() }}" required>
                        </div>
                    </div>
                    <input type="text" name="note" class="form-control form-control-sm mt-2" maxlength="500" placeholder="{{ __('task.time.note') }}" aria-label="{{ __('task.time.note') }}">
                    <button type="submit" class="btn btn-sm btn-primary w-100 mt-2">{{ __('task.time.save') }}</button>
                </form>
            </details>
        @endif
    </div>

    @if ($task->timeEntries->isNotEmpty())
        <ul class="list-group list-group-flush small">
            @foreach ($task->timeEntries as $entry)
                @php $own = $isEmployee && (int) $entry->employee_id === (int) $actor->id; @endphp
                <li class="list-group-item d-flex justify-content-between align-items-center gap-2">
                    <span class="text-break">
                        <strong>{{ \App\Support\Duration::format($entry->minutes) }}</strong> · {{ $own ? __('task.labels_text.you') : $entry->employee?->name }}
                        @if ($entry->is_manual)<span class="badge bg-label-secondary">{{ __('task.time.manual') }}</span>@endif
                        <span class="d-block text-body-secondary" dir="ltr">{{ $entry->started_at->format('Y-m-d') }}</span>
                        @if ($entry->note)<span class="d-block text-body-secondary">{{ $entry->note }}</span>@endif
                    </span>
                    @if ($own || (! $isEmployee && $abilities['update']))
                        <form action="{{ route($routes . '.time.destroy', [$task->id, $entry->id]) }}" method="POST" class="m-0">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-icon btn-text-danger" aria-label="{{ __('global.delete') }}"><i class="fa-solid fa-trash"></i></button>
                        </form>
                    @endif
                </li>
            @endforeach
        </ul>
    @endif
</div>

@once
@push('css')
<style>
    .live-dot { width: .6rem; height: .6rem; border-radius: 50%; display: inline-block; background: var(--bs-secondary); }
    .live-dot.on { background: var(--bs-success); animation: live-pulse 2s infinite; }
    @keyframes live-pulse { 70% { box-shadow: 0 0 0 .5rem rgba(40, 199, 111, 0); } 0% { box-shadow: 0 0 0 0 rgba(40, 199, 111, .6); } }
</style>
@endpush
@push('js')
<script>
    // عدّاد الوقت المنقضي — أي عنصر عليه data-elapsed-since (ثواني Unix)
    (function () {
        const pad = n => String(n).padStart(2, '0');
        function tick() {
            const now = Math.floor(Date.now() / 1000);
            document.querySelectorAll('[data-elapsed-since]').forEach(function (el) {
                const s = Math.max(0, now - parseInt(el.dataset.elapsedSince, 10));
                el.textContent = pad(Math.floor(s / 3600)) + ':' + pad(Math.floor(s % 3600 / 60)) + ':' + pad(s % 60);
            });
        }
        tick();
        setInterval(tick, 1000);
    })();
</script>
@endpush
@endonce
