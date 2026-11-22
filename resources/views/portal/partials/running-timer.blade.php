{{-- عدّاد شغّال؟ يظهر في الشريط العلوي في أي صفحة: اسم المهمة والوقت وزرار إيقاف. --}}
@php
    $runningTimer = \App\Models\TaskTimeEntry::query()->running()->where('employee_id', $me->id)->with('task:id,title')->latest('id')->first();
@endphp
@if ($runningTimer)
    <li class="nav-item me-2 me-xl-3">
        <div class="d-flex align-items-center gap-2 border border-success rounded-pill ps-3 pe-1 py-1">
            <a href="{{ route('portal.tasks.show', $runningTimer->task_id) }}" class="small text-success fw-semibold text-decoration-none d-flex align-items-center gap-1"
               title="{{ __('task.time.running_on', ['title' => $runningTimer->task?->title]) }}">
                <i class="fa-solid fa-stopwatch"></i>
                <span dir="ltr" data-elapsed-since="{{ $runningTimer->started_at->timestamp }}">00:00:00</span>
                <span class="d-none d-lg-inline text-truncate" style="max-width: 10rem">{{ $runningTimer->task?->title }}</span>
            </a>
            <form action="{{ route('portal.time.stop') }}" method="POST" class="m-0" data-no-confirm>
                @csrf
                <button type="submit" class="btn btn-sm btn-icon btn-danger rounded-pill" aria-label="{{ __('task.time.stop') }}" title="{{ __('task.time.stop') }}"><i class="fa-solid fa-stop"></i></button>
            </form>
        </div>
    </li>
    @once
    @push('js')
    <script>
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
@endif
