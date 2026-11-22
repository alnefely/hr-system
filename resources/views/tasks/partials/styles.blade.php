{{--
    تنسيقات موديول المهام — مشتركة بين لوحة الإدارة والبوابة.
    الألوان من متغيرات Bootstrap فبتشتغل في الوضع الليلي من غير قواعد إضافية.
--}}
@once
@push('css')
<style>
    .task-progress { position: relative; height: .5rem; border-radius: 1rem; background: var(--bs-secondary-bg); overflow: visible; }
    .task-progress .bar { height: 100%; border-radius: 1rem; transition: width .3s ease; }
    .task-progress .expected { position: absolute; top: -.25rem; bottom: -.25rem; width: 2px; background: var(--bs-body-color); opacity: .55; border-radius: 1px; }
    [dir='rtl'] .task-progress .bar { margin-inline-start: 0; }
    .task-progress-lg { height: .75rem; }
    .task-title-cell { min-width: 14rem; max-width: 28rem; white-space: normal; }
    .task-title-cell a { color: var(--bs-heading-color); }
    .task-title-cell a:hover { color: var(--bs-primary); }
    .task-meta { font-size: .8125rem; color: var(--bs-secondary-color); }
    .task-meta i { opacity: .8; }
    .task-label { display: inline-block; font-size: .6875rem; padding: .1rem .45rem; border-radius: .25rem; color: #fff; line-height: 1.4; }
    .avatar-group .avatar { width: 2rem; height: 2rem; }
    .avatar-group .avatar-initial { font-size: .75rem; border: 2px solid var(--bs-paper-bg, var(--bs-body-bg)); }
    .task-card { transition: box-shadow .15s ease, transform .15s ease; }
    .task-card:hover { box-shadow: var(--bs-box-shadow) !important; transform: translateY(-1px); }
    .task-card .stretched-link::after { z-index: 0; }
    .task-card .task-card-actions { position: relative; z-index: 1; }
    .task-checklist .form-check-input { cursor: pointer; margin-top: .2rem; }
    .task-checklist .done label { text-decoration: line-through; color: var(--bs-secondary-color); }
    .task-timeline { list-style: none; padding: 0; margin: 0; }
    .task-timeline li { position: relative; padding-inline-start: 1.5rem; padding-bottom: 1rem; }
    .task-timeline li::before { content: ''; position: absolute; inset-inline-start: .3rem; top: .35rem; bottom: -.35rem; width: 2px; background: var(--bs-border-color); }
    .task-timeline li:last-child::before { display: none; }
    .task-timeline li .dot { position: absolute; inset-inline-start: 0; top: .25rem; width: .75rem; height: .75rem; border-radius: 50%; background: var(--bs-primary); }
    .task-description { white-space: pre-line; word-break: break-word; }
    .task-stat .icon { width: 2.5rem; height: 2.5rem; display: inline-flex; align-items: center; justify-content: center; border-radius: .5rem; }
    .task-stat { color: inherit; text-decoration: none; }
    .task-stat.active .card { outline: 2px solid var(--bs-primary); }
    @media (max-width: 575.98px) {
        .task-status-actions .btn { flex: 1 1 calc(50% - .5rem); }
        .task-title-cell { min-width: 11rem; }
    }
</style>
@endpush
@endonce
