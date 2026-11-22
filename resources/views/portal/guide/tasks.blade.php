@extends('layouts.portal.app')
@section('title', __('portal.nav.guide'))

{{--
    دليل الموظف للمهام والمشاريع.
    الأرقام (حجم المرفق، حد العدّاد، تذكير قرب التسليم) جاية من config/tasks.php
    عشان الدليل يفضل مطابق لو الإعدادات اتغيّرت على السيرفر.
--}}
@php
    $maxFiles   = (int) config('tasks.attachments.max_files', 5);
    $maxMb      = round(config('tasks.attachments.max_kb', 10240) / 1024);
    $timerHours = (int) config('tasks.time.max_timer_hours', 12);
    $dueSoon    = (int) config('tasks.reminders.due_soon_hours', 24);

    $toc = [
        ['g-where',      'fa-compass',              'المهام فين في البوابة'],
        ['g-tabs',       'fa-folder-tree',          'شاشة «مهامي» وتبويباتها'],
        ['g-lifecycle',  'fa-arrows-spin',          'حالات المهمة'],
        ['g-start',      'fa-play',                 'اتكلّفت بمهمة — أعمل إيه؟'],
        ['g-progress',   'fa-percent',              'نسبة الإنجاز'],
        ['g-review',     'fa-clipboard-check',      'التسليم والمراجعة'],
        ['g-decline',    'fa-hand',                 'الاعتذار عن مهمة'],
        ['g-create',     'fa-plus',                 'إنشاء مهمة بنفسك'],
        ['g-collab',     'fa-comments',             'التعليقات والمرفقات'],
        ['g-time',       'fa-stopwatch',            'تتبع الوقت'],
        ['g-views',      'fa-table-columns',        'اللوحة والتقويم'],
        ['g-projects',   'fa-diagram-project',      'مشاريعي'],
        ['g-manager',    'fa-user-tie',             'لو انت مدير مباشر'],
        ['g-notify',     'fa-bell',                 'الإشعارات والتذكيرات'],
        ['g-faq',        'fa-circle-question',      'أسئلة ورسائل شائعة'],
    ];
@endphp

@push('css')
<style>
    /* الثيم بيوسّط روابط nav-pills — بنرجّعها لبداية السطر زي دليل الإدارة */
    #guideToc .nav-link { display: flex; justify-content: flex-start; align-items: flex-start; gap: .5rem; text-align: start; line-height: 1.6; }
    #guideToc .nav-link > i { flex: 0 0 1.25rem; width: 1.25rem; text-align: center; margin: .15rem 0 0; }
    #guideToc .nav-link > .toc-text { flex: 1 1 auto; min-width: 0; }
    .guide-card { scroll-margin-top: 90px; }
    .guide-flow { display: flex; flex-wrap: wrap; align-items: center; gap: .5rem; }
    .guide-flow .arrow { color: var(--bs-secondary-color); }
    .guide-step { display: flex; gap: .75rem; }
    .guide-step .num {
        flex: 0 0 2rem; height: 2rem; border-radius: 50%;
        display: inline-flex; align-items: center; justify-content: center;
        font-weight: 700; background: var(--bs-primary-bg-subtle); color: var(--bs-primary);
    }
</style>
@endpush

