{{--
    لوحة كانبان — مشتركة بين لوحة الإدارة والبوابة.

    $board       من TaskBoardService::board()
    $routes      'admin.tasks' أو 'portal.tasks'
    $moveUrl     رابط نقل الكارت (فيه __ID__)
    $live        (اختياري) ['channel' => ..., 'cardUrl' => ..., 'authUrl' => ...] للتحديث اللحظي

    السحب بـ SortableJS (لمس على الموبايل كمان). أي نقل غلط بيرجع مكانه
    برسالة السيرفر، والإرجاع من المراجعة بيطلب السبب.
--}}
@include('tasks.partials.styles')
@once
@push('css')
<style>
    .kanban { display: flex; gap: 1rem; overflow-x: auto; padding-bottom: .75rem; scroll-snap-type: x mandatory; align-items: flex-start; }
    .kanban-col { flex: 0 0 min(19rem, 85vw); scroll-snap-align: start; background: var(--bs-tertiary-bg, var(--bs-body-bg)); border-radius: .5rem; }
    @media (min-width: 1200px) { .kanban-col { flex: 1 1 0; min-width: 15rem; } }
    .kanban-col-head { position: sticky; top: 0; z-index: 2; padding: .75rem 1rem; border-bottom: 3px solid var(--col-color); background: inherit; border-radius: .5rem .5rem 0 0; }
    .kanban-list { min-height: 6rem; max-height: calc(100vh - 16rem); overflow-y: auto; padding: .75rem; }
    .board-card { cursor: grab; }
    .board-card:active { cursor: grabbing; }
    .board-card.sortable-ghost { opacity: .35; }
    .board-card.sortable-chosen { box-shadow: var(--bs-box-shadow) !important; }
    .board-card.flash { animation: board-flash 1.6s ease; }
    @keyframes board-flash { 0%, 40% { box-shadow: 0 0 0 3px var(--bs-primary); } 100% { box-shadow: none; } }
    .live-dot { width: .6rem; height: .6rem; border-radius: 50%; display: inline-block; background: var(--bs-secondary); }
    .live-dot.on { background: var(--bs-success); box-shadow: 0 0 0 0 rgba(40, 199, 111, .6); animation: live-pulse 2s infinite; }
    @keyframes live-pulse { 70% { box-shadow: 0 0 0 .5rem rgba(40, 199, 111, 0); } 100% { box-shadow: 0 0 0 0 rgba(40, 199, 111, 0); } }
</style>
@endpush
@endonce

@php
    $colors = ['new' => 'var(--bs-secondary)', 'in_progress' => 'var(--bs-primary)', 'in_review' => 'var(--bs-warning)', 'completed' => 'var(--bs-success)'];
@endphp

<div class="kanban" id="kanban" @if (! empty($live)) data-channel="{{ $live['channel'] }}" @endif>
    @foreach ($board as $key => $column)
        <section class="kanban-col" style="--col-color: {{ $colors[$key] ?? 'var(--bs-primary)' }}" aria-label="{{ $column['status']->label() }}">
            <header class="kanban-col-head d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold">{{ $column['status']->label() }}</h6>
                <span class="badge bg-label-secondary" data-count>{{ $column['total'] }}</span>
            </header>
            <div class="kanban-list" data-status="{{ $key }}">
                @foreach ($column['tasks'] as $task)
                    @include('tasks.partials.board-card', ['task' => $task])
                @endforeach
            </div>
            @if ($key === 'completed')
                <div class="px-3 pb-2 small text-body-secondary">{{ __('task.views.completed_hint', ['days' => \App\Services\Tasks\TaskBoardService::COMPLETED_DAYS]) }}</div>
            @endif
        </section>
    @endforeach
</div>

@push('js')
<script src="/admin/vendor/libs/sortablejs/Sortable.min.js"></script>
@if (! empty($live) && config('broadcasting.default') === 'pusher' && config('broadcasting.connections.pusher.key'))
    <script src="https://js.pusher.com/8.2/pusher.min.js"></script>
