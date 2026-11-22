{{--
    جرس إشعارات البوابة — نفس فكرة جرس لوحة الإدارة:
    القائمة بتتحمّل لما تتفتح، والعداد بيتحدّث لحظياً عبر Pusher
    على قناة الموظف الخاصة (private-employee.{id}).
--}}
@php
    $unreadPortal = \App\Models\EmployeeNotification::where('employee_id', $me->id)->whereNull('read_at')->count();
@endphp
<li class="nav-item dropdown me-2 me-xl-3" id="portalNotificationsBell">
    <a class="nav-link dropdown-toggle hide-arrow position-relative" href="javascript:void(0);" data-bs-toggle="dropdown"
       data-bs-auto-close="outside" role="button" aria-label="{{ __('notification.title') }}" title="{{ __('notification.title') }}">
        <i class="fa-solid fa-bell fa-lg" aria-hidden="true"></i>
        <span class="badge rounded-pill bg-danger position-absolute top-0 start-100 translate-middle {{ $unreadPortal ? '' : 'd-none' }}" id="portalNotificationsCount">{{ $unreadPortal }}</span>
    </a>
    <ul class="dropdown-menu dropdown-menu-end p-0" style="width: min(22rem, 92vw)">
        <li class="dropdown-header border-bottom d-flex justify-content-between align-items-center py-3 px-3">
            <span class="fw-bold">{{ __('notification.title') }}</span>
            <button type="button" class="btn btn-sm btn-label-primary py-0 px-2" id="portalMarkAllRead">{{ __('notification.mark_all') }}</button>
        </li>
        <li>
            <div id="portalNotificationsList" style="max-height: 340px; overflow-y: auto;">
                <div class="text-center text-muted py-4"><div class="spinner-border spinner-border-sm"></div></div>
            </div>
        </li>
        <li class="border-top text-center py-2">
            <a href="{{ route('portal.notifications.index') }}" class="small fw-semibold">{{ __('notification.view_all') }}</a>
        </li>
    </ul>
</li>

@push('js')
@php
    $pusherEnabled = config('broadcasting.default') === 'pusher' && config('broadcasting.connections.pusher.key');
@endphp
@if ($pusherEnabled)
    <script src="https://js.pusher.com/8.2/pusher.min.js"></script>
@endif
<script>
(function () {
    const bell = document.getElementById('portalNotificationsBell');
    if (!bell) return;
    const listEl = document.getElementById('portalNotificationsList');
    const countEl = document.getElementById('portalNotificationsCount');
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
    const T = { empty: @json(__('notification.empty')), emptyHint: @json(__('notification.empty_hint')) };
    const esc = (v) => String(v ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');

    function setCount(n) {
        countEl.textContent = n;
        countEl.classList.toggle('d-none', !n);
    }

    function render(items) {
        if (!items || !items.length) {
            listEl.innerHTML = '<div class="text-center text-muted py-4 px-3"><i class="fa-solid fa-bell-slash mb-2" style="font-size:1.8rem;opacity:.3"></i>'
                + '<div class="small fw-semibold">' + esc(T.empty) + '</div><div class="small">' + esc(T.emptyHint) + '</div></div>';
            return;
        }
        listEl.innerHTML = items.map(function (n) {
            return '<a href="' + esc(n.url || '#') + '" data-id="' + n.id + '" data-unread="' + (n.is_read ? '0' : '1') + '"'
                + ' class="d-flex align-items-start gap-2 px-3 py-2 border-bottom text-body portal-notification ' + (n.is_read ? '' : 'bg-label-primary bg-opacity-10') + '">'
                + '<span class="badge bg-label-' + esc(n.level) + ' rounded p-2 flex-shrink-0"><i class="' + esc(n.icon) + '"></i></span>'
                + '<span class="flex-grow-1 small"><span class="d-block fw-semibold">' + esc(n.title) + '</span>'
                + (n.body ? '<span class="d-block text-muted text-break">' + esc(n.body) + '</span>' : '')
                + '<span class="d-block text-muted" style="font-size:.72rem">' + esc(n.human_time) + '</span></span></a>';
        }).join('');
    }

    function load() {
        fetch(@json(route('portal.notifications.latest')), { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } })
            .then(r => r.ok ? r.json() : Promise.reject(r.status))
            .then(d => { render(d.notifications); setCount(d.unread_count); })
            .catch(() => { listEl.innerHTML = ''; });
    }

    bell.addEventListener('shown.bs.dropdown', load);

    const readUrl = @json(route('portal.notifications.read', ['notification' => '__ID__']));
    listEl.addEventListener('click', function (e) {
        const item = e.target.closest('.portal-notification');
        if (!item || item.dataset.unread !== '1') return;
        e.preventDefault();
        const href = item.getAttribute('href');
        fetch(readUrl.replace('__ID__', item.dataset.id), {
            method: 'POST', keepalive: true,
            headers: { 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        }).then(r => r.ok ? r.json() : null).then(d => { if (d) setCount(d.unread_count); }).catch(() => {})
          .finally(() => { if (href && href !== '#') window.location.href = href; });
    });

    document.getElementById('portalMarkAllRead')?.addEventListener('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        fetch(@json(route('portal.notifications.read-all')), {
            method: 'POST', headers: { 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        }).then(() => { setCount(0); load(); });
    });

    @if ($pusherEnabled)
    try {
        const pusher = new Pusher(@json(config('broadcasting.connections.pusher.key')), {
            cluster: @json(config('broadcasting.connections.pusher.options.cluster')),
            authEndpoint: @json(route('portal.broadcasting.auth')),
            auth: { headers: { 'X-CSRF-TOKEN': csrf } }
        });
        pusher.subscribe('private-employee.' + @json($me->id)).bind('notification.created', function (data) {
            setCount(data.unread_count);
            if (bell.querySelector('.dropdown-menu.show')) load();
            // صفحة المهمة المفتوحة نفسها بتتحدّث (تعليق أو تغيير حالة جديد)
            document.dispatchEvent(new CustomEvent('portal:notification', { detail: data.notification }));
        });
    } catch (e) {
        console.warn('Pusher unavailable:', e);
    }
    @endif
})();
</script>
@endpush