@section('content')
<div class="row">

    {{-- العنوان --}}
    <div class="col-12 mb-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4 d-flex flex-wrap align-items-center gap-3">
                <span class="badge bg-label-primary p-3"><i class="fa-solid fa-book-open fs-3"></i></span>
                <div class="flex-grow-1">
                    <h4 class="fw-bold mb-1">دليل المهام والمشاريع</h4>
                    <p class="text-body-secondary mb-0">
                        كل اللي محتاج تعرفه عشان تستلم مهامك، تتابعها، وتسلّمها — خطوة بخطوة.
                    </p>
                </div>
                <a href="{{ route('portal.tasks.index') }}" class="btn btn-primary">
                    <i class="fa-solid fa-list-check me-1"></i> روح لمهامي
                </a>
            </div>
        </div>
    </div>

    {{-- المحتويات --}}
    <div class="col-lg-3 mb-4">
        <div class="card border-0 shadow-sm sticky-top" style="top: 85px;">
            <div class="card-header border-bottom py-3">
                <h6 class="card-title mb-0 fw-bold"><i class="fa-solid fa-list-ol me-2 text-primary"></i> المحتويات</h6>
            </div>
            <div class="card-body p-2" style="max-height: calc(100vh - 190px); overflow-y: auto;">
                <nav class="nav nav-pills flex-column gap-1" id="guideToc">
                    @foreach ($toc as [$id, $icon, $label])
                        <a class="nav-link py-2 px-3" href="#{{ $id }}"><i class="fa-solid {{ $icon }}"></i><span class="toc-text">{{ $label }}</span></a>
                    @endforeach
                </nav>
            </div>
        </div>
    </div>

    <div class="col-lg-9">

        {{-- فين --}}
        <div class="card border-0 shadow-sm mb-4 guide-card" id="g-where">
            <div class="card-header border-bottom">
                <h5 class="card-title mb-0 fw-bold text-primary"><i class="fa-solid fa-compass me-2"></i> المهام فين في البوابة</h5>
            </div>
            <div class="card-body pt-4">
                <p class="mb-3">من القائمة الجانبية هتلاقي أربع شاشات للمهام:</p>
                <div class="table-responsive">
                    <table class="table table-bordered align-middle mb-0">
                        <thead><tr><th style="width:26%">الشاشة</th><th>فيها إيه</th></tr></thead>
                        <tbody>
                            <tr><td class="fw-bold"><i class="fa-solid fa-list-check me-1 text-primary"></i> {{ __('portal.nav.tasks') }}</td><td class="small">كل المهام اللي ليك علاقة بيها: المكلّف بيها، اللي أنشأتها، مهام فريقك، واللي مستنية مراجعتك.</td></tr>
                            <tr><td class="fw-bold"><i class="fa-solid fa-diagram-project me-1 text-primary"></i> {{ __('portal.nav.projects') }}</td><td class="small">المشاريع اللي انت مديرها أو عضو فيها، ونسبة إنجاز كل مشروع ومهامه.</td></tr>
                            <tr><td class="fw-bold"><i class="fa-solid fa-stopwatch me-1 text-primary"></i> {{ __('portal.nav.time') }}</td><td class="small">الوقت اللي سجّلته على المهام أسبوع بأسبوع — حسب اليوم وحسب المهمة.</td></tr>
                            <tr><td class="fw-bold"><i class="fa-solid fa-chart-column me-1 text-primary"></i> {{ __('portal.nav.performance') }}</td><td class="small">أرقامك: المهام المكتملة، الالتزام بالمواعيد، الوقت الفعلي مقابل المتوقع — ولو عندك فريق، أداء فريقك.</td></tr>
                        </tbody>
                    </table>
                </div>
                <div class="alert alert-info mt-3 mb-0 small">
                    <i class="fa-solid fa-circle-info me-1"></i>
                    في الشريط العلوي هتلاقي <strong>جرس الإشعارات</strong>، ولو فيه عدّاد وقت شغّال هيظهر جنبه عشان توقفه من أي صفحة.
                </div>
            </div>
        </div>

        {{-- التبويبات --}}
        <div class="card border-0 shadow-sm mb-4 guide-card" id="g-tabs">
            <div class="card-header border-bottom">
                <h5 class="card-title mb-0 fw-bold text-primary"><i class="fa-solid fa-folder-tree me-2"></i> شاشة «مهامي» وتبويباتها</h5>
            </div>
            <div class="card-body pt-4">
                <p class="mb-3">
                    فوق الشاشة <strong>أربع بطاقات</strong> بتلخّص المهام المكلّف بيها:
                    <span class="badge bg-label-primary">مفتوحة</span>
                    <span class="badge bg-label-info">قيد التنفيذ</span>
                    <span class="badge bg-label-warning">قيد المراجعة</span>
                    <span class="badge bg-label-danger">متأخرة</span>
                    — اضغط على أي بطاقة وهتتفلتر القائمة عليها.
                </p>
                <div class="table-responsive mb-3">
                    <table class="table table-bordered align-middle mb-0">
                        <thead><tr><th style="width:26%">التبويب</th><th>بيعرض إيه</th></tr></thead>
                        <tbody>
                            <tr><td class="fw-bold">{{ __('task.tabs.assigned') }}</td><td class="small">المهام اللي اتكلّفت بيها — ده التبويب الافتراضي.</td></tr>
                            <tr><td class="fw-bold">{{ __('task.tabs.review') }}</td><td class="small">بيظهر بس لما يكون فيه مهام <strong>متسلّمة ومستنية مراجعتك</strong>، وجنبه عددها.</td></tr>
                            <tr><td class="fw-bold">{{ __('task.tabs.team') }}</td><td class="small">بيظهر للمدير المباشر بس: المهام المكلّف بيها الموظفين اللي تحت إدارتك.</td></tr>
                            <tr><td class="fw-bold">{{ __('task.tabs.created') }}</td><td class="small">المهام اللي انت عملتها (لنفسك أو لزمايلك).</td></tr>
                            <tr><td class="fw-bold">{{ __('task.tabs.watching') }}</td><td class="small">مهام انت متابع ليها من غير ما تكون مكلّف بيها.</td></tr>
                        </tbody>
                    </table>
                </div>
                <p class="small text-body-secondary mb-0">
                    القائمة بتعرض <strong>المفتوحة</strong> افتراضياً. تقدر تبحث بالعنوان أو الكود وتفلتر بالحالة والأولوية والمشروع وموعد التسليم.
                    المهام <span class="badge bg-label-danger">العاجلة</span> و<span class="badge bg-label-warning">العالية</span> بتظهر أول القائمة.
                </p>
            </div>
        </div>

        {{-- الحالات --}}
        <div class="card border-0 shadow-sm mb-4 guide-card" id="g-lifecycle">
            <div class="card-header border-bottom">
                <h5 class="card-title mb-0 fw-bold text-primary"><i class="fa-solid fa-arrows-spin me-2"></i> حالات المهمة</h5>
            </div>
            <div class="card-body pt-4">
                <div class="guide-flow mb-4">
                    <span class="badge bg-label-secondary fs-6">جديدة</span><i class="fa-solid fa-arrow-left arrow"></i>
                    <span class="badge bg-label-primary fs-6">قيد التنفيذ</span><i class="fa-solid fa-arrow-left arrow"></i>
                    <span class="badge bg-label-warning fs-6">قيد المراجعة</span><i class="fa-solid fa-arrow-left arrow"></i>
                    <span class="badge bg-label-success fs-6">مكتملة</span>
                    <span class="text-body-secondary small ms-2">— وفي أي وقت ممكن تبقى</span>
                    <span class="badge bg-label-dark fs-6">ملغية</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered align-middle mb-0">
                        <thead><tr><th style="width:22%">الحالة</th><th>معناها</th></tr></thead>
                        <tbody>
                            <tr><td><span class="badge bg-label-secondary">جديدة</span></td><td class="small">اتعملت ولسه الشغل مابدأش.</td></tr>
                            <tr><td><span class="badge bg-label-primary">قيد التنفيذ</span></td><td class="small">بدأت الشغل — بالزرار <strong>«{{ __('task.actions.start') }}»</strong> أو تلقائياً أول ما تحدّث النسبة لأكتر من 0%.</td></tr>
                            <tr><td><span class="badge bg-label-warning">قيد المراجعة</span></td><td class="small">سلّمتها وهي مستنية حد يعتمدها أو يرجّعها.</td></tr>
                            <tr><td><span class="badge bg-label-success">مكتملة</span></td><td class="small">اتعتمدت أو اتقفلت — النسبة بتبقى 100% تلقائي.</td></tr>
                            <tr><td><span class="badge bg-label-dark">ملغية</span></td><td class="small">اتلغت من صاحب المهمة. الإلغاء وإعادة الفتح لصاحب القرار بس، مش للمكلّف.</td></tr>
                        </tbody>
                    </table>
                </div>
                <div class="alert alert-warning mt-3 mb-0 small">
                    <i class="fa-solid fa-triangle-exclamation me-1"></i>
                    لو المهمة مكتوب عليها <strong>«{{ __('task.labels_text.blocked') }}»</strong> يبقى مربوطة بمهمة لازم تخلص الأول —
                    مش هتقدر تبدأها ولا تسلّمها إلا لما المهمة التانية تكتمل، وهيوصلك إشعار أول ما ده يحصل.
                </div>
            </div>
        </div>

        {{-- البداية --}}
        <div class="card border-0 shadow-sm mb-4 guide-card" id="g-start">
            <div class="card-header border-bottom">
                <h5 class="card-title mb-0 fw-bold text-primary"><i class="fa-solid fa-play me-2"></i> اتكلّفت بمهمة — أعمل إيه؟</h5>
            </div>
            <div class="card-body pt-4">
                <div class="d-flex flex-column gap-3">
                    <div class="guide-step"><span class="num">1</span><div><strong>افتح الإشعار أو المهمة.</strong> <span class="small text-body-secondary">اقرا الوصف، موعد التسليم، الأولوية، والخطوات لو موجودة. لو فيه حاجة مش واضحة اسأل في التعليقات.</span></div></div>
                    <div class="guide-step"><span class="num">2</span><div><strong>اضغط «{{ __('task.actions.start') }}».</strong> <span class="small text-body-secondary">المهمة بتنتقل لقيد التنفيذ، واللي أنشأها بيعرف إنك بدأت.</span></div></div>
                    <div class="guide-step"><span class="num">3</span><div><strong>اشتغل وسجّل تقدّمك.</strong> <span class="small text-body-secondary">علّم على الخطوات اللي خلصت، أو حدّث النسبة بنفسك، وشغّل العدّاد وانت شغال (أو سجّل الوقت يدوي بعدين).</span></div></div>
                    <div class="guide-step"><span class="num">4</span><div><strong>سلّم.</strong> <span class="small text-body-secondary">لما تخلص اضغط <strong>«{{ __('task.actions.send_to_review') }}»</strong> واكتب ملاحظة بالمطلوب مراجعته. لو المهمة مش محتاجة مراجعة هتلاقي <strong>«{{ __('task.actions.complete') }}»</strong> بدلها.</span></div></div>
                </div>
                <div class="alert alert-primary mt-4 mb-0 small">
                    <i class="fa-solid fa-lightbulb me-1"></i>
                    الأزرار اللي بتظهر لك في شاشة المهمة <strong>على حسب دورك فيها</strong> — المكلّف مايشوفش زرار الإلغاء أو تعديل المكلّفين مثلاً.
                    لو زرار مش ظاهر يبقى العملية دي مش من دورك.
                </div>
            </div>
        </div>

        {{-- النسبة --}}
        <div class="card border-0 shadow-sm mb-4 guide-card" id="g-progress">
            <div class="card-header border-bottom">
                <h5 class="card-title mb-0 fw-bold text-primary"><i class="fa-solid fa-percent me-2"></i> نسبة الإنجاز</h5>
            </div>
            <div class="card-body pt-4">
                <p class="mb-3">اللي أنشأ المهمة بيحدد النسبة بتتحسب إزاي:</p>
                <div class="table-responsive mb-3">
                    <table class="table table-bordered align-middle mb-0">
                        <thead><tr><th style="width:30%">طريقة الحساب</th><th>انت بتعمل إيه</th></tr></thead>
                        <tbody>
                            <tr><td class="fw-bold">{{ __('task.progress_source_enums.manual') }}</td><td class="small">اضغط <strong>«{{ __('task.actions.update_progress') }}»</strong> واكتب النسبة وملاحظة قصيرة عن اللي اتعمل. كل تحديث بيتسجّل في «{{ __('task.labels_text.progress_log') }}».</td></tr>
                            <tr><td class="fw-bold">{{ __('task.progress_source_enums.checklist') }}</td><td class="small">علّم ✓ على كل خطوة تخلص — النسبة = الخطوات المنتهية ÷ كل الخطوات. مش هتقدر تكتب النسبة بإيدك.</td></tr>
                            <tr><td class="fw-bold">{{ __('task.progress_source_enums.subtasks') }}</td><td class="small">النسبة متوسط نسب المهام الفرعية (المكتملة = 100%، والملغية مابتتحسبش).</td></tr>
                        </tbody>
                    </table>
                </div>

                <h6 class="fw-bold mb-2">النسبة المستهدفة — انت ماشي كويس؟</h6>
                <p class="small mb-2">
                    لو المهمة ليها بداية وموعد تسليم، النظام بيحسب <strong>المفروض تكون وصلت كام لحد النهاردة</strong>
                    (على افتراض إن الشغل متوزّع بالتساوي على المدة)، وبيقارنها بنسبتك:
                </p>
                <div class="d-flex flex-wrap gap-2 mb-3">
                    <span class="badge bg-label-success">{{ __('task.progress_health.ahead') }} — أعلى من المستهدف بـ 10% أو أكتر</span>
                    <span class="badge bg-label-info">{{ __('task.progress_health.on_track') }} — فرق أقل من 10%</span>
                    <span class="badge bg-label-danger">{{ __('task.progress_health.behind') }} — أقل من المستهدف بـ 10% أو أكتر</span>
                </div>
                <p class="small text-body-secondary mb-0">
                    مثال: مهمة مدتها 10 أيام وعدّى منها 5 — المستهدف 50%. لو نسبتك 30% هتظهر «متأخر عن الخطة».
                </p>

                <div class="alert alert-info mt-3 mb-0 small">
                    <i class="fa-solid fa-bell me-1"></i>
                    لو المهمة عليها <strong>«{{ __('task.fields.progress_update_interval') }}»</strong> (يومياً، أسبوعياً، أو كل عدد أيام)
                    هيوصلك تذكير لما ييجي ميعاد التحديث — والتذكير الجاي بيتحسب من آخر مرة حدّثت فيها.
                </div>
            </div>
        </div>

        {{-- المراجعة --}}
        <div class="card border-0 shadow-sm mb-4 guide-card" id="g-review">
            <div class="card-header border-bottom">
                <h5 class="card-title mb-0 fw-bold text-primary"><i class="fa-solid fa-clipboard-check me-2"></i> التسليم والمراجعة</h5>
            </div>
            <div class="card-body pt-4">
                <p class="mb-3">
                    المهام اللي عليها <strong>«{{ __('task.fields.requires_review') }}»</strong> مش بتتقفل من المكلّف —
                    بيسلّمها، وحد تاني يعتمدها.
                </p>
                <h6 class="fw-bold mb-2">مين بيراجع؟</h6>
                <ul class="small mb-3">
                    <li>اللي <strong>أنشأ المهمة</strong> — ده الطبيعي.</li>
                    <li>لو انت عملت المهمة <strong>لنفسك</strong>: مديرك المباشر.</li>
                    <li>لو عملتها لنفسك ومالكش مدير مباشر: مفيش مراجع، وتقدر تقفلها مباشرة.</li>
                    <li>مدير المشروع والمدير المباشر لأي مكلّف يقدروا يعتمدوا كمان.</li>
                </ul>
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="alert alert-success h-100 mb-0">
                            <h6 class="alert-heading fw-bold"><i class="fa-solid fa-check me-1"></i> اتعتمدت</h6>
                            <p class="small mb-0">المهمة بتبقى مكتملة، والنسبة 100%، ويوصلك إشعار.</p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="alert alert-warning h-100 mb-0">
                            <h6 class="alert-heading fw-bold"><i class="fa-solid fa-rotate-left me-1"></i> رجعت للتنفيذ</h6>
                            <p class="small mb-0">المراجع <strong>لازم يكتب السبب</strong>. هتلاقيه في الإشعار وفي قسم «{{ __('task.review.title') }}» في المهمة — عدّل وسلّم تاني.</p>
                        </div>
                    </div>
                </div>
                <p class="small text-body-secondary mt-3 mb-0">
                    كل تسليم وقرار بيتسجّل في «{{ __('task.review.title') }}» باسم صاحبه وملاحظته — فتقدر ترجع لتاريخ المراجعات في أي وقت.
                </p>
            </div>
        </div>

        {{-- الاعتذار --}}
        <div class="card border-0 shadow-sm mb-4 guide-card" id="g-decline">
            <div class="card-header border-bottom">
                <h5 class="card-title mb-0 fw-bold text-primary"><i class="fa-solid fa-hand me-2"></i> الاعتذار عن مهمة</h5>
            </div>
            <div class="card-body pt-4">
                <p class="small mb-2">
                    لو مش هتقدر تنفّذ مهمة اتكلّفت بيها (ضغط شغل، مش تخصصك…) اضغط <strong>«{{ __('task.actions.decline') }}»</strong>
                    واكتب السبب. اللي أنشأ المهمة بيوصله إشعار بالسبب عشان يكلّف حد تاني.
                </p>
                <ul class="small mb-0">
                    <li>بعد الاعتذار بتطلع من المكلّفين وحالتك بتبقى <span class="badge bg-label-danger">اعتذر</span>.</li>
                    <li>لو اتكلّفت بيها تاني بعد كده، التكليف بيرجع فعّال عادي.</li>
                    <li>مينفعش تعتذر عن مهمة انت اللي أنشأتها.</li>
                </ul>
            </div>
        </div>

        {{-- الإنشاء --}}
        <div class="card border-0 shadow-sm mb-4 guide-card" id="g-create">
            <div class="card-header border-bottom">
                <h5 class="card-title mb-0 fw-bold text-primary"><i class="fa-solid fa-plus me-2"></i> إنشاء مهمة بنفسك</h5>
            </div>
            <div class="card-body pt-4">
                <p class="small mb-3">
                    من «{{ __('portal.nav.tasks') }}» اضغط <strong>«{{ __('task.actions.add_new') }}»</strong>. تقدر تعمل مهمة لنفسك (تنظّم بيها شغلك)
                    أو تكلّف زمايلك. المهمة بتتعمل في فرعك — أو في فرع المشروع لو اخترت مشروع.
                </p>
                <div class="table-responsive mb-3">
                    <table class="table table-bordered align-middle mb-0 small">
                        <thead><tr><th style="width:30%">الحقل</th><th>ملاحظة</th></tr></thead>
                        <tbody>
                            <tr><td class="fw-bold">{{ __('task.fields.title') }}</td><td>{{ __('task.hints.title') }}</td></tr>
                            <tr><td class="fw-bold">{{ __('task.fields.project') }}</td><td>{{ __('task.hints.project') }} (لازم تكون مدير أو عضو في المشروع — المشاهد مايضيفش).</td></tr>
                            <tr><td class="fw-bold">{{ __('task.fields.assignees') }}</td><td>زمايلك في نفس الفرع، أو أعضاء المشروع حتى لو من فرع تاني.</td></tr>
                            <tr><td class="fw-bold">{{ __('task.fields.due_at') }}</td><td>{{ __('task.hints.due_at') }}</td></tr>
                            <tr><td class="fw-bold">{{ __('task.fields.estimated_hours') }}</td><td>{{ __('task.hints.estimated_hours') }}</td></tr>
                            <tr><td class="fw-bold">{{ __('task.fields.requires_review') }}</td><td>{{ __('task.hints.requires_review') }}</td></tr>
                            <tr><td class="fw-bold">{{ __('task.fields.checklist') }}</td><td>{{ __('task.hints.checklist') }}</td></tr>
                        </tbody>
                    </table>
                </div>
                <h6 class="fw-bold mb-2">بعد ما تنشئها — انت صاحب القرار فيها</h6>
                <ul class="small mb-3">
                    <li>تعدّل بياناتها ومكلّفينها، تلغيها أو تعيد فتحها، وتحذفها.</li>
                    <li>تعتمد أو ترجّع لما المكلّف يسلّمها.</li>
                    <li><strong>تربطها</strong> بمهمة تانية من قسم «{{ __('task.dependencies.title') }}» ← «{{ __('task.dependencies.add') }}»: {{ __('task.dependencies.hint') }} (لازم تكون في نفس الفرع أو المشروع.)</li>
                </ul>
                <div class="alert alert-info mb-0 small">
                    <i class="fa-solid fa-sitemap me-1"></i>
                    <strong>مهمة كبيرة؟</strong> قسّمها: من شاشة المهمة <strong>«{{ __('task.actions.add_subtask') }}»</strong>.
                    المكلّف نفسه يقدر يضيف مهام فرعية وخطوات، مش صاحب المهمة بس.
                </div>
            </div>
        </div>

        {{-- التواصل --}}
        <div class="card border-0 shadow-sm mb-4 guide-card" id="g-collab">
            <div class="card-header border-bottom">
                <h5 class="card-title mb-0 fw-bold text-primary"><i class="fa-solid fa-comments me-2"></i> التعليقات والمرفقات</h5>
            </div>
            <div class="card-body pt-4">
                <div class="row g-3">
                    <div class="col-md-6">
                        <h6 class="fw-bold"><i class="fa-solid fa-comment me-1 text-primary"></i> التعليقات</h6>
                        <ul class="small mb-0">
                            <li>اكتب سؤالك أو تحديثك في المهمة بدل الرسائل الجانبية — كل الفريق بيشوف نفس النقاش.</li>
                            <li>تقدر <strong>ترد</strong> على تعليق معيّن، و<strong>تعدّل أو تحذف</strong> تعليقك (التعديل بيظهر جنبه «{{ __('task.comments.edited') }}»).</li>
                            <li><strong>الإشارة (&#64;)</strong>: اختار زميل من «{{ __('task.comments.mention') }}» ويوصله إشعار مخصوص. المتاحين: المكلّفين، صاحب المهمة، أعضاء المشروع ومديريهم المباشرين.</li>
                            <li>كل تعليق جديد بيوصل للمكلّفين وصاحب المهمة.</li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h6 class="fw-bold"><i class="fa-solid fa-paperclip me-1 text-primary"></i> المرفقات</h6>
                        <ul class="small mb-0">
                            <li>ارفع على المهمة نفسها أو مع التعليق.</li>
                            <li>لحد <strong>{{ $maxFiles }} ملفات</strong> في المرة، والملف لحد <strong>{{ $maxMb }} ميجا</strong>.</li>
                            <li>المسموح: صور، PDF، Word، Excel، PowerPoint، نصوص وCSV، وZIP.</li>
                            <li>الملفات خاصة — مابيفتحهاش غير اللي يقدر يشوف المهمة.</li>
                            <li>تقدر تحذف الملف اللي رفعته انت.</li>
                        </ul>
                    </div>
                </div>
                <p class="small text-body-secondary mt-3 mb-0">
                    <i class="fa-solid fa-clock-rotate-left me-1"></i>
                    «{{ __('task.labels_text.timeline') }}» في آخر الشاشة بيسجّل كل حاجة حصلت: مين كلّف مين، تغيير الحالة، النسبة، الخطوات، الملفات، والوقت.
                </p>
            </div>
        </div>

        {{-- الوقت --}}
        <div class="card border-0 shadow-sm mb-4 guide-card" id="g-time">
            <div class="card-header border-bottom">
                <h5 class="card-title mb-0 fw-bold text-primary"><i class="fa-solid fa-stopwatch me-2"></i> تتبع الوقت</h5>
            </div>
            <div class="card-body pt-4">
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <div class="border rounded p-3 h-100">
                            <h6 class="fw-bold"><i class="fa-solid fa-play me-1 text-success"></i> بالعدّاد</h6>
                            <ul class="small mb-0">
                                <li>من شاشة المهمة: <strong>«{{ __('task.time.start') }}»</strong>.</li>
                                <li>عدّاد <strong>واحد بس</strong> شغّال في نفس الوقت — لو شغّلت على مهمة تانية، الأولاني بيقف ويتسجّل لوحده.</li>
                                <li>بيظهر في الشريط العلوي وتقدر توقفه من أي صفحة.</li>
                                <li>لو نسيته شغّال، بيتحسب بحد أقصى <strong>{{ $timerHours }} ساعة</strong> بس.</li>
                            </ul>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="border rounded p-3 h-100">
                            <h6 class="fw-bold"><i class="fa-solid fa-pen me-1 text-primary"></i> يدوي</h6>
                            <ul class="small mb-0">
                                <li><strong>«{{ __('task.time.add_manual') }}»</strong>: الساعات والدقائق والتاريخ وملاحظة.</li>
                                <li>من دقيقة لـ 24 ساعة في السجل الواحد.</li>
                                <li>مينفعش تاريخ لسه ماجاش.</li>
                                <li>تقدر تحذف سجلاتك انت بس.</li>
                            </ul>
                        </div>
                    </div>
                </div>
                <p class="small mb-0">
                    في المهمة بتشوف <strong>{{ __('task.time.estimated') }}</strong> مقابل <strong>{{ __('task.time.spent') }}</strong> والمتبقي أو الزيادة.
                    وشاشة <strong>«{{ __('portal.nav.time') }}»</strong> بتجمع وقتك أسبوع بأسبوع — حسب اليوم وحسب المهمة.
                    العدّاد والوقت للمهام المفتوحة بس، وأول ما المهمة تتقفل العدّادات اللي عليها بتقف.
                </p>
            </div>
        </div>

        {{-- اللوحة والتقويم --}}
        <div class="card border-0 shadow-sm mb-4 guide-card" id="g-views">
            <div class="card-header border-bottom">
                <h5 class="card-title mb-0 fw-bold text-primary"><i class="fa-solid fa-table-columns me-2"></i> اللوحة والتقويم</h5>
            </div>
            <div class="card-body pt-4">
                <p class="small mb-2">من «{{ __('portal.nav.tasks') }}» تقدر تغيّر طريقة العرض: <strong>{{ __('task.views.list') }}</strong> · <strong>{{ __('task.views.board') }}</strong> · <strong>{{ __('task.views.calendar') }}</strong>.</p>
                <ul class="small mb-0">
                    <li><strong>اللوحة:</strong> عمود لكل حالة. {{ __('task.views.drag_hint') }} — بنفس قواعد الأزرار (مثلاً مش هتقدر تسحب مهمة محتاجة مراجعة لـ «مكتملة» بنفسك). المكتملة بتظهر لآخر 14 يوم بس، واللوحة بتتحدّث لوحدها مع أي تغيير.</li>
                    <li><strong>التقويم:</strong> المهام على أيام الشهر حسب <strong>موعد التسليم</strong> — مفيد تشوف الأسبوع الجاي فيه إيه.</li>
                </ul>
            </div>
        </div>

        {{-- المشاريع --}}
        <div class="card border-0 shadow-sm mb-4 guide-card" id="g-projects">
            <div class="card-header border-bottom">
                <h5 class="card-title mb-0 fw-bold text-primary"><i class="fa-solid fa-diagram-project me-2"></i> مشاريعي</h5>
            </div>
            <div class="card-body pt-4">
                <p class="small mb-3">
                    المشروع مجموعة مهام لهدف واحد. المشاريع بتتعمل من الإدارة، وانت بتظهر لك لو ليك دور فيها —
                    ونسبة المشروع بتتحسب تلقائي من مهامه.
                </p>
                <div class="table-responsive">
                    <table class="table table-bordered align-middle mb-0">
                        <thead><tr><th style="width:22%">دورك</th><th>تقدر تعمل إيه</th></tr></thead>
                        <tbody>
                            <tr><td><span class="badge bg-label-primary">{{ __('project.member_role_enums.manager') }}</span></td><td class="small">تعدّل بيانات المشروع وحالته وأعضاءه من البوابة، تضيف مهام، وتعتمد وتعدّل وتكلّف في <strong>كل</strong> مهام المشروع.</td></tr>
                            <tr><td><span class="badge bg-label-info">{{ __('project.member_role_enums.member') }}</span></td><td class="small">تشوف المشروع وكل مهامه وتضيف مهام جديدة فيه.</td></tr>
                            <tr><td><span class="badge bg-label-secondary">{{ __('project.member_role_enums.viewer') }}</span></td><td class="small">تشوف بس.</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- المدير --}}
        <div class="card border-0 shadow-sm mb-4 guide-card" id="g-manager">
            <div class="card-header border-bottom">
                <h5 class="card-title mb-0 fw-bold text-primary"><i class="fa-solid fa-user-tie me-2"></i> لو انت مدير مباشر</h5>
            </div>
            <div class="card-body pt-4">
                <p class="small mb-2">لو فيه موظفين تحت إدارتك في النظام، بتظهر لك حاجات زيادة:</p>
                <ul class="small mb-0">
                    <li>تبويب <strong>«{{ __('task.tabs.team') }}»</strong>: كل المهام المكلّف بيها فريقك.</li>
                    <li>تقدر <strong>تعتمد أو ترجّع</strong> أي مهمة مكلّف بيها حد من فريقك، وتغيّر حالتها.</li>
                    <li>المهام اللي فريقك يعملها لنفسه بتيجي لك للمراجعة — هتلاقيها في <strong>«{{ __('task.tabs.review') }}»</strong>.</li>
                    <li>في <strong>«{{ __('portal.nav.performance') }}»</strong> قسم «{{ __('task.reports.team_title') }}»: أرقام كل موظف وأكتر المهام تأخيراً.</li>
                </ul>
            </div>
        </div>

        {{-- الإشعارات --}}
        <div class="card border-0 shadow-sm mb-4 guide-card" id="g-notify">
            <div class="card-header border-bottom">
                <h5 class="card-title mb-0 fw-bold text-primary"><i class="fa-solid fa-bell me-2"></i> الإشعارات والتذكيرات</h5>
            </div>
            <div class="card-body pt-4">
                <div class="table-responsive mb-3">
                    <table class="table table-bordered align-middle mb-0 small">
                        <thead><tr><th style="width:40%">الإشعار</th><th>بيوصل لمين</th></tr></thead>
                        <tbody>
                            <tr><td>اتكلّفت بمهمة</td><td>المكلّف الجديد</td></tr>
                            <tr><td>مهمة اتسلّمت للمراجعة</td><td>المراجع</td></tr>
                            <tr><td>اتعتمدت / رجعت للتنفيذ / اتلغت / اتفتحت تاني</td><td>المكلّفين (وصاحب المهمة لو مش هو اللي عمل كده)</td></tr>
                            <tr><td>حد اعتذر</td><td>صاحب المهمة</td></tr>
                            <tr><td>تعليق جديد · إشارة (&#64;)</td><td>المكلّفين وصاحب المهمة · المشار إليه</td></tr>
                            <tr><td>مهمة كنت مستنيها خلصت</td><td>مكلّفين المهمة اللي كانت مستنية</td></tr>
                            <tr><td><span class="badge bg-label-warning">تذكير</span> موعد التسليم قرّب</td><td>المكلّفين — قبل الموعد بـ {{ $dueSoon }} ساعة، مرة واحدة</td></tr>
                            <tr><td><span class="badge bg-label-danger">تنبيه</span> المهمة عدّت موعدها</td><td>المكلّفين وصاحب المهمة — مرة واحدة</td></tr>
                            <tr><td><span class="badge bg-label-info">تذكير</span> وقت تحديث النسبة</td><td>المكلّفين — حسب إعداد المهمة</td></tr>
                        </tbody>
                    </table>
                </div>
                <p class="small text-body-secondary mb-0">
                    مش هيوصلك إشعار بحاجة انت اللي عملتها. اضغط على الإشعار يفتح المهمة ويتعلّم مقروء،
                    أو «تعليم الكل كمقروء» من صفحة الإشعارات.
                    لو الموعد اتغيّر، التذكير بيتبعت تاني للموعد الجديد.
                </p>
            </div>
        </div>

        {{-- أسئلة --}}
        <div class="card border-0 shadow-sm mb-4 guide-card" id="g-faq">
            <div class="card-header border-bottom">
                <h5 class="card-title mb-0 fw-bold text-primary"><i class="fa-solid fa-circle-question me-2"></i> أسئلة ورسائل شائعة</h5>
            </div>
            <div class="card-body pt-4">
                <div class="table-responsive">
                    <table class="table table-bordered align-middle mb-0 small">
                        <thead><tr><th style="width:42%">الرسالة / السؤال</th><th>معناها وتعمل إيه</th></tr></thead>
                        <tbody>
                            <tr><td>«{{ __('task.errors.needs_review') }}»</td><td>المهمة عليها مراجعة. اضغط «{{ __('task.actions.send_to_review') }}» بدل «{{ __('task.actions.complete') }}».</td></tr>
                            <tr><td>«{{ __('task.errors.blocked') }}»</td><td>شوف قسم «{{ __('task.dependencies.title') }}» في المهمة — استنى المهمة دي تخلص (هيوصلك إشعار).</td></tr>
                            <tr><td>«{{ __('task.errors.progress_automatic') }}»</td><td>علّم على الخطوات أو خلّص المهام الفرعية، والنسبة هتتحدّث لوحدها.</td></tr>
                            <tr><td>«{{ __('task.errors.closed') }}»</td><td>المهمة مكتملة أو ملغية. لو محتاج تكمّل فيها اطلب من صاحبها يعيد فتحها.</td></tr>
                            <tr><td>«{{ __('task.errors.no_reviewer') }}»</td><td>عملت المهمة لنفسك ومالكش مدير مباشر — اقفلها بـ «{{ __('task.actions.complete') }}».</td></tr>
                            <tr><td>«{{ __('task.errors.invalid_assignee') }}»</td><td>اختار زميل من فرعك، أو ضيفه عضو في المشروع الأول (عن طريق مدير المشروع).</td></tr>
                            <tr><td>«{{ __('task.errors.forbidden') }}»</td><td>العملية مش من دورك في المهمة — اطلبها من صاحب المهمة أو مدير المشروع.</td></tr>
                            <tr><td>مش لاقي مهمة اتقالي عليها</td><td>جرّب تبويب تاني، وغيّر فلتر الحالة لـ «كل الحالات» — الافتراضي بيعرض المفتوحة بس.</td></tr>
                            <tr><td>نسيت العدّاد شغّال</td><td>وقّفه من الشريط العلوي. الوقت متحسب لحد {{ $timerHours }} ساعة بس، واحذف السجل وسجّل يدوي لو الرقم مش مظبوط.</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection

@push('js')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var links = Array.prototype.slice.call(document.querySelectorAll('#guideToc a[href^="#"]'));
    var items = links.map(function (link) {
        return { link: link, section: document.querySelector(link.getAttribute('href')) };
    }).filter(function (item) { return item.section; });

    if (!items.length) return;

    function activate(link) {
        links.forEach(function (a) { a.classList.remove('active'); });
        link.classList.add('active');
    }

    function onScroll() {
        var found = items[0].link;
        for (var i = 0; i < items.length; i++) {
            if (items[i].section.getBoundingClientRect().top - 110 <= 0) found = items[i].link; else break;
        }
        if (window.innerHeight + window.scrollY >= document.body.scrollHeight - 40) found = items[items.length - 1].link;
        activate(found);
    }

    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
});
</script>
@endpush