@endif
<script>
(function () {
    const root = document.getElementById('kanban');
    if (!root || typeof Sortable === 'undefined') return;

    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
    const moveUrl = @json($moveUrl);
    const T = {
        reasonTitle: @json(__('task.review.return_reason')),
        confirm: @json(__('task.review.confirm')),
        cancel: @json(__('global.cancel')),
    };

    function toast(message, icon) {
        if (typeof Swal === 'undefined') { alert(message); return; }
        Swal.fire({ toast: true, position: 'top', timer: 3000, showConfirmButton: false, icon: icon, title: message });
    }

    function refreshCounts() {
        root.querySelectorAll('.kanban-col').forEach(function (col) {
            col.querySelector('[data-count]').textContent = col.querySelectorAll('.board-card').length;
        });
    }

    function send(item, status, order, note) {
        return fetch(moveUrl.replace('__ID__', item.dataset.id), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({ status: status, order: order, note: note || null })
        }).then(r => r.json().then(d => ({ ok: r.ok, data: d })));
    }

    root.querySelectorAll('.kanban-list').forEach(function (list) {
        Sortable.create(list, {
            group: 'tasks',
            animation: 150,
            delay: 150,
            delayOnTouchOnly: true,
            ghostClass: 'sortable-ghost',
            chosenClass: 'sortable-chosen',
            onEnd: function (evt) {
                const item = evt.item;
                const to = evt.to;
                const status = to.dataset.status;
                const order = Array.from(to.querySelectorAll('.board-card')).map(c => parseInt(c.dataset.id, 10));
                const revert = function () {
                    evt.from.insertBefore(item, evt.from.children[evt.oldIndex] || null);
                    refreshCounts();
                };

                if (evt.from === to && evt.oldIndex === evt.newIndex) return;
                refreshCounts();

                const attempt = function (note) {
                    send(item, status, order, note).then(function (res) {
                        if (res.ok) {
                            if (evt.from !== to) toast(res.data.message, 'success');
                            return;
                        }
                        if (res.data && res.data.needs_note && typeof Swal !== 'undefined') {
                            Swal.fire({
                                title: T.reasonTitle, input: 'textarea', inputAttributes: { maxlength: 1000 },
                                showCancelButton: true, confirmButtonText: T.confirm, cancelButtonText: T.cancel,
                                inputValidator: v => (!v || !v.trim()) ? T.reasonTitle : undefined
                            }).then(function (r) { r.isConfirmed ? attempt(r.value) : revert(); });
                            return;
                        }
                        revert();
                        toast((res.data && (res.data.message || Object.values(res.data.errors || {})[0])) || 'Error', 'error');
                    }).catch(function () { revert(); toast('Error', 'error'); });
                };

                attempt(null);
            }
        });
    });

    @if (! empty($live) && config('broadcasting.default') === 'pusher' && config('broadcasting.connections.pusher.key'))
    // التحديث اللحظي: إشعار برقم المهمة ← نطلب الكارت بفلاتر الشاشة ونحطه في عموده
    const dot = document.getElementById('liveDot');
    const cardUrl = @json($live['cardUrl']);
    const query = window.location.search;

    function upsert(taskId) {
        fetch(cardUrl.replace('__ID__', taskId) + query, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
            .then(r => r.status === 204 ? null : r.json())
            .then(function (d) {
                const existing = root.querySelector('.board-card[data-id="' + taskId + '"]');
                if (!d) { existing?.remove(); refreshCounts(); return; }
                const wrap = document.createElement('div');
                wrap.innerHTML = d.html.trim();
                const card = wrap.firstElementChild;
                const list = root.querySelector('.kanban-list[data-status="' + d.status + '"]');
                if (!list) { existing?.remove(); refreshCounts(); return; }
                if (existing && existing.parentElement === list) {
                    existing.replaceWith(card);
                } else {
                    existing?.remove();
                    list.prepend(card);
                }
                card.classList.add('flash');
                refreshCounts();
            }).catch(() => {});
    }

    try {
        const pusher = new Pusher(@json(config('broadcasting.connections.pusher.key')), {
            cluster: @json(config('broadcasting.connections.pusher.options.cluster')),
            authEndpoint: @json($live['authUrl']),
            auth: { headers: { 'X-CSRF-TOKEN': csrf } }
        });
        pusher.connection.bind('state_change', s => dot?.classList.toggle('on', s.current === 'connected'));
        pusher.subscribe('private-' + @json($live['channel'])).bind('task.changed', d => upsert(d.task_id));
    } catch (e) {
        console.warn('Pusher unavailable:', e);
    }
    @endif
})();
</script>
@endpush
