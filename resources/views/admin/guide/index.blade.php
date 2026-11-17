@extends('layouts.admin.app')
@section('title', 'دليل الاستخدام والتشغيل التفصيلي للمنظومة المالية والإدارية')

@section('content')

<style>
    /* محاذاة عناصر المحتويات: الأيقونة عمود ثابت في بداية السطر
       بدل ما تبقى inline وتتزحزح مع طول النص. */
    #guideToc .nav-link {
        display: flex;
        align-items: flex-start;
        gap: .5rem;
        text-align: start;
        line-height: 1.6;
    }

    /* عرض ثابت لكل الأيقونات مهما اختلف شكلها */
    #guideToc .nav-link > i {
        flex: 0 0 1.25rem;
        width: 1.25rem;
        text-align: center;
        margin: 0;
        margin-top: .15rem;
    }

    /* النص بياخد باقي السطر ويلتف تحت نفسه */
    #guideToc .nav-link > span.toc-text {
        flex: 1 1 auto;
        min-width: 0;
    }

    /* شارة "جديد" ماتتمططش */
    #guideToc .nav-link .badge {
        flex: 0 0 auto;
        align-self: center;
    }
</style>

<div class="row">
    {{-- Header Banner --}}
    <div class="col-12 mb-4">
        <div class="card border-0 shadow-sm text-white" style="border-radius: 14px; background: linear-gradient(135deg, #1e1b4b 0%, #4338ca 60%, #6366f1 100%);">
            <div class="card-body p-4 p-md-5">
                <div class="row align-items-center">
                    <div class="col-md-9">
                        <div class="badge bg-white text-primary mb-3 px-3 py-2 fw-bold fs-6 shadow-sm">
                            <i class="fa-solid fa-graduation-cap me-2"></i> الدليل المرجعي الإداري والمحاسبي المعتمد
                        </div>
                        <h2 class="text-white fw-bolder mb-2">الدليل التشغيلي المتكامل لكافة موديولات المنظومة</h2>
                        <p class="text-white text-opacity-90 fs-6 mb-0 leading-relaxed">
                            مرجع تفصيلي يشرح بدقة المعادلات الحسابية، آليات التأثير على الرواتب، حركة الأرصدة، دورة حياة المبيعات والفواتير، الاشتراكات الخارجية، وإدارة العهد والمصروفات، متطابقاً بنسبة 100% مع الكود البرمجي وقواعد البيانات الفعلية.
                        </p>
                    </div>
                    <div class="col-md-3 text-center d-none d-md-block">
                        <i class="fa-solid fa-file-invoice-dollar text-white text-opacity-30" style="font-size: 8rem;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Side Navigation Links --}}
    <div class="col-lg-3 mb-4">
        <div class="card border-0 shadow-sm sticky-top" style="top: 85px; border-radius: 12px;">
            <div class="card-header border-bottom py-3">
                <h6 class="card-title mb-0 fw-bold"><i class="fa-solid fa-list-ol me-2 text-primary"></i> محتويات الدليل</h6>
            </div>
            <div class="card-body p-2" style="max-height: calc(100vh - 190px); overflow-y: auto;">
                <nav class="nav nav-pills flex-column gap-1" id="guideToc">
                    <a class="nav-link py-2 px-3" href="#sec-dashboard"><i class="fa-solid fa-gauge-high"></i><span class="toc-text">لوحة التحكم والمؤشرات</span></a>
                    <a class="nav-link py-2 px-3" href="#sec-employees"><i class="fa-solid fa-users"></i><span class="toc-text">الموظفون والفروع والمسميات</span><span class="badge bg-label-success ms-1">جديد</span></a>
                    <a class="nav-link py-2 px-3" href="#sec-portal"><i class="fa-solid fa-door-open"></i><span class="toc-text">بوابة الموظف</span><span class="badge bg-label-success ms-1">جديد</span></a>
                    <a class="nav-link py-2 px-3" href="#sec-formula"><i class="fa-solid fa-calculator"></i><span class="toc-text">1. معادلة صافي الراتب والشرح بالعربي</span><span class="badge bg-label-info ms-1">محدَّث</span></a>
                    <a class="nav-link py-2 px-3" href="#sec-example"><i class="fa-solid fa-receipt"></i><span class="toc-text">2. مثال عملي رقمي خطوة بخطوة</span></a>
                    <a class="nav-link py-2 px-3" href="#sec-permissions"><i class="fa-solid fa-clock"></i><span class="toc-text">3. الأذونات والاستئذان وحساب الساعات</span><span class="badge bg-label-info ms-1">محدَّث</span></a>
                    <a class="nav-link py-2 px-3" href="#sec-leaves"><i class="fa-solid fa-calendar-check"></i><span class="toc-text">4. الإجازات، الأرصدة، وسلة المحذوفات</span><span class="badge bg-label-info ms-1">محدَّث</span></a>
                    <a class="nav-link py-2 px-3" href="#sec-advances"><i class="fa-solid fa-hand-holding-dollar"></i><span class="toc-text">5. السلف والقروض والإسقاط الإداري</span><span class="badge bg-label-info ms-1">محدَّث</span></a>
                    <a class="nav-link py-2 px-3" href="#sec-rewards-allowances"><i class="fa-solid fa-award"></i><span class="toc-text">6. البدلات الثابتة vs المكافآت الاستثنائية</span></a>
                    <a class="nav-link py-2 px-3" href="#sec-approval-cycle"><i class="fa-solid fa-user-check"></i><span class="toc-text">دورة اعتماد الجزاءات والمكافآت</span><span class="badge bg-label-info ms-1">محدَّث</span></a>
                    <a class="nav-link py-2 px-3" href="#sec-penalties"><i class="fa-solid fa-gavel"></i><span class="toc-text">7. الجزاءات وحساب سعر يوم العمل</span></a>
                    <a class="nav-link py-2 px-3" href="#sec-payroll-lifecycle"><i class="fa-solid fa-money-check-dollar"></i><span class="toc-text">8. مسير الرواتب ودورة الاعتماد والصرف</span><span class="badge bg-label-info ms-1">محدَّث</span></a>
                    <a class="nav-link py-2 px-3" href="#sec-sales-channels-customers"><i class="fa-solid fa-cart-shopping"></i><span class="toc-text">9. المبيعات، الفواتير، وقنوات البيع</span><span class="badge bg-label-info ms-1">محدَّث</span></a>
                    <a class="nav-link py-2 px-3" href="#sec-customer-sources"><i class="fa-solid fa-share-nodes"></i><span class="toc-text">مصادر العملاء</span></a>
                    <a class="nav-link py-2 px-3" href="#sec-vendors-subscriptions"><i class="fa-solid fa-repeat"></i><span class="toc-text">10. الموردين والاشتراكات الدورية</span></a>
                    <a class="nav-link py-2 px-3" href="#sec-expenses-petty-cash"><i class="fa-solid fa-wallet"></i><span class="toc-text">11. العهد النقدية وتعدد العملات</span><span class="badge bg-label-info ms-1">محدَّث</span></a>
                    <a class="nav-link py-2 px-3" href="#sec-short-links"><i class="fa-solid fa-link"></i><span class="toc-text">الروابط المختصرة والإحصائيات</span></a>
                    <a class="nav-link py-2 px-3" href="#sec-reports"><i class="fa-solid fa-chart-pie"></i><span class="toc-text">التقارير</span><span class="badge bg-label-success ms-1">جديد</span></a>

                    <hr class="my-2">
                    <div class="px-3 pb-1"><small class="text-muted fw-bold">الحوكمة والأمان</small></div>

                    <a class="nav-link py-2 px-3" href="#sec-roles"><i class="fa-solid fa-user-shield"></i><span class="toc-text">الأدوار والصلاحيات حسب التخصص</span><span class="badge bg-label-success ms-1">جديد</span></a>
                    <a class="nav-link py-2 px-3" href="#sec-table-filters"><i class="fa-solid fa-filter"></i><span class="toc-text">البحث والفلترة في الجداول</span></a>
                    <a class="nav-link py-2 px-3" href="#sec-activity-log"><i class="fa-solid fa-shield-halved"></i><span class="toc-text">12. سجل حركات النظام (Audit Log)</span></a>
                    <a class="nav-link py-2 px-3" href="#sec-branch-isolation"><i class="fa-solid fa-building-lock"></i><span class="toc-text">13. عزل البيانات بين الفروع</span><span class="badge bg-label-info ms-1">محدَّث</span></a>
                    <a class="nav-link py-2 px-3" href="#sec-auth-security"><i class="fa-solid fa-key"></i><span class="toc-text">14. الدخول واستعادة كلمة المرور</span><span class="badge bg-label-info ms-1">محدَّث</span></a>
                    <a class="nav-link py-2 px-3" href="#sec-notifications"><i class="fa-solid fa-bell"></i><span class="toc-text">15. الإشعارات والتنبيهات اللحظية</span><span class="badge bg-label-info ms-1">محدَّث</span></a>
                    <a class="nav-link py-2 px-3" href="#sec-maintenance"><i class="fa-solid fa-terminal"></i><span class="toc-text">16. أوامر الصيانة والتشغيل الدوري</span><span class="badge bg-label-info ms-1">محدَّث</span></a>
                </nav>
            </div>
        </div>
    </div>

    {{-- Main Detailed Content --}}
    <div class="col-lg-9">

        {{-- 1. معادلة احتساب صافي الراتب المستحق بالعربي --}}
        {{-- لوحة التحكم --}}
        <div class="card border-0 shadow-sm mb-4" id="sec-dashboard">
            <div class="card-header border-bottom d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0 fw-bold text-primary">
                    <i class="fa-solid fa-gauge-high me-2"></i> لوحة التحكم والمؤشرات
                </h5>
                <span class="badge bg-label-success font-monospace">جديد</span>
            </div>
            <div class="card-body pt-4">

                <p class="text-muted mb-4">
                    الصفحة الرئيسية <code dir="ltr">/admin/home</code> بتجمع كل ما تحتاج معرفته
                    في شاشة واحدة، مرتّبة من <strong>الأهم للأقل أهمية</strong>:
                    ما يحتاج قراراً منك أولاً، ثم الأرقام، ثم التحليلات.
                </p>

                <div class="alert alert-primary d-flex gap-3 mb-4">
                    <i class="fa-solid fa-filter fs-4"></i>
                    <div>
                        <h6 class="alert-heading fw-bold mb-1">شريط الفلاتر أعلى اللوحة</h6>
                        <p class="small mb-2">
                            كل الأرقام والرسوم تحت الشريط بتتحسب على <strong>الفترة والفرع</strong>
                            اللي تختارهم. التحديث بيتم <strong>من غير إعادة تحميل الصفحة</strong>.
                        </p>
                        <ul class="small mb-0 ps-3">
                            <li><strong>الفترة:</strong> الشهر الحالي (الافتراضي) · الشهر الماضي · الربع · السنة · آخر 12 شهر · فترة مخصصة</li>
                            <li><strong>الفرع:</strong> كل الفروع، أو فرع واحد بعينه</li>
                        </ul>
                        <p class="small mb-0 mt-2">
                            <strong>مفيش فلتر عملة</strong> — الرسوم بتعرض كل العملات مع بعض،
                            كل عملة سلسلة مستقلة بلونها.
                        </p>
                    </div>
                </div>

                <div class="table-responsive mb-4">
                    <table class="table table-bordered align-middle">
                        <thead>
                            <tr><th style="width:30%">القسم</th><th>ماذا يعرض</th></tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="fw-bold text-warning">محتاج إجراء منك</td>
                                <td class="small">
                                    شريط علوي بالطلبات المعلّقة (إجازات، سلف، جزاءات، مكافآت، مسيرات مسودة)
                                    والتنبيهات (فواتير متأخرة، اشتراكات تقترب). كل زر ينقلك للشاشة مفلترة.
                                    <strong>لا يظهر الشريط إن لم يكن هناك ما يحتاج إجراءً.</strong>
                                </td>
                            </tr>
                            <tr>
                                <td class="fw-bold text-primary">الملخص المالي</td>
                                <td class="small">
                                    الإيرادات والمصروفات والصافي والمستحق للفترة المختارة —
                                    <strong>صف مستقل لكل عملة</strong>، فلا يُجمع الجنيه مع الريال.
                                    كل مؤشر عليه <strong>علامة استفهام</strong> بتشرح معناه بالظبط.
                                </td>
                            </tr>
                            <tr>
                                <td class="fw-bold text-info">الرسوم البيانية</td>
                                <td class="small">
                                    <strong>اتجاه الإيرادات والمصروفات:</strong> طوله بيتبع الفترة المختارة.
                                    كل عملة ليها خطين بنفس اللون — الإيراد متصل والمصروف متقطّع.<br>
                                    <strong>توزيع المصروفات</strong> و<strong>المبيعات حسب القناة:</strong>
                                    أعمدة أفقية، <strong>الاسم والقيمة مكتوبين جوه العمود</strong> نفسه
                                    عشان الأسماء الطويلة ما تتقصّش، وكل عملة عمود بلون مستقل.<br>
                                    <strong>الموظفون حسب الفرع:</strong> عمود مقسوم للنشطين ومن هم في إجازة.
                                </td>
                            </tr>
                            <tr>
                                <td class="fw-bold text-warning">أعلى العملاء</td>
                                <td class="small">أكثر 5 عملاء تعاملاً هذا الشهر بقيمة تعاملاتهم وعدد فواتيرهم.</td>
                            </tr>
                            <tr>
                                <td class="fw-bold text-success">الموارد البشرية</td>
                                <td class="small">الموظفون النشطون · في إجازة · تعيينات الشهر · الإجمالي.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="alert alert-info mb-0 h-100">
                            <h6 class="alert-heading fw-bold"><i class="fa-solid fa-coins me-1"></i> فصل العملات</h6>
                            <p class="small mb-0">
                                كل الأرقام المالية مفصولة بالعملة. الرسوم بتعرض
                                <strong>كل العملات في نفس الوقت</strong>، كل واحدة سلسلة
                                مستقلة — لأن جمع عملات مختلفة في رقم واحد نتيجة مضلّلة.
                            </p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="alert alert-success mb-0 h-100">
                            <h6 class="alert-heading fw-bold"><i class="fa-solid fa-building-lock me-1"></i> يحترم عزل الفروع</h6>
                            <p class="small mb-0">
                                مدير الفرع يرى أرقام فرعه فقط في كل بطاقة ورسم.
                                المدير العام يرى الصورة الكاملة.
                            </p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="alert alert-warning mb-0 h-100">
                            <h6 class="alert-heading fw-bold"><i class="fa-solid fa-bolt me-1"></i> سريعة مع نمو البيانات</h6>
                            <p class="small mb-0">
                                كل الأرقام تُجمَّع في استعلامات <span dir="ltr">SQL</span> مجمّعة،
                                فعدد الاستعلامات ثابت مهما زادت السجلات.
                            </p>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        {{-- الموظفون والفروع والمسميات --}}
        <div class="card border-0 shadow-sm mb-4" id="sec-employees">
            <div class="card-header border-bottom d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0 fw-bold text-primary">
                    <i class="fa-solid fa-users me-2"></i> الموظفون والفروع والمسميات الوظيفية
                </h5>
                <span class="badge bg-label-success font-monospace">جديد</span>
            </div>
            <div class="card-body pt-4">

                <p class="text-muted mb-4">
                    ملف الموظف هو أساس كل وحدات الموارد البشرية والرواتب: منه يُحسب سعر اليوم والساعة،
                    وعليه تُسجَّل الإجازات والأذونات والجزاءات والمكافآت والسلف، ومنه يُبنى المسير.
                </p>

                <div class="table-responsive mb-4">
                    <table class="table table-bordered align-middle">
                        <thead>
                            <tr><th style="width:28%">الجزء</th><th>ماذا يحتوي وكيف يعمل</th></tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="fw-bold">البيانات الوظيفية</td>
                                <td class="small">
                                    الفرع، المسمى الوظيفي، المدير المباشر، تاريخ التعيين ونهاية الاختبار، نوع العقد، والحالة
                                    (<span class="badge bg-label-success">نشط</span>
                                    <span class="badge bg-label-warning">في إجازة</span>
                                    <span class="badge bg-label-danger">ترك العمل</span>).
                                    <strong>المسير يشمل الموظفين النشطين فقط.</strong>
                                </td>
                            </tr>
                            <tr>
                                <td class="fw-bold">الكود الوظيفي</td>
                                <td class="small">
                                    يُولَّد تلقائياً لو تُرك فارغاً بصيغة <span dir="ltr">رمز الفرع-0001</span>،
                                    والرقم التالي = <strong>أكبر رقم مستخدم + 1</strong> — فلا يتكرر الكود حتى لو
                                    انتقل موظف لفرع آخر أو حُذف.
                                </td>
                            </tr>
                            <tr>
                                <td class="fw-bold">الراتب والأسعار الخاصة</td>
                                <td class="small">
                                    الراتب الأساسي، ساعات العمل اليومية (الافتراضي 8)، حد الاستئذان الشهري (الافتراضي 4 ساعات)،
                                    ورصيد الإجازة السنوية. ويمكن تحديد <strong>سعر يوم خاص</strong> أو <strong>سعر ساعة خاص</strong>
                                    للموظف بدل الحساب من الراتب — <a href="#sec-penalties">راجع المعادلات</a>.
                                </td>
                            </tr>
                            <tr>
                                <td class="fw-bold">سجل الرواتب</td>
                                <td class="small">
                                    أي تغيير في الراتب يُسجَّل كقيد بتاريخ سريان، فيبقى تاريخ الزيادات محفوظاً،
                                    والراتب الأساسي يتحدّث لأحدث قيد بدأ سريانه.
                                </td>
                            </tr>
                            <tr>
                                <td class="fw-bold">البدلات الثابتة</td>
                                <td class="small">سكن، انتقال، اتصالات... تُضاف شهرياً للمسير تلقائياً — <a href="#sec-rewards-allowances">الفرق بينها وبين المكافآت</a>.</td>
                            </tr>
                            <tr>
                                <td class="fw-bold">المستندات</td>
                                <td class="small">
                                    الهوية والعقود والشهادات مع تاريخ الانتهاء. المستند الذي ينتهي خلال
                                    <strong>30 يوماً</strong> يظهر كـ «قارب على الانتهاء» في
                                    <a href="#sec-reports">تقرير متابعة المستندات</a>، ويمكن توثيق المستند (تم التحقق).
                                </td>
                            </tr>
                            <tr>
                                <td class="fw-bold">المهارات</td>
                                <td class="small">مهارات الموظف ومستواها، للعرض في ملفه.</td>
                            </tr>
                            <tr>
                                <td class="fw-bold">دخول البوابة</td>
                                <td class="small">
                                    في شاشة الإضافة والتعديل جزء <strong>"دخول بوابة الموظف"</strong>: كتابة كلمة مرور تفعّل دخوله
                                    <a href="#sec-portal">للبوابة</a> برقم هاتفه (أو تعيد تعيينها لو مفعّل). لا يوجد فيها حساب للوحة التحكم —
                                    حسابات الإدارة منفصلة تماماً وتُنشأ من شاشة <strong>المشرفين</strong>، ويمكن هناك ربط المشرف بملف موظف
                                    ليرى بيانات <a href="#sec-branch-isolation">فرعه فقط</a>.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="alert alert-warning mb-0 h-100">
                            <h6 class="alert-heading fw-bold"><i class="fa-solid fa-eye-slash me-1"></i> بيانات حساسة بصلاحية منفصلة</h6>
                            <p class="small mb-0">
                                الراتب يظهر فقط لمن لديه <code dir="ltr">employee_salary_view</code>،
                                والبيانات البنكية لمن لديه <code dir="ltr">employee_bank_view</code> —
                                فأخصائي الموارد البشرية يُدخل بيانات الموظفين دون أن يرى رواتبهم.
                            </p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="alert alert-info mb-0 h-100">
                            <h6 class="alert-heading fw-bold"><i class="fa-solid fa-building me-1"></i> الفروع والمسميات</h6>
                            <p class="small mb-0">
                                لكل فرع <strong>عملته</strong> (تُستخدم في رواتبه ومصروفاته وفواتيره) ورمزه
                                (يُولَّد تلقائياً من الدولة: <span dir="ltr">EG-01</span>، <span dir="ltr">SA-01</span>...).
                                الفرع أو المسمى المرتبط بموظفين لا يُحذف.
                            </p>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        {{-- بوابة الموظف --}}
        <div class="card border-0 shadow-sm mb-4" id="sec-portal">
            <div class="card-header border-bottom d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0 fw-bold text-primary">
                    <i class="fa-solid fa-door-open me-2"></i> بوابة الموظف (الخدمة الذاتية)
                </h5>
                <span class="badge bg-label-success font-monospace">جديد</span>
            </div>
            <div class="card-body pt-4">

                <p class="text-muted mb-4">
                    بوابة منفصلة على <a href="{{ route('portal.login') }}" target="_blank" dir="ltr">{{ route('portal.login') }}</a>
                    يدخلها كل موظف برقم <strong>هاتفه الأساسي</strong> وكلمة المرور، ليقدّم طلباته بنفسه ويتابعها.
                    منفصلة تماماً عن لوحة التحكم: جلسة الموظف لا تصل لأي شاشة إدارية، ولا يرى إلا بياناته هو،
                    والخروج من إحدى اللوحتين لا يُخرج من الأخرى لو كانتا مفتوحتين في نفس المتصفح.
                </p>
                <p class="text-muted mb-4">
                    الصفحة الرئيسية للنظام <a href="{{ route('home') }}" target="_blank" dir="ltr">{{ route('home') }}</a>
                    تعرض اختيار اللوحة: <strong>بوابة الموظف</strong> أو <strong>لوحة الإدارة</strong> — أرسل هذا الرابط للجميع.
                </p>

                <div class="table-responsive mb-4">
                    <table class="table table-bordered align-middle">
                        <thead>
                            <tr><th style="width:26%">الشاشة</th><th>ماذا يفعل فيها الموظف</th></tr>
                        </thead>
                        <tbody>
                            <tr><td class="fw-bold">الرئيسية</td><td class="small">أرصدة إجازاته المتبقية، ساعات الاستئذان هذا الشهر مقابل حده، طلباته المعلّقة، المتبقي من سلفه، وآخر قسائم راتبه.</td></tr>
                            <tr><td class="fw-bold">استئذاناتي</td><td class="small">طلب استئذان (التاريخ ووقت الخروج والعودة — الساعات تُحسب تلقائياً) ومتابعة حالته وسبب الرفض.</td></tr>
                            <tr><td class="fw-bold">إجازاتي</td><td class="small">طلب إجازة بنفس قواعد لوحة التحكم (لا تداخل، أيام منطقية) مع عرض رصيده لكل نوع.</td></tr>
                            <tr><td class="fw-bold">سلفي</td><td class="small">طلب سلفة بالمبلغ وعدد الأقساط وبداية الخصم، ومتابعة القسط والمتبقي.</td></tr>
                            <tr><td class="fw-bold">قسائم الراتب</td><td class="small">القسائم <strong>المصروفة فقط</strong> للعرض والطباعة — البند قبل الصرف قد يتغيّر.</td></tr>
                            <tr><td class="fw-bold">بياناتي</td><td class="small">بيانات العمل ومستنداته وتواريخ انتهائها — للعرض فقط؛ التعديل عن طريق الموارد البشرية.</td></tr>
                        </tbody>
                    </table>
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="alert alert-primary mb-0 h-100">
                            <h6 class="alert-heading fw-bold"><i class="fa-solid fa-user-gear me-1"></i> التفعيل والإيقاف (الموارد البشرية)</h6>
                            <ul class="small mb-0 ps-3">
                                <li>الدخول <strong>مقفول افتراضياً</strong>. يُفعَّل لكل موظف بكلمة مرور أولية تُسلَّم له: من ملفه، أو من شاشة إضافته أو تعديله.</li>
                                <li>الموظف <strong>مُلزَم بتغييرها</strong> عند أول دخول، وكذلك بعد أي إعادة تعيين.</li>
                                <li>الإيقاف يتطلب <strong>سبباً يظهر للموظف</strong> عند محاولة الدخول، ويُطلَّع فوراً لو كان داخل البوابة.</li>
                                <li>الموظف المنتهية خدمته لا يدخل أبداً.</li>
                                <li>الصلاحية: <code dir="ltr">employee_portal_manage</code> (مدير الموارد البشرية والمدير العام).</li>
                            </ul>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="alert alert-info mb-0 h-100">
                            <h6 class="alert-heading fw-bold"><i class="fa-solid fa-route me-1"></i> مسار الطلب</h6>
                            <ul class="small mb-0 ps-3">
                                <li>الطلب يصل <strong>معلّقاً</strong> للوحة التحكم مع <a href="#sec-notifications">إشعار</a> لمن لديه صلاحية مراجعته في فرع الموظف.</li>
                                <li>الاعتماد والرفض يتمّان من لوحة التحكم بنفس <a href="#sec-roles">الأدوار</a> الحالية، والنتيجة تظهر للموظف في البوابة مع سبب الرفض.</li>
                                <li>يستطيع الموظف <strong>إلغاء طلبه</strong> ما دام معلّقاً.</li>
                                <li>رقم الهاتف <strong>فريد</strong> لكل موظف لأنه اسم الدخول، ونسيان كلمة المرور يُحَل بإعادة تعيينها من الموارد البشرية.</li>
                            </ul>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <div class="card border-0 shadow-sm mb-4" id="sec-formula">
            <div class="card-header border-bottom d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0 fw-bold text-primary">
                    <i class="fa-solid fa-calculator me-2"></i> 1. معادلة احتساب صافي الراتب المستحق (Net Salary) - الشرح التفصيلي بالعربي
                </h5>
                <span class="badge bg-label-primary font-monospace">Core Accounting Logic</span>
            </div>
            <div class="card-body pt-4">
                <div class="p-3 border rounded mb-4 text-center" style="background: rgba(115, 103, 240, 0.06); border-radius: 12px;">
                    <div class="fs-6 fw-bold text-primary mb-2">المعادلة الرياضية المعتمدة في النظام:</div>
                    <div class="fs-5 fw-bold text-dark font-monospace mb-2" dir="ltr">
                        Net Salary = [ Basic Salary + Total Allowances + Approved Rewards ] - [ Approved Penalties + Advances Installments + Unpaid Leaves Deductions + Excess Permission Deductions ]
                    </div>
                    <div class="fs-6 fw-bold text-success font-monospace" dir="rtl">
                        صافي الراتب المستحق = [ الراتب الأساسي + إجمالي البدلات التعاقدية + المكافآت المعتمدة للشهر ] - [ جزاءات الشهر المعتمدة + قسط السلفة الشهري + خصم الإجازات بدون راتب + خصم ساعات الاستئذان المتجاوزة ]
                    </div>
                </div>

                <h6 class="fw-bold mb-3 text-dark"><i class="fa-solid fa-circle-nodes me-2 text-primary"></i> شرح كل جزء من أجزاء المعادلة باللغة العربية:</h6>

                <div class="row g-3 mb-4">
                    {{-- كفة الاستحقاقات --}}
                    <div class="col-md-6">
                        <div class="card border border-success h-100 shadow-none">
                            <div class="card-header bg-label-success py-2">
                                <h6 class="card-title mb-0 text-success fw-bold"><i class="fa-solid fa-plus-circle me-1"></i> أولاً: كفة الاستحقاقات والإضافات (+)</h6>
                            </div>
                            <div class="card-body p-3">
                                <div class="mb-3 border-bottom pb-2">
                                    <strong class="text-dark d-block">1. الراتب الأساسي (Basic Salary):</strong>
                                    <small class="text-muted">هو الأجر الأساسي المتفق عليه في عقد العمل والمسجل في شاشة الموظف. وهو الأساس لحساب سعر يوم وساعة العمل.</small>
                                </div>
                                <div class="mb-3 border-bottom pb-2">
                                    <strong class="text-dark d-block">2. إجمالي البدلات التعاقدية (Total Allowances):</strong>
                                    <small class="text-muted">مجموع البدلات الثابتة الشهرية (سكن، مواصلات، اتصالات، غذاء، طبيعة عمل) المسجلة في جدول بدلات الموظف وتصرف له شهرياً.</small>
                                </div>
                                <div>
                                    <strong class="text-dark d-block">3. المكافآت والبدلات الاستثنائية (Approved Rewards):</strong>
                                    <small class="text-muted">المبالغ الاستثنائية التي تم اعتمادها للموظف خلال نفس شهر المسير (حافز إنجاز، مكافأة تميز، عمولة مبيعات، أجر إضافي Overtime).</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- كفة الاستقطاعات --}}
                    <div class="col-md-6">
                        <div class="card border border-danger h-100 shadow-none">
                            <div class="card-header bg-label-danger py-2">
                                <h6 class="card-title mb-0 text-danger fw-bold"><i class="fa-solid fa-minus-circle me-1"></i> ثانياً: كفة الاستقطاعات والخصومات (-)</h6>
                            </div>
                            <div class="card-body p-3">
                                <div class="mb-3 border-bottom pb-2">
                                    <strong class="text-dark d-block">1. الجزاءات والخصومات الإدارية (Approved Penalties):</strong>
                                    <small class="text-muted">تشمل: خصم الأيام (عدد الأيام &times; سعر يوم العمل) بالإضافة إلى أي خصومات نقدية مباشرة. <strong>المعتمد فقط</strong> يدخل المسير، و<strong>بالمبلغ المثبَّت وقت التسجيل</strong> — المعلّق والمرفوض لا يُخصمان.</small>
                                </div>
                                <div class="mb-3 border-bottom pb-2">
                                    <strong class="text-dark d-block">2. أقساط السلف والقروض (Advances Installments):</strong>
                                    <small class="text-muted">القسط الشهري المستحق سداده من السلفة المعتمدة، ولا يتجاوز الرصيد المتبقي الفعلي بذمة الموظف.</small>
                                </div>
                                <div class="mb-3 border-bottom pb-2">
                                    <strong class="text-dark d-block">3. خصم الإجازات بدون راتب (Unpaid Leaves):</strong>
                                    <small class="text-muted">أيام الإجازات غير المدفوعة المعتمدة الواقعة داخل الشهر &times; سعر يوم العمل. الإجازة التي تمتد بين شهرين <strong>تُوزَّع عليهما</strong> حسب أيام كل شهر — <a href="#sec-leaves">مثال</a>.</small>
                                </div>
                                <div>
                                    <strong class="text-dark d-block">4. خصم ساعات الاستئذان المتجاوزة (Excess Permission Hours):</strong>
                                    <small class="text-muted">الساعات التي تتعدى الحد الشهري المسموح للموظف &times; سعر ساعة العمل الفعلية.</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="alert alert-success border-0 small">
                    <i class="fa-solid fa-equals me-2"></i>
                    <strong>نفس الأرقام في كل مكان:</strong> المسير وتقرير كشف حساب الموظف وتقرير تدقيق الراتب
                    يحسبون بنفس القواعد بالظبط (المعتمد فقط، المبلغ المثبَّت، توزيع الإجازة على الشهور، نفس سعر الساعة) —
                    فالرقم الذي يظهر في التدقيق هو نفسه الذي سيُصرف.
                </div>

                <div class="alert alert-primary border-0 small mb-0">
                    <i class="fa-solid fa-shield-check me-2"></i>
                    <strong>قاعدة حماية الصافي:</strong> يضمن النظام ألا يصبح صافي الراتب قيمة سالبة؛ فإذا كانت الاستقطاعات أكبر من الاستحقاقات، يكون الناتج <span dir="ltr">0.00</span>. الفرق <strong>لا يُرحَّل تلقائياً</strong> للشهر التالي — يُعالَج إدارياً وفق لائحة العمل (مثلاً بتسجيل سلفة أو جزاء في الشهر التالي).
                </div>
            </div>
        </div>

        {{-- 2. مثال عملي رقمي خطوة بخطوة --}}
        <div class="card border-0 shadow-sm mb-4" id="sec-example">
            <div class="card-header border-bottom">
                <h5 class="card-title mb-0 fw-bold text-primary">
                    <i class="fa-solid fa-receipt me-2"></i> 2. سيناريو ومثال عملي رقمي خطوة بخطوة (Case Study)
                </h5>
            </div>
            <div class="card-body pt-4">
                <p class="text-muted leading-relaxed">
                    لنفترض موظفاً اسمه <strong>أحمد مصطفى</strong>، بياناته المالية لشهر سبتمبر 2026 كالتالي:
                </p>

                <div class="table-responsive mb-4">
                    <table class="table table-bordered table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>البند</th>
                                <th>القيمة المالية</th>
                                <th>طريقة الاحتساب</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>الراتب الأساسي (Basic Salary)</td>
                                <td class="fw-bold text-dark"><span dir="ltr">6,000.00</span></td>
                                <td>المثبت في العقد وملف الموظف</td>
                            </tr>
                            <tr>
                                <td>سعر يوم العمل (Daily Rate)</td>
                                <td class="fw-bold text-info"><span dir="ltr">200.00</span></td>
                                <td><span dir="ltr">6,000 / 30 = 200.00</span></td>
                            </tr>
                            <tr>
                                <td>سعر ساعة العمل (Hourly Rate)</td>
                                <td class="fw-bold text-info"><span dir="ltr">25.00</span></td>
                                <td><span dir="ltr">200 / 8 ساعات = 25.00</span></td>
                            </tr>
                            <tr>
                                <td>البدلات الثابتة التعاقدية (Allowances)</td>
                                <td class="fw-bold text-success"><span dir="ltr">+ 1,500.00</span></td>
                                <td>بدل سكن 1,000 + بدل انتقال 500</td>
                            </tr>
                            <tr>
                                <td>مكافأة استثنائية معتمدة (Approved Reward)</td>
                                <td class="fw-bold text-success"><span dir="ltr">+ 800.00</span></td>
                                <td>حافز إنجاز مشروع معتمد خلال الشهر</td>
                            </tr>
                            <tr class="table-success fw-bold">
                                <td><strong>إجمالي الاستحقاقات (Total Earnings)</strong></td>
                                <td colspan="2"><span dir="ltr">6,000 + 1,500 + 800 = 8,300.00</span></td>
                            </tr>
                            <tr>
                                <td>جزاء إداري (خصم يومين)</td>
                                <td class="fw-bold text-danger"><span dir="ltr">- 400.00</span></td>
                                <td><span dir="ltr">2 يوم &times; 200.00 = 400.00</span></td>
                            </tr>
                            <tr>
                                <td>إجازة بدون راتب (Unpaid Leave)</td>
                                <td class="fw-bold text-danger"><span dir="ltr">- 200.00</span></td>
                                <td><span dir="ltr">1 يوم &times; 200.00 = 200.00</span></td>
                            </tr>
                            <tr>
                                <td>ساعات استئذان زائدة</td>
                                <td class="fw-bold text-danger"><span dir="ltr">- 50.00</span></td>
                                <td>تجاوز الحد بساعتين: <span dir="ltr">2 ساعة &times; 25.00 = 50.00</span></td>
                            </tr>
                            <tr>
                                <td>قسط السلفة الشهرية (Advance Installment)</td>
                                <td class="fw-bold text-danger"><span dir="ltr">- 1,000.00</span></td>
                                <td>قسط الشهر المستحق من سلفة قائمة</td>
                            </tr>
                            <tr class="table-danger fw-bold">
                                <td><strong>إجمالي الاستقطاعات (Total Deductions)</strong></td>
                                <td colspan="2"><span dir="ltr">400 + 200 + 50 + 1,000 = 1,650.00</span></td>
                            </tr>
                            <tr class="table-primary fw-bolder fs-6">
                                <td><strong>صافي الراتب المحوّل في المسير (Net Salary)</strong></td>
                                <td colspan="2" class="text-primary"><span dir="ltr">8,300.00 - 1,650.00 = 6,650.00</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="p-3 border rounded" style="background-color: rgba(40, 199, 111, 0.08);">
                    <h6 class="text-success fw-bold mb-1"><i class="fa-solid fa-check-double me-1"></i> ماذا يحدث برمجياً ومحاسبياً عند الضغط على "صرف الراتب"؟</h6>
                    <ol class="small text-muted mb-0 ps-3">
                        <li>يتحول كشف راتب الموظف إلى <code>مدفوع (Paid)</code> ويتم تسجيل اسم المشرف وتاريخ الصرف.</li>
                        <li>يتم تسجيل حركة سداد تلقائية بقيمة <span dir="ltr">1,000.00</span> في سجل سداد السلفة، ويقل الرصيد المتبقي بمقدار <span dir="ltr">1,000.00</span>.</li>
                        <li>تتحول المكافأة الاستثنائية (<span dir="ltr">800.00</span>) في جدول المكافآت من <code>معتمد (Approved)</code> إلى <code>مدفوع (Paid)</code>.</li>
                        <li>تصبح قسيمة الراتب (Payslip) جاهزة للطباعة أو التصدير أو الإرسال للموظف عبر حسابه البنكي.</li>
                    </ol>
                </div>
            </div>
        </div>

        {{-- 3. الأذونات والاستئذان --}}
        <div class="card border-0 shadow-sm mb-4" id="sec-permissions">
            <div class="card-header border-bottom">
                <h5 class="card-title mb-0 fw-bold text-primary">
                    <i class="fa-solid fa-clock me-2"></i> 3. الأذونات والاستئذان (Employee Permissions)
                </h5>
            </div>
            <div class="card-body pt-4">
                <p class="text-muted leading-relaxed">
                    تتيح هذه الوحدة للموظفين تقديم طلبات خروج مؤقت أو تأخير بإذن مسبق دون احتسابه غياباً كاملاً:
                </p>
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <div class="p-3 border rounded h-100">
                            <h6 class="fw-bold mb-2 text-primary"><i class="fa-solid fa-stopwatch me-1"></i> دقة احتساب الساعات العشرية</h6>
                            <p class="small text-muted mb-0">
                                يدعم النظام الساعات بدقة (1.25 ساعة = ساعة و15 دقيقة، 0.5 = 30 دقيقة).
                                <strong>عدد الساعات يُحسب من وقت الخروج والعودة</strong> وليس مما يكتبه المستخدم —
                                فلا يمكن تسجيل إذن من 9 إلى 10 بعدد 8 ساعات.
                            </p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-3 border rounded h-100">
                            <h6 class="fw-bold mb-2 text-warning"><i class="fa-solid fa-gauge-high me-1"></i> الرصيد الشهري وعداد التجاوز</h6>
                            <p class="small text-muted mb-0">
                                لكل موظف حد استئذان شهري يُحدَّد في ملفه (<strong>الافتراضي 4 ساعات</strong>). عند اختيار موظف في الشاشة يظهر فوراً مجموع ساعاته المعتمدة في الشهر، والمتبقي، وما إذا كان قد تجاوز الحد.
                            </p>
                        </div>
                    </div>
                </div>
                <div class="alert alert-secondary border-0 small mb-0">
                    <strong><i class="fa-solid fa-info-circle me-1"></i> الأثر في المسير:</strong>
                    إذا بلغ مجموع ساعات الاستئذان المعتمدة 5 ساعات، والحد المسموح 4 ساعات: يتم خصم ساعة واحدة متجاوزة في مسير الرواتب بحساب <span dir="ltr">1 &times; Hourly Rate</span>.
                    <br>
                    <strong>دورة الطلب:</strong> يُسجَّل معلّقاً ← يُعتمد أو يُرفض. الإذن المعتمد يمكن رفضه لاحقاً (إلغاؤه)
                    فيخرج من حساب الساعات، والمعلّق والمرفوض لا يُحتسبان في الحد.
                </div>
            </div>
        </div>

        {{-- 4. الإجازات والأرصدة --}}
        <div class="card border-0 shadow-sm mb-4" id="sec-leaves">
            <div class="card-header border-bottom">
                <h5 class="card-title mb-0 fw-bold text-primary">
                    <i class="fa-solid fa-calendar-check me-2"></i> 4. الإجازات وأرصدة الموظفين وسلة المحذوفات (Leaves & Balances)
                </h5>
            </div>
            <div class="card-body pt-4">
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <div class="p-3 border rounded h-100">
                            <h6 class="fw-bold text-success mb-2"><i class="fa-solid fa-umbrella-beach me-1"></i> الإجازات مدفوعة الأجر (Paid Leaves)</h6>
                            <ul class="small text-muted mb-0 ps-3">
                                <li><strong>الإجازة السنوية:</strong> حسب الرصيد المسجّل في ملف الموظف (الافتراضي 21 يوماً).</li>
                                <li><strong>الإجازة العارضة:</strong> 7 أيام سنوياً افتراضياً.</li>
                                <li><strong>الإجازة المرضية:</strong> 14 يوماً سنوياً افتراضياً.</li>
                                <li><strong>تعديل الأرصدة:</strong> من شاشة «أرصدة الإجازات» لمن لديه صلاحية إدارة الأرصدة — لتطبيق أي سياسة خاصة (مثل 30 يوماً لمن تجاوزت خدمته 10 سنوات).</li>
                                <li><strong>الأثر المالي:</strong> لا يخصم أي مليم من الراتب؛ فقط يُخصم عدد الأيام من رصيد الموظف للسنة.</li>
                            </ul>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-3 border rounded h-100">
                            <h6 class="fw-bold text-danger mb-2"><i class="fa-solid fa-ban me-1"></i> الإجازات غير مدفوعة الأجر (Unpaid Leaves)</h6>
                            <ul class="small text-muted mb-0 ps-3">
                                <li><strong>التعريف:</strong> إجازة خاصة بدون راتب بناءً على طلب الموظف وموافقة الإدارة.</li>
                                <li><strong>الأثر على الرصيد:</strong> لا تستهلك شيئاً من رصيد الإجازات السنوية أو العارضة.</li>
                                <li><strong>الأثر المالي:</strong> يقوم محرك الرواتب آلياً بخصم قيمتها من راتب الشهر بحساب: <span dir="ltr">عدد الأيام &times; سعر يوم العمل</span>.</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <h6 class="fw-bold mb-2"><i class="fa-solid fa-list-check me-1 text-primary"></i> قواعد تُطبَّق تلقائياً</h6>
                <div class="table-responsive mb-3">
                    <table class="table table-bordered align-middle small mb-0">
                        <tbody>
                            <tr>
                                <td class="fw-bold" style="width:30%">لا تداخل</td>
                                <td>لا يُقبل طلب يتداخل مع إجازة أخرى معلّقة أو معتمدة لنفس الموظف.</td>
                            </tr>
                            <tr>
                                <td class="fw-bold">عدد الأيام منطقي</td>
                                <td>عدد الأيام المطلوب لا يتجاوز عدد أيام الفترة من تاريخ البداية للنهاية.</td>
                            </tr>
                            <tr>
                                <td class="fw-bold">الرصيد يكفي</td>
                                <td>عند الاعتماد يتحقق النظام أن المتبقي من رصيد النوع (سنوية/عارضة/مرضية) يكفي، وإلا يرفض الاعتماد برسالة توضح المتبقي.</td>
                            </tr>
                            <tr>
                                <td class="fw-bold">رفض المعتمد</td>
                                <td>رفض إجازة سبق اعتمادها (إلغاؤها) يُرجع أيامها لرصيد الموظف فوراً.</td>
                            </tr>
                            <tr>
                                <td class="fw-bold">الرصيد يُحسب ولا يُطرح</td>
                                <td>المستهلك يُعاد حسابه دائماً من الإجازات المعتمدة نفسها، فأي حذف أو استرجاع أو رفض يعطي الرقم الصحيح.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="alert alert-info small">
                    <i class="fa-solid fa-calendar-days me-1"></i>
                    <strong>إجازة بدون أجر تمتد بين شهرين:</strong> إجازة من 28 أكتوبر إلى 5 نوفمبر (9 أيام) —
                    يُخصم في مسير <strong>أكتوبر 4 أيام</strong> وفي مسير <strong>نوفمبر 5 أيام</strong>،
                    وليس الـ 9 كلها في شهر البداية. ونفس التوزيع يظهر في تقارير الموظف.
                </div>

                <div class="p-3 border rounded" style="background-color: rgba(115, 103, 240, 0.05);">
                    <h6 class="fw-bold text-primary mb-1"><i class="fa-solid fa-trash-can me-1"></i> حماية وتكامل سلة المحذوفات:</h6>
                    <p class="small text-muted mb-0">
                        عند حذف أي إجازة بالخطأ أو التراجع عنها، تنتقل الإجازة فوراً إلى <strong>سلة المحذوفات</strong>، ويقوم النظام تلقائياً بـ <strong>إعادة الأيام المستهلكة إلى رصيد الموظف فورياً</strong> لمنع ضياع رصيده. وإذا قرر المشرف استرجاعها من السلة، يعيد النظام خصم الأيام من الرصيد تلقائياً.
                    </p>
                </div>
            </div>
        </div>

        {{-- 5. السلف والقروض --}}
        <div class="card border-0 shadow-sm mb-4" id="sec-advances">
            <div class="card-header border-bottom">
                <h5 class="card-title mb-0 fw-bold text-primary">
                    <i class="fa-solid fa-hand-holding-dollar me-2"></i> 5. السلف والقروض المالية والإسقاط الإداري (Employee Advances)
                </h5>
            </div>
            <div class="card-body pt-4">
                <p class="text-muted leading-relaxed">
                    منظومة السلف مجهزة بدورة محاسبية كاملة تضمن عدم تضارب الأقساط مع المرتبات:
                </p>
                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <div class="p-3 border rounded text-center">
                            <i class="fa-solid fa-file-signature fs-3 text-primary mb-2"></i>
                            <h6 class="fw-bold mb-1">1. تقديم السلفة واعتمادها</h6>
                            <p class="small text-muted mb-0">تحديد المبلغ الإجمالي، تاريخ البدء، وعدد الأقساط. يحسب النظام القسط الشهري تلقائياً.</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 border rounded text-center">
                            <i class="fa-solid fa-money-bill-transfer fs-3 text-success mb-2"></i>
                            <h6 class="fw-bold mb-1">2. الخصم الآلي في المسير</h6>
                            <p class="small text-muted mb-0">يسحب محرك الرواتب القسط المستحق تلقائياً، وعند صرف المسير ينخفض الرصيد المتبقي بذمة الموظف.</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 border rounded text-center">
                            <i class="fa-solid fa-hand-holding-heart fs-3 text-info mb-2"></i>
                            <h6 class="fw-bold mb-1">3. الإسقاط الإداري (Waiver)</h6>
                            <p class="small text-muted mb-0">إمكانية إسقاط/إعفاء جزء أو كامل المبلغ بقرار إداري، فينخفض المتبقي فورياً دون خصم شيء من الراتب.</p>
                        </div>
                    </div>
                </div>

                <div class="alert alert-warning border-0 small mb-0">
                    <strong>الحالة ما تتغيّرش من الجدول.</strong>
                    عمود الحالة للعرض فقط، والتغيير بيتم من
                    <strong>أزرار الاعتماد والرفض</strong> اللي بتتحقق من صلاحيات
                    منفصلة (<code dir="ltr">advance_approve</code> /
                    <code dir="ltr">advance_reject</code>) وبتسجّل تاريخ القرار
                    والشخص اللي اتخذه.
                    <br>
                    الاعتماد والرفض متاحين للسلفة <strong>في حالة «في انتظار الاعتماد» فقط</strong> —
                    فسلفة اتصرفت واتسدّد جزء منها ما ينفعش ترجع مرفوضة وتضيّع تتبّع فلوس اتحصّلت.
                </div>

                <div class="table-responsive mt-3">
                    <table class="table table-bordered align-middle small mb-0">
                        <tbody>
                            <tr>
                                <td class="fw-bold" style="width:30%">السداد والإعفاء للمعتمدة فقط</td>
                                <td>
                                    السداد النقدي المباشر والإسقاط الإداري يُسجَّلان على سلفة <strong>معتمدة</strong> فقط
                                    (صلاحية <code dir="ltr">advance_update</code> — دور المحاسب).
                                    السلفة المعلّقة أو المرفوضة ترفض أي سداد، حتى لا تتحول «مسددة» دون أن يعتمدها أحد.
                                </td>
                            </tr>
                            <tr>
                                <td class="fw-bold">لا يتجاوز المتبقي</td>
                                <td>أي سداد أو إعفاء أكبر من الرصيد المتبقي يُرفض برسالة توضح المتبقي.</td>
                            </tr>
                            <tr>
                                <td class="fw-bold">الحالة تُحسب تلقائياً</td>
                                <td>عند وصول المتبقي لصفر تتحول السلفة <span class="badge bg-label-success">مسددة</span> تلقائياً، وتعود <span class="badge bg-label-info">معتمدة</span> لو حُذف سداد وأصبح عليها رصيد.</td>
                            </tr>
                            <tr>
                                <td class="fw-bold">لا حذف بعد السداد</td>
                                <td>السلفة التي عليها سداد أو إعفاء لا تُحذف — حذفها كان يُخفي تاريخ خصومات اتسجّلت على الموظف.</td>
                            </tr>
                            <tr>
                                <td class="fw-bold">ملخّص الموظف</td>
                                <td>إجمالي السلف والمسدد والمتبقي في رأس الشاشة <strong>يستبعد المرفوضة</strong>.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- 6. البدلات والمكافآت --}}
        <div class="card border-0 shadow-sm mb-4" id="sec-rewards-allowances">
            <div class="card-header border-bottom">
                <h5 class="card-title mb-0 fw-bold text-primary">
                    <i class="fa-solid fa-award me-2"></i> 6. البدلات الثابتة vs المكافآت والبدلات الاستثنائية
                </h5>
            </div>
            <div class="card-body pt-4">
                <div class="table-responsive">
                    <table class="table table-bordered table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>وجه المقارنة</th>
                                <th>البدلات الثابتة (Fixed Allowances)</th>
                                <th>المكافآت والبدلات الاستثنائية (Rewards)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><strong>أين تسجل؟</strong></td>
                                <td>في ملف الموظف وشاشة تعديل بياناته</td>
                                <td>في موديول <code>المكافآت والبدلات</code> المستقل</td>
                            </tr>
                            <tr>
                                <td><strong>طبيعة الصرف</strong></td>
                                <td>دورية متكررة كل شهر تلقائياً</td>
                                <td>استثنائية لمرة واحدة في شهر محدد</td>
                            </tr>
                            <tr>
                                <td><strong>أمثلة</strong></td>
                                <td>بدل سكن، بدل سيارة، بدل هاتف، بدل طبيعة عمل</td>
                                <td>مكافأة تميز، عمولة مبيعات، حافز إنجاز، عمل إضافي</td>
                            </tr>
                            <tr>
                                <td><strong>حالة الصرف</strong></td>
                                <td>تعتبر جزءاً من الراتب الشهري التعاقدي</td>
                                <td>تتطلب موافقة المدير أولاً ثم تتحول إلى "مدفوعة" عند صرف المسير</td>
                            </tr>
                            <tr>
                                <td><strong>الحذف</strong></td>
                                <td>من ملف الموظف</td>
                                <td>مسموح وهي معلّقة أو مرفوضة فقط — بعد الاعتماد أو الصرف أصبحت جزءاً من المسير فلا تُحذف</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- 7. الجزاءات وسعر اليوم --}}
        {{-- دورة اعتماد الحركات المالية --}}
        <div class="card border-0 shadow-sm mb-4" id="sec-approval-cycle">
            <div class="card-header border-bottom d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0 fw-bold text-primary">
                    <i class="fa-solid fa-user-check me-2"></i> دورة اعتماد الجزاءات والمكافآت (فصل المهام)
                </h5>
                <span class="badge bg-label-success font-monospace">جديد</span>
            </div>
            <div class="card-body pt-4">

                <p class="text-muted mb-4">
                    أي حركة تؤثر على راتب الموظف — <strong>خصم أو استحقاق</strong> — لم تعد تُطبَّق مباشرة.
                    أصبحت تمر بدورة اعتماد يراجعها <strong>شخص آخر غير مَن سجّلها</strong>،
                    وهو ما يُعرف محاسبياً بـ <span dir="ltr">Segregation of Duties</span> (فصل المهام).
                </p>

                <div class="p-3 border rounded mb-4 text-center" style="background: rgba(115, 103, 240, 0.06); border-radius: 12px;">
                    <div class="fs-6 fw-bold text-primary mb-2">مسار الحركة:</div>
                    <div class="fs-6 fw-bold text-dark">
                        <span class="badge bg-label-warning px-3 py-2">قيد الاعتماد</span>
                        <i class="fa-solid fa-arrow-left mx-2 text-muted"></i>
                        <span class="badge bg-label-success px-3 py-2">معتمد</span>
                        <i class="fa-solid fa-arrow-left mx-2 text-muted"></i>
                        <span class="badge bg-label-primary px-3 py-2">يدخل مسير الرواتب</span>
                    </div>
                    <div class="small text-muted mt-2">
                        أو: <span class="badge bg-label-danger">مرفوض</span> — لا يدخل المسير، ويُوثَّق سبب الرفض.
                    </div>
                </div>

                <h6 class="fw-bold mb-3 text-dark"><i class="fa-solid fa-list-check me-2 text-primary"></i> القواعد المطبَّقة:</h6>

                <div class="table-responsive mb-4">
                    <table class="table table-bordered align-middle">
                        <thead>
                            <tr>
                                <th style="width:32%">القاعدة</th>
                                <th>التفاصيل</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="fw-bold">البداية دائماً معلّقة</td>
                                <td class="small">الجزاء أو المكافأة تُسجَّل بحالة <span class="badge bg-label-warning">قيد الاعتماد</span> ولا تؤثر على الراتب.</td>
                            </tr>
                            <tr>
                                <td class="fw-bold">المعتمد فقط يدخل المسير</td>
                                <td class="small">عند إصدار مسير الرواتب، يتجاهل النظام كل الحركات المعلّقة أو المرفوضة.</td>
                            </tr>
                            <tr>
                                <td class="fw-bold text-danger">لا يعتمد أحدٌ ما سجّله بنفسه</td>
                                <td class="small">مَن سجّل الحركة لا يستطيع اعتمادها — يلزم مراجعة مسؤول آخر. (المدير العام مستثنى.)</td>
                            </tr>
                            <tr>
                                <td class="fw-bold">الرفض يتطلب سبباً</td>
                                <td class="small">سبب الرفض إلزامي ويُحفَظ في السجل، ويظهر كتلميح بجانب الحالة في الجدول.</td>
                            </tr>
                            <tr>
                                <td class="fw-bold">قرار واحد لا رجعة فيه</td>
                                <td class="small">بعد الاعتماد أو الرفض لا يمكن تغيير القرار — يُسجَّل قيد جديد بدلاً منه.</td>
                            </tr>
                            <tr>
                                <td class="fw-bold">المعتمد لا يُحذف</td>
                                <td class="small">بعد الاعتماد تصبح الحركة جزءاً من المسير، فحذفها يكسر التطابق المحاسبي.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <div class="card border border-danger h-100 shadow-none">
                            <div class="card-header bg-label-danger py-2">
                                <h6 class="mb-0 fw-bold"><i class="fa-solid fa-gavel me-1"></i> الجزاءات</h6>
                            </div>
                            <div class="card-body">
                                <p class="small text-muted mb-2">
                                    الجزاء خصم من راتب الموظف، وقانون العمل يتطلب
                                    <strong>تحقيقاً وموافقة موثّقة</strong> قبل أي خصم —
                                    والخصم غير المراجَع قابل للطعن قانونياً.
                                </p>
                                <div class="small">
                                    <span class="badge bg-label-secondary">الصلاحيات</span>
                                    <code class="small ms-1" dir="ltr">penalty_approve</code>
                                    <code class="small ms-1" dir="ltr">penalty_reject</code>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card border border-success h-100 shadow-none">
                            <div class="card-header bg-label-success py-2">
                                <h6 class="mb-0 fw-bold"><i class="fa-solid fa-award me-1"></i> المكافآت</h6>
                            </div>
                            <div class="card-body">
                                <p class="small text-muted mb-2">
                                    المكافأة صرف مبلغ للموظف. كانت تُعتمد ذاتياً من مسجّلها،
                                    أي أن شخصاً واحداً كان يستطيع <strong>تسجيل وصرف</strong> مكافأة
                                    دون مراجعة — وهي ثغرة رقابية أُغلقت.
                                </p>
                                <div class="small">
                                    <span class="badge bg-label-secondary">الصلاحيات</span>
                                    <code class="small ms-1" dir="ltr">reward_approve</code>
                                    <code class="small ms-1" dir="ltr">reward_reject</code>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="alert alert-info mb-0">
                    <h6 class="alert-heading fw-bold"><i class="fa-solid fa-gear me-1"></i> مَن يعتمد؟</h6>
                    <p class="small mb-0">
                        في <a href="#sec-roles">الأدوار الجاهزة</a>: <strong>أخصائي الموارد البشرية ومدير الفرع</strong>
                        يسجّلون الجزاءات والمكافآت، و<strong>مدير الموارد البشرية</strong> (والمدير العام) يعتمدها.
                        لو عدّلت الأدوار يدوياً، يُفضَّل ألا تُجمع صلاحية التسجيل والاعتماد في دور واحد —
                        ومع ذلك يمنع النظام أي شخص من اعتماد حركة سجّلها بنفسه.
                    </p>
                </div>

            </div>
        </div>

        <div class="card border-0 shadow-sm mb-4" id="sec-penalties">
            <div class="card-header border-bottom">
                <h5 class="card-title mb-0 fw-bold text-primary">
                    <i class="fa-solid fa-gavel me-2"></i> 7. الجزاءات والخصومات واحتساب سعر يوم العمل
                </h5>
            </div>
            <div class="card-body pt-4">
                <p class="text-muted leading-relaxed">
                    لحماية حقوق الشركة والموظف، يعتمد النظام آلية قياسية لحساب الخصومات:
                </p>

                <div class="alert alert-warning">
                    <i class="fa-solid fa-user-check me-1"></i>
                    <strong>الجزاء لا يُخصم إلا بعد الاعتماد.</strong>
                    يُسجَّل بحالة «قيد الاعتماد» ولا يدخل مسير الرواتب حتى يراجعه مسؤول آخر —
                    <a href="#sec-approval-cycle" class="alert-link">راجع دورة الاعتماد</a>.
                </div>

                <div class="alert alert-info">
                    <i class="fa-solid fa-lock me-1"></i>
                    <strong>المبلغ يُثبَّت وقت التسجيل.</strong>
                    لو تغيّر راتب الموظف لاحقاً، يبقى الخصم بالمبلغ الذي رآه المعتمِد ووافق عليه،
                    ولا يُعاد حسابه من الراتب الجديد.
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <div class="p-3 border rounded">
                            <h6 class="fw-bold text-danger mb-2">معادلة سعر اليوم (Daily Rate Formula)</h6>
                            <div class="font-monospace fw-bold text-dark fs-6" dir="ltr">Daily Rate = Basic Salary / 30</div>
                            <small class="text-muted mt-1 d-block">
                                يقسم الراتب الأساسي على 30 يوماً شهرياً. عند تطبيق خصم يوم أو يومين، يحسب المبلغ تلقائياً فور اختيار اسم الموظف.
                                <strong>لو للموظف سعر يوم خاص</strong> في ملفه يُستخدم بدلاً من المعادلة.
                            </small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-3 border rounded">
                            <h6 class="fw-bold text-info mb-2">معادلة سعر الساعة (Hourly Rate Formula)</h6>
                            <div class="font-monospace fw-bold text-dark fs-6" dir="ltr">Hourly Rate = Daily Rate / 8 Working Hours</div>
                            <small class="text-muted mt-1 d-block">
                                يقسم سعر اليوم على 8 ساعات عمل يومية (أو عدد الساعات المسجل للموظف) لاستخدامه في خصم ساعات الاستئذان.
                                <strong>لو للموظف سعر ساعة خاص</strong> يُستخدم مباشرة، ولو له سعر يوم خاص فقط تُحسب الساعة منه
                                (مثال: سعر يوم خاص 160 &divide; 8 = 20 للساعة).
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- 8. مسير الرواتب ودورة الاعتماد --}}
        <div class="card border-0 shadow-sm mb-4" id="sec-payroll-lifecycle">
            <div class="card-header border-bottom">
                <h5 class="card-title mb-0 fw-bold text-primary">
                    <i class="fa-solid fa-money-check-dollar me-2"></i> 8. مسير الرواتب ودورة الاعتماد والصرف (Payroll Workflow)
                </h5>
            </div>
            <div class="card-body pt-4">
                <div class="row g-3 text-center mb-3">
                    <div class="col-md-3">
                        <div class="p-3 border rounded h-100">
                            <span class="badge bg-warning mb-2">1. مسودة (Draft)</span>
                            <p class="small text-muted mb-0">يتم توليد الكشف آلياً وسحب كافة استحقاقات وخصومات وسلف الشهر. <strong>التعديل والحذف في المسودة فقط.</strong></p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="p-3 border rounded h-100">
                            <span class="badge bg-info mb-2">2. معتمد (Approved)</span>
                            <p class="small text-muted mb-0">يُقفَل التعديل تمهيداً للصرف. إلغاء الاعتماد ممكن (بسبب مكتوب) طالما لم يُصرف، فيرجع مسودة.</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="p-3 border rounded h-100">
                            <span class="badge bg-success mb-2">3. مدفوع (Paid)</span>
                            <p class="small text-muted mb-0">حالة نهائية — لا رجوع منها. تُسجَّل أقساط السلف وتُصرف المكافآت تلقائياً.</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="p-3 border rounded h-100">
                            <span class="badge bg-danger mb-2">4. مرفوض (Rejected)</span>
                            <p class="small text-muted mb-0">بسبب مكتوب (3 أحرف على الأقل). يمكن إعادة تقديمه فيرجع مسودة للتصحيح.</p>
                        </div>
                    </div>
                </div>

                <h6 class="fw-bold mt-4 mb-2"><i class="fa-solid fa-people-arrows me-1 text-primary"></i> مَن يفعل ماذا (فصل المهام)</h6>
                <div class="p-3 border rounded mb-3 text-center" style="background: rgba(115, 103, 240, 0.06);">
                    <span class="badge bg-label-primary px-3 py-2">مدير الموارد البشرية: يُصدر ويعدّل</span>
                    <i class="fa-solid fa-arrow-left mx-2 text-muted"></i>
                    <span class="badge bg-label-info px-3 py-2">المدير العام: يعتمد أو يرفض</span>
                    <i class="fa-solid fa-arrow-left mx-2 text-muted"></i>
                    <span class="badge bg-label-success px-3 py-2">المحاسب: يصرف</span>
                    <div class="small text-muted mt-2">
                        حسب <a href="#sec-roles">الأدوار الجاهزة</a> — لا يوجد دور (غير المدير العام) يستطيع الإصدار والاعتماد والصرف وحده.
                    </div>
                </div>

                <div class="table-responsive mb-3">
                    <table class="table table-bordered align-middle small mb-0">
                        <tbody>
                            <tr>
                                <td class="fw-bold" style="width:30%">الإصدار</td>
                                <td>
                                    اختيار السنة والشهر والفرع. <strong>بدون فرع</strong> = كشف مستقل لكل فرع فيه موظفون نشطون
                                    (كل فرع بعملته)، والفروع التي لها كشف بالفعل تُتخطّى مع ذكر أسمائها.
                                    لا يمكن إصدار كشفين لنفس الفرع والشهر.
                                </td>
                            </tr>
                            <tr>
                                <td class="fw-bold">ما يدخل الكشف</td>
                                <td>
                                    الموظفون <strong>النشطون</strong> فقط، وكل بند يجمع: الأساسي + البدلات الثابتة + المكافآت المعتمدة
                                    − الجزاءات المعتمدة − أيام الإجازة بدون أجر في الشهر − الاستئذان الزائد − قسط كل سلفة معتمدة بدأ سدادها
                                    (<a href="#sec-formula">المعادلة</a>). طريقة الصرف الافتراضية تحويل بنكي لمن له حساب بنكي، ونقدي لغيره.
                                </td>
                            </tr>
                            <tr>
                                <td class="fw-bold">التعديل في المسودة</td>
                                <td>تعديل البدلات والخصومات وطريقة الصرف لكل بند، أو تفاصيله سطراً سطراً. الإجماليات تُعاد حسابها تلقائياً.</td>
                            </tr>
                            <tr>
                                <td class="fw-bold">الصرف</td>
                                <td>
                                    صرف <strong>بند واحد</strong> أو <strong>بنود محددة</strong> أو <strong>الكشف كله</strong>.
                                    البند المصروف لا يُصرف مرتين ولا يُخصم قسط سلفته مرتين حتى لو ضُغط الزر مرتين.
                                    عند صرف آخر بند يتحول الكشف تلقائياً إلى <span class="badge bg-label-success">مدفوع</span>.
                                </td>
                            </tr>
                            <tr>
                                <td class="fw-bold">ما يحدث عند الصرف</td>
                                <td>
                                    يُسجَّل سداد قسط كل سلفة في سجل سداداتها ويقل المتبقي، وتتحول المكافآت المصروفة إلى
                                    <span class="badge bg-label-info">تم الصرف مع الراتب</span>، وتصبح قسائم الرواتب جاهزة للطباعة
                                    (فردية أو مجمّعة).
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="alert alert-info small mb-0">
                    <i class="fa-solid fa-bell me-1"></i>
                    عند إصدار أي كشف يصل <a href="#sec-notifications">إشعار</a> لمن لديهم صلاحية الرواتب في نفس الفرع بأن كشفاً بانتظار المراجعة.
                </div>
            </div>
        </div>

        {{-- 9. المبيعات وقنوات البيع --}}
        <div class="card border-0 shadow-sm mb-4" id="sec-sales-channels-customers">
            <div class="card-header border-bottom">
                <h5 class="card-title mb-0 fw-bold text-primary">
                    <i class="fa-solid fa-cart-shopping me-2"></i> 9. المبيعات، الفواتير، قنوات البيع والعملاء (Sales Ecosystem)
                </h5>
            </div>
            <div class="card-body pt-4">
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="p-3 border rounded h-100">
                            <h6 class="fw-bold text-primary mb-2"><i class="fa-solid fa-address-book me-1"></i> إدارة العملاء والشركات</h6>
                            <p class="small text-muted mb-0">
                                تسجيل العميل (أفراد أو شركات)، الأرقام الضريبية، جهات الاتصال، الحدود الائتمانية، وشروط الدفع (دفع مقدم، عند الاستلام، صافي 30 أو 60 يوماً).
                                أرقام الهاتف بتستخدم <strong>نفس مكتبة هاتف الموظف</strong>: تختار الدولة والنظام يتحقق من صحة الرقم.
                            </p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 border rounded h-100">
                            <h6 class="fw-bold text-success mb-2"><i class="fa-solid fa-diagram-project me-1"></i> قنوات البيع والتطبيقات</h6>
                            <p class="small text-muted mb-0">
                                إدارة منصات البيع (جاهز، هنقرستيشن، مرسول، المتاجر الإلكترونية، المعارض، البيع المباشر) وتحديد نسبة عمولة كل منصة لتسوية الأرباح الصافية.
                            </p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 border rounded h-100">
                            <h6 class="fw-bold text-info mb-2"><i class="fa-solid fa-file-invoice me-1"></i> فواتير المبيعات والإيرادات</h6>
                            <p class="small text-muted mb-0">
                                إنشاء الفواتير بالبنود التفصيلية مع احتساب ضريبة القيمة المضافة والخصومات، وتتبع حالات الدفع، وتغذية تقارير الأرباح والخسائر والتدفق النقدي.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="table-responsive mt-4">
                    <table class="table table-bordered align-middle small mb-0">
                        <tbody>
                            <tr>
                                <td class="fw-bold" style="width:30%">ترقيم الفواتير</td>
                                <td>
                                    تلقائي بصيغة <span dir="ltr">INV-YYYYMM-0001</span> ويبدأ من جديد كل شهر.
                                    الرقم فريد على مستوى الشركة كلها، والرقم التالي = أكبر رقم مستخدم في الشهر + 1 في كل الفروع.
                                    وأكواد العملاء بنفس الطريقة (<span dir="ltr">CUST-0001</span>).
                                </td>
                            </tr>
                            <tr>
                                <td class="fw-bold">المبالغ تُحسب على السيرفر</td>
                                <td>
                                    إجمالي كل بند = الكمية &times; السعر، والإجمالي الفرعي = مجموع البنود،
                                    والإجمالي = الفرعي + الضريبة − الخصم. هذه الأرقام يحسبها النظام عند الحفظ
                                    ولا تُؤخذ جاهزة من الشاشة، فلا يمكن حفظ فاتورة بنودها لا تساوي إجماليها.
                                    الفاتورة بدون بنود تحتفظ بالإجمالي الفرعي المُدخل يدوياً.
                                </td>
                            </tr>
                            <tr>
                                <td class="fw-bold">حالة الدفع والتحصيل</td>
                                <td>
                                    <span class="badge bg-label-success">مدفوعة</span>
                                    <span class="badge bg-label-warning">مدفوعة جزئياً</span>
                                    <span class="badge bg-label-danger">غير مدفوعة</span> —
                                    تُسجَّل الدفعات على الفاتورة من شاشتها، ولا تُقبل دفعة أكبر من المستحق،
                                    والمدفوع والمستحق والحالة تُعاد حسابها من الدفعات نفسها.
                                </td>
                            </tr>
                            <tr>
                                <td class="fw-bold">الفاتورة المتأخرة</td>
                                <td>
                                    غير مدفوعة أو مدفوعة جزئياً وعليها مستحق، ومرّ على تاريخها أكثر من <strong>30 يوماً</strong>.
                                    زر «فواتير متأخرة» في لوحة التحكم يفتح قائمة المبيعات مفلترة عليها بنفس التعريف.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- مصادر العملاء --}}
        <div class="card border-0 shadow-sm mb-4" id="sec-customer-sources">
            <div class="card-header border-bottom d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0 fw-bold text-primary">
                    <i class="fa-solid fa-share-nodes me-2"></i> مصادر العملاء (Customer Sources)
                </h5>
                <span class="badge bg-label-success font-monospace">جديد</span>
            </div>
            <div class="card-body pt-4">

                <p class="text-muted mb-4">
                    شاشة <code dir="ltr">/admin/customer-sources</code> بتخليك تدير قائمة
                    <strong>"إزاي وصل العميل ده لينا"</strong> بنفسك. قبل كده كانت القائمة
                    مكتوبة جوه الكود، يعني إضافة مصدر جديد كانت محتاجة مبرمج.
                </p>

                <div class="table-responsive mb-4">
                    <table class="table table-bordered align-middle">
                        <thead>
                            <tr><th style="width:28%">الحقل</th><th>الشرح</th></tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="fw-bold">الكود</td>
                                <td class="small">كود مختصر فريد بيُستخدم في الربط والتقارير.</td>
                            </tr>
                            <tr>
                                <td class="fw-bold">اسم المصدر</td>
                                <td class="small">الاسم اللي بيظهر في قائمة "مصدر العميل" في شاشة العميل.</td>
                            </tr>
                            <tr>
                                <td class="fw-bold">ترتيب العرض</td>
                                <td class="small">رقم بيحدد ترتيب الظهور في القائمة — الأقل بيطلع الأول.</td>
                            </tr>
                            <tr>
                                <td class="fw-bold">الحالة</td>
                                <td class="small">
                                    المصدر المعطّل بيختفي من القائمة عند إضافة عميل جديد،
                                    <strong>لكنه بيفضل ظاهر</strong> في شاشة تعديل أي عميل مرتبط بيه
                                    عشان مايتمسحش بالغلط.
                                </td>
                            </tr>
                            <tr>
                                <td class="fw-bold">عدد العملاء</td>
                                <td class="small">
                                    رقم قابل للضغط بينقلك لقائمة العملاء <strong>مفلترة على المصدر ده</strong>،
                                    فتعرف كل قناة جابتلك مين.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="alert alert-warning mb-0 h-100">
                            <h6 class="alert-heading fw-bold"><i class="fa-solid fa-triangle-exclamation me-1"></i> المصدر المرتبط بعملاء لا يُحذف</h6>
                            <p class="small mb-0">
                                لو المصدر عليه عملاء، الحذف بيترفض. الحذف كان هيسيب العملاء
                                من غير مصدر ويضيّع معلومة مالهاش بديل.
                                <strong>عطّله بدل ما تحذفه.</strong>
                            </p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="alert alert-info mb-0 h-100">
                            <h6 class="alert-heading fw-bold"><i class="fa-solid fa-clock-rotate-left me-1"></i> سلة محذوفات وسجل حركات</h6>
                            <p class="small mb-0">
                                الحذف ناعم — السجل بيروح السلة وينفع يترجع.
                                وكل إضافة أو تعديل أو حذف بيتسجّل في
                                <a href="#sec-activity-log">سجل حركات النظام</a>.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="alert alert-secondary small mt-3 mb-0">
                    <i class="fa-solid fa-key me-1"></i>
                    <strong>صلاحية لكل إجراء:</strong> العرض والإضافة والتعديل والحذف صلاحيات منفصلة
                    (<code dir="ltr">customer_source_show / _create / _update / _delete</code>) —
                    مَن له صلاحية العرض فقط لا يستطيع التعديل.
                </div>

            </div>
        </div>

        {{-- 10. الموردين والاشتراكات --}}
        <div class="card border-0 shadow-sm mb-4" id="sec-vendors-subscriptions">
            <div class="card-header border-bottom">
                <h5 class="card-title mb-0 fw-bold text-primary">
                    <i class="fa-solid fa-repeat me-2"></i> 10. الموردين والاشتراكات والخدمات الدورية (Vendors & Subscriptions)
                </h5>
            </div>
            <div class="card-body pt-4">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="p-3 border rounded h-100">
                            <h6 class="fw-bold text-primary mb-2"><i class="fa-solid fa-handshake me-1"></i> الموردين والشركات الخارجية</h6>
                            <p class="small text-muted mb-0">
                                أرشفة بيانات الشركات الموردة، السجلات التجارية، الحسابات البنكية، وتاريخ التعاملات المالية وسندات الصرف المسددة لهم.
                            </p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-3 border rounded h-100">
                            <h6 class="fw-bold text-warning mb-2"><i class="fa-solid fa-bell me-1"></i> الاشتراكات التقنية والتشغيلية</h6>
                            <p class="small text-muted mb-0">
                                تتبع السيرفرات السحابية (AWS/DigitalOcean)، الاشتراكات البرمجية، إيجارات الفروع، وعقود الصيانة الدورية مع تنبيهات بمواعيد التجديد القادمة.
                            </p>
                        </div>
                    </div>
                </div>

                <ul class="small text-muted mt-3 mb-0 ps-3">
                    <li><strong>معزولة بالفرع:</strong> مدير الفرع يرى ويعدّل اشتراكات فرعه فقط، ولا يستطيع تسجيل اشتراك لفرع آخر. الاشتراك العام (بدون فرع) يديره المدير العام.</li>
                    <li><strong>تسجيل دفعة:</strong> من شاشة الاشتراك، مع إمكانية تحديد تاريخ التجديد التالي في نفس الخطوة.</li>
                    <li><strong>الحذف بتأكيد:</strong> حذف مورد أو اشتراك يطلب تأكيداً أولاً، والمورد المرتبط باشتراكات لا يُحذف.</li>
                </ul>
            </div>
        </div>

        {{-- 11. العهد والمصروفات --}}
        <div class="card border-0 shadow-sm mb-4" id="sec-expenses-petty-cash">
            <div class="card-header border-bottom">
                <h5 class="card-title mb-0 fw-bold text-primary">
                    <i class="fa-solid fa-wallet me-2"></i> 11. العهد النقدية، المصروفات وتعدد العملات (Petty Cash & Expenses)
                </h5>
            </div>
            <div class="card-body pt-4">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="p-3 border rounded h-100">
                            <h6 class="fw-bold text-primary mb-2"><i class="fa-solid fa-coins me-1"></i> تخصيص العهدة بعملة كل فرع</h6>
                            <p class="small text-muted mb-0">
                                لا يوجد خلط بين العملات؛ عهدة فرع الرياض بالريال السعودي (<span dir="ltr">SAR</span>)، وعهدة فرع القاهرة بالجنيه المصري (<span dir="ltr">EGP</span>)، وعهدة فرع دبي بالدرهم (<span dir="ltr">AED</span>). يظهر في الصفحة الرصيد المخصص، المصروف، والمتبقي بدقة متناهية.
                            </p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-3 border rounded h-100">
                            <h6 class="fw-bold text-success mb-2"><i class="fa-solid fa-receipt me-1"></i> تصنيفات النفقات ومراكز التكلفة</h6>
                            <p class="small text-muted mb-0">
                                توجيه المصروفات على تصنيفات محددة (ضيافة، صيانة، نثريات، وقود، مشتريات عاجلة) مع وجوب إرفاق الفاتورة أو الإيصال الضريبي لإحكام الرقابة المالية قبل التسوية.
                            </p>
                        </div>
                    </div>
                </div>

                <h6 class="fw-bold mt-4 mb-2"><i class="fa-solid fa-share me-1 text-primary"></i> ترحيل المتبقي للشهر التالي</h6>
                <p class="small text-muted mb-2">
                    المتبقي من عهدة كل فرع يُرحَّل <strong>تلقائياً بالكامل</strong> للشهر التالي ويُضاف لمخصصه —
                    لا يوجد زر ترحيل يدوي ولا تحتاج لأي إجراء:
                </p>
                <div class="table-responsive mb-3">
                    <table class="table table-bordered align-middle small mb-0">
                        <thead>
                            <tr><th>الشهر</th><th>المخصص</th><th>المرحّل</th><th>الإجمالي</th><th>المصروف</th><th>المتبقي</th></tr>
                        </thead>
                        <tbody>
                            <tr><td>أغسطس</td><td dir="ltr">6,305</td><td dir="ltr">0</td><td dir="ltr">6,305</td><td dir="ltr">5,014</td><td dir="ltr"><strong>1,291</strong></td></tr>
                            <tr><td>سبتمبر</td><td dir="ltr">5,000</td><td dir="ltr">1,291</td><td dir="ltr">6,291</td><td dir="ltr">5,873</td><td dir="ltr"><strong>418</strong></td></tr>
                            <tr><td>أكتوبر</td><td dir="ltr">5,000</td><td dir="ltr">418</td><td dir="ltr"><strong>5,418</strong></td><td dir="ltr">—</td><td dir="ltr">—</td></tr>
                        </tbody>
                    </table>
                </div>
                <ul class="small text-muted mb-0 ps-3">
                    <li><strong>كل فرع لوحده:</strong> مع اختيار "جميع الفروع" يُرحَّل متبقي كل فرع لنفس الفرع، ولا يُجمع في رصيد مشترك.</li>
                    <li><strong>كل طرق الدفع:</strong> المصروف المخصوم يشمل النقدي والتحويل البنكي والبطاقة — نفس رقم بطاقة "المتبقي الفعلي".</li>
                    <li><strong>تحديث فوري:</strong> تسجيل أو تعديل أو حذف مصروف في شهر سابق يُصحّح ترحيل كل الشهور التالية مباشرة.</li>
                    <li><strong>العجز لا يُرحَّل:</strong> لو المصروف تجاوز الإجمالي يبدأ الشهر التالي بمخصصه فقط.</li>
                </ul>

                <h6 class="fw-bold mt-4 mb-2"><i class="fa-solid fa-user-lock me-1 text-primary"></i> الصلاحيات والرقابة</h6>
                <div class="table-responsive mb-3">
                    <table class="table table-bordered align-middle small mb-0">
                        <tbody>
                            <tr>
                                <td class="fw-bold" style="width:30%">تحديد المخصص الشهري</td>
                                <td>صلاحية منفصلة <code dir="ltr">expense_balance_manage</code> (دور المحاسب) — مسؤول العهدة يسجّل المصروفات لكنه لا يحدد مخصصه.</td>
                            </tr>
                            <tr>
                                <td class="fw-bold">مصروف بتاريخ شهر سابق</td>
                                <td>
                                    تسجيل أو تعديل مصروف في شهر <strong>قبل الشهر الحالي</strong> يحتاج صلاحية
                                    <code dir="ltr">expense_backdate</code> — لأنه يغيّر ترحيل كل الشهور التالية.
                                    الشهر الحالي متاح لأي مَن له صلاحية الإضافة.
                                </td>
                            </tr>
                            <tr>
                                <td class="fw-bold">عزل الفروع</td>
                                <td>
                                    مسؤول العهدة ومدير الفرع يريان مصروفات وأرصدة عهدة <strong>فرعهم فقط</strong>،
                                    ولا يستطيعان تسجيل مصروف أو تحديد مخصص لفرع آخر.
                                </td>
                            </tr>
                            <tr>
                                <td class="fw-bold">تنبيه نقص الرصيد</td>
                                <td>يومياً، لو المتبقي من عهدة الفرع نزل تحت 20% يصل <a href="#sec-notifications">إشعار</a> لمن لديه صلاحية المصروفات في الفرع.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>


        {{-- الروابط المختصرة --}}
        <div class="card border-0 shadow-sm mb-4" id="sec-short-links">
            <div class="card-header border-bottom">
                <h5 class="card-title mb-0 fw-bold text-primary">
                    <i class="fa-solid fa-link me-2"></i> الروابط المختصرة والإحصائيات (Short Links)
                    <span class="badge bg-label-success ms-1">جديد</span>
                </h5>
            </div>
            <div class="card-body pt-4">

                <p class="text-muted mb-4">
                    تحويل أي رابط طويل إلى رابط قصير قابل للمشاركة، مع تتبّع كامل لعدد النقرات
                    والزوار ومصادر الزيارات. الفائدة الأساسية: <strong>يمكنك تغيير وجهة الرابط
                    لاحقاً دون تغيير الرابط المنشور</strong> — فلو نشرت رابطاً في حملة إعلانية
                    ثم تغيّر عنوان الصفحة، تُعدّل الوجهة من النظام ويظل الرابط المنشور يعمل.
                </p>

                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <div class="p-3 border rounded h-100">
                            <h6 class="fw-bold text-primary mb-2"><i class="fa-solid fa-wand-magic-sparkles me-1"></i> الكود المختصر</h6>
                            <p class="small text-muted mb-2">
                                يُولَّد كود عشوائي تلقائياً ويظهر جاهزاً في الحقل عند الإنشاء،
                                ويمكنك تغييره أو الضغط على زر التوليد للحصول على غيره.
                                الطول <span dir="ltr">{{ config('links.code_length', 7) }}</span> أحرف،
                                والحروف المتشابهة (<span dir="ltr">0 O 1 l I</span>) مستبعدة
                                لتفادي اللبس عند القراءة أو الكتابة اليدوية.
                            </p>
                            <p class="small text-muted mb-0">
                                <strong>حسّاس لحالة الأحرف:</strong> <span dir="ltr">aB7xK9m</span>
                                و<span dir="ltr">Ab7Xk9M</span> رابطان مختلفان تماماً.
                            </p>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="p-3 border rounded h-100">
                            <h6 class="fw-bold text-success mb-2"><i class="fa-solid fa-shield-halved me-1"></i> قواعد التحكم</h6>
                            <ul class="small text-muted mb-0 ps-3">
                                <li><strong>تاريخ انتهاء:</strong> يتوقف الرابط تلقائياً بعده.</li>
                                <li><strong>حد أقصى للنقرات:</strong> يتوقف بعد بلوغ العدد.</li>
                                <li><strong>إيقاف مؤقت:</strong> دون حذف، ويمكن إعادة تفعيله.</li>
                                <li>الرابط الموقوف أو المنتهي يعرض صفحة اعتذار للزائر، ولا يحوّل.</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="alert alert-primary d-flex align-items-start" role="alert">
                    <i class="fa-solid fa-circle-info me-2 mt-1"></i>
                    <div class="small">
                        <strong>الوجهة محمية:</strong> لا يُقبل إلا رابط <span dir="ltr">http</span> أو
                        <span dir="ltr">https</span> يشير لعنوان عام. الروابط الداخلية
                        (<span dir="ltr">localhost</span> أو شبكة داخلية) مرفوضة، وكذلك اختصار
                        رابط مختصر من النظام نفسه لتفادي الدوران اللانهائي.
                    </div>
                </div>

                <h6 class="fw-bold mt-4 mb-2"><i class="fa-solid fa-chart-line me-1 text-primary"></i> الإحصائيات وكيف تُحتسب</h6>

                <div class="table-responsive mb-3">
                    <table class="table table-bordered align-middle">
                        <thead>
                            <tr>
                                <th style="width:28%">المؤشر</th>
                                <th>كيف يُحتسب</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="small fw-semibold">النقرات</td>
                                <td class="small">كل فتح فعلي للرابط من زائر حقيقي.</td>
                            </tr>
                            <tr>
                                <td class="small fw-semibold">الزوار الفريدون</td>
                                <td class="small">الزائر الواحد يُحتسب مرة واحدة في اليوم مهما كرّر الفتح.</td>
                            </tr>
                            <tr>
                                <td class="small fw-semibold">زيارات البوتات</td>
                                <td class="small">
                                    معاينات واتساب وتيليجرام وفيسبوك ومحركات البحث. تُسجَّل في
                                    خانة منفصلة و<strong>لا تُحتسب ضمن النقرات</strong> — فمجرد
                                    إرسال الرابط في مجموعة واتساب يولّد عشرات المعاينات الوهمية.
                                </td>
                            </tr>
                            <tr>
                                <td class="small fw-semibold">التوزيعات</td>
                                <td class="small">الدول والأجهزة والمتصفحات ومصادر الزيارات — للزوار الحقيقيين فقط.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="alert alert-info d-flex align-items-start" role="alert">
                    <i class="fa-solid fa-clock me-2 mt-1"></i>
                    <div class="small">
                        <strong>التحديث كل 5 دقائق.</strong> لتبقى صفحة التحويل سريعة، تُسجَّل
                        النقرة في الذاكرة المؤقتة أولاً، ثم تُنقل لقاعدة البيانات وتُحدَّث كل
                        الإحصائيات — بما فيها إحصائيات اليوم الجاري — كل 5 دقائق تلقائياً.
                        فقد تجد نقرة في آخر دقيقتين لم تنعكس بعد، وتظهر في التحديث التالي.
                        <br>
                        <strong>لو الذاكرة المؤقتة (Redis) غير متاحة على السيرفر</strong> تُكتب النقرة في قاعدة البيانات
                        مباشرة وتظهر فوراً — أبطأ قليلاً لكن لا تضيع أي نقرة، والزائر يُحوَّل في كل الأحوال.
                    </div>
                </div>

                <div class="alert alert-secondary d-flex align-items-start mb-0" role="alert">
                    <i class="fa-solid fa-user-shield me-2 mt-1"></i>
                    <div class="small">
                        <strong>الخصوصية:</strong> لا يُخزَّن عنوان <span dir="ltr">IP</span> الزائر
                        مطلقاً — يُحفظ مُشفَّراً فقط بما يكفي للتفرقة بين الزوار. تفاصيل النقرات
                        تُحذف بعد <span dir="ltr">{{ config('links.clicks_retention', 90) }}</span>
                        يوماً، بينما يبقى الملخّص اليومي دائماً فلا يضيع التاريخ.
                    </div>
                </div>

            </div>
        </div>


        {{-- التقارير --}}
        <div class="card border-0 shadow-sm mb-4" id="sec-reports">
            <div class="card-header border-bottom d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0 fw-bold text-primary">
                    <i class="fa-solid fa-chart-pie me-2"></i> التقارير
                </h5>
                <span class="badge bg-label-success font-monospace">جديد</span>
            </div>
            <div class="card-body pt-4">

                <p class="text-muted mb-4">
                    كل تقرير له صلاحية مستقلة، ويحترم <a href="#sec-branch-isolation">عزل الفروع</a>:
                    مدير الفرع يرى أرقام فرعه فقط.
                </p>

                <div class="table-responsive mb-3">
                    <table class="table table-bordered align-middle">
                        <thead>
                            <tr><th style="width:26%">التقرير</th><th>ماذا يعرض</th><th style="width:22%">الصلاحية</th></tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="fw-bold">كشف حساب الموظف</td>
                                <td class="small">
                                    لموظف وفترة: أيام الإجازات بأنواعها، ساعات الاستئذان والزائد عن الحد، الجزاءات،
                                    وتكلفة الخصومات المتوقعة — مع سجل زمني لكل الطلبات <strong>بحالتها الحقيقية</strong>
                                    (المعلّق والمرفوض يظهران بدون تكلفة).
                                </td>
                                <td class="small"><code dir="ltr">report_employee_statement</code></td>
                            </tr>
                            <tr>
                                <td class="fw-bold">تدقيق الراتب</td>
                                <td class="small">
                                    تفصيل الراتب المتوقع لموظف في فترة: الأساسي والبدلات والمكافآت، والجزاءات المعتمدة
                                    والاستئذان الزائد والإجازات بدون أجر وأقساط السلف المسددة —
                                    <strong>بنفس قواعد المسير بالظبط</strong>، فيطابق ما سيُصرف.
                                </td>
                                <td class="small"><code dir="ltr">report_salary_audit</code></td>
                            </tr>
                            <tr>
                                <td class="fw-bold">الأرباح والخسائر</td>
                                <td class="small">
                                    لفترة وفرع: الإيرادات − (المصروفات + مدفوعات الموردين + الرواتب المصروفة)،
                                    <strong>مفصولة لكل عملة</strong> مع هامش الربح، وآخر الفواتير والمصروفات.
                                </td>
                                <td class="small"><code dir="ltr">report_financial_pl</code></td>
                            </tr>
                            <tr>
                                <td class="fw-bold">متابعة المستندات</td>
                                <td class="small">
                                    مستندات الموظفين المنتهية، والتي تنتهي خلال 30 يوماً، وغير الموثّقة —
                                    لتجديد الإقامات والعقود قبل فوات موعدها.
                                </td>
                                <td class="small"><code dir="ltr">report_documents</code></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="alert alert-info small mb-0">
                    <i class="fa-solid fa-file-excel me-1"></i>
                    شاشات الجزاءات والمكافآت والسلف والمصروفات فيها زر <strong>تصدير Excel</strong> بنفس الفلاتر المختارة.
                </div>

            </div>
        </div>

        {{-- الأدوار والصلاحيات --}}
        @php($rolePresets = \App\Services\Role\RolePresets::all())
        <div class="card border-0 shadow-sm mb-4" id="sec-roles">
            <div class="card-header border-bottom d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0 fw-bold text-primary">
                    <i class="fa-solid fa-user-shield me-2"></i> الأدوار والصلاحيات حسب التخصص
                </h5>
                <span class="badge bg-label-success font-monospace">جديد</span>
            </div>
            <div class="card-body pt-4">

                <p class="text-muted mb-4">
                    كل مشرف له <strong>دور</strong> يحدد الشاشات والأزرار المتاحة له. النظام يأتي بأدوار جاهزة
                    لكل تخصص، ويمكن تعديلها أو إنشاء أدوار جديدة من شاشة <strong>الأدوار</strong>.
                    الزر الذي لا يملك المستخدم صلاحيته لا يظهر له أصلاً، ومحاولة فتح رابطه مباشرة تُرفض.
                </p>

                <div class="table-responsive mb-4">
                    <table class="table table-bordered align-middle">
                        <thead>
                            <tr><th style="width:22%">الدور</th><th>المسؤوليات</th><th style="width:12%" class="text-center">الصلاحيات</th></tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="fw-bold">صلاحية كاملة</td>
                                <td class="small">كل شيء بدون استثناء، بما فيه إدارة المشرفين والأدوار والإعدادات والحذف النهائي. لمسؤول النظام فقط.</td>
                                <td class="text-center"><span class="badge bg-label-dark">{{ count(all_roles()) }}</span></td>
                            </tr>
                            @foreach ($rolePresets as $preset)
                                <tr>
                                    <td class="fw-bold">{{ $preset['name'] }}</td>
                                    <td class="small">{{ $preset['description'] }}</td>
                                    <td class="text-center"><span class="badge bg-label-primary">{{ count($preset['permissions']) }}</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <h6 class="fw-bold mb-3 text-dark"><i class="fa-solid fa-scale-balanced me-2 text-primary"></i> فصل المهام — لا أحد يُنهي حركة مالية وحده:</h6>
                <div class="table-responsive mb-4">
                    <table class="table table-bordered align-middle small">
                        <thead>
                            <tr><th style="width:22%">الحركة</th><th>يسجّل</th><th>يعتمد</th><th>ينفّذ</th></tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="fw-bold">مسير الرواتب</td>
                                <td>مدير الموارد البشرية</td>
                                <td>المدير العام</td>
                                <td>المحاسب (الصرف)</td>
                            </tr>
                            <tr>
                                <td class="fw-bold">جزاء / مكافأة</td>
                                <td>أخصائي الموارد البشرية · مدير الفرع</td>
                                <td>مدير الموارد البشرية</td>
                                <td>يدخل المسير تلقائياً</td>
                            </tr>
                            <tr>
                                <td class="fw-bold">سلفة</td>
                                <td>أخصائي الموارد البشرية · مدير الفرع</td>
                                <td>مدير الموارد البشرية</td>
                                <td>المحاسب (سداد/إعفاء) · المسير (الأقساط)</td>
                            </tr>
                            <tr>
                                <td class="fw-bold">إجازة / إذن</td>
                                <td>أخصائي الموارد البشرية · مدير الفرع</td>
                                <td>مدير الفرع · مدير الموارد البشرية</td>
                                <td>—</td>
                            </tr>
                            <tr>
                                <td class="fw-bold">مصروف عهدة</td>
                                <td>مسؤول العهدة</td>
                                <td>—</td>
                                <td>المحاسب يحدد المخصص الشهري ويسجّل بأثر رجعي</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="alert alert-warning mb-0 h-100">
                            <h6 class="alert-heading fw-bold"><i class="fa-solid fa-building-lock me-1"></i> الدور شيء والفرع شيء آخر</h6>
                            <p class="small mb-0">
                                الدور يحدد <strong>ماذا</strong> يفعل المستخدم، أما <strong>على أي فرع</strong> فيحدده ربط حسابه بموظف:
                                اربط حسابات <strong>مدير الفرع ومسؤول العهدة</strong> بموظف في فرعهم، و<strong>لا تربط</strong>
                                حسابات المدير العام ومدير الموارد البشرية والمحاسب والمراجع بموظف — وإلا سيرون فرعاً واحداً.
                                <a href="#sec-branch-isolation">التفاصيل</a>.
                            </p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="alert alert-info mb-0 h-100">
                            <h6 class="alert-heading fw-bold"><i class="fa-solid fa-shield-halved me-1"></i> قواعد ثابتة</h6>
                            <ul class="small mb-0 ps-3">
                                <li>لا يوجد دور جاهز يملك <strong>الحذف النهائي</strong> أو إدارة المشرفين والأدوار والإعدادات.</li>
                                <li>لا يعتمد أحد حركة سجّلها بنفسه، حتى لو يملك الصلاحيتين.</li>
                                <li>حذف دور ينقل مشرفيه تلقائياً لـ «صلاحية احتياطية» (عرض اللوحة فقط).</li>
                                <li>بعد تعديل دور يُطبَّق على مشرفيه فوراً.</li>
                            </ul>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        {{-- البحث والفلترة في الجداول --}}
        <div class="card border-0 shadow-sm mb-4" id="sec-table-filters">
            <div class="card-header border-bottom d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0 fw-bold text-primary">
                    <i class="fa-solid fa-filter me-2"></i> البحث والفلترة في الجداول
                </h5>
                <span class="badge bg-label-success font-monospace">محدَّث</span>
            </div>
            <div class="card-body pt-4">

                <p class="text-muted mb-4">
                    كل جدول في النظام تحت رؤوس الأعمدة فيه <strong>صف بحث</strong>.
                    نوع خانة البحث بيتغيّر حسب طبيعة العمود نفسه، فمش لازم تحفظ
                    القيمة المخزّنة عشان تلاقي حاجة.
                </p>

                <div class="table-responsive mb-4">
                    <table class="table table-bordered align-middle">
                        <thead>
                            <tr><th style="width:26%">نوع العمود</th><th>خانة البحث</th></tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="fw-bold text-primary">قيم محددة</td>
                                <td class="small">
                                    الحالة، النوع، طريقة الدفع، مفعّل/معطّل —
                                    <strong>قائمة منسدلة</strong> بالخيارات المتاحة فقط.
                                    مفيش كتابة حرة، ومفيش خيار الجدول يرفضه عند الحفظ.
                                </td>
                            </tr>
                            <tr>
                                <td class="fw-bold text-info">التواريخ</td>
                                <td class="small">
                                    <strong>حقل تاريخ</strong> بتقويم — بدل ما تكتب الصيغة بإيدك.
                                </td>
                            </tr>
                            <tr>
                                <td class="fw-bold text-secondary">نص حر</td>
                                <td class="small">الأسماء والأكواد والملاحظات — خانة كتابة عادية.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="alert alert-info mb-0 h-100">
                            <h6 class="alert-heading fw-bold"><i class="fa-solid fa-list-check me-1"></i> مصدر واحد لكل قائمة</h6>
                            <p class="small mb-0">
                                خيارات أي قائمة (فلتر الصفحة، فلتر الجدول، وشاشة الإضافة)
                                بتيجي كلها من <strong>نفس ملف الترجمة</strong>، وبتطابق
                                القيم المسموح بيها في قاعدة البيانات بالظبط.
                                فمستحيل تلاقي فلتر بيعرض كلام والجدول بيعرض كلام تاني.
                            </p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="alert alert-success mb-0 h-100">
                            <h6 class="alert-heading fw-bold"><i class="fa-solid fa-magnifying-glass me-1"></i> قوائم قابلة للبحث</h6>
                            <p class="small mb-0">
                                كل القوائم المنسدلة في النظام فيها <strong>خانة بحث</strong>،
                                فلو القائمة طويلة (زي الموظفين أو العملاء) تكتب أول حروف
                                الاسم بدل ما تقلّب فيها.
                            </p>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        {{-- 12. سجل حركات النظام --}}
        <div class="card border-0 shadow-sm mb-4" id="sec-activity-log">
            <div class="card-header border-bottom d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0 fw-bold text-primary">
                    <i class="fa-solid fa-shield-halved me-2"></i> 12. سجل حركات النظام والتدقيق (Audit Log)
                </h5>
                <span class="badge bg-label-success font-monospace">جديد</span>
            </div>
            <div class="card-body pt-4">

                <p class="text-muted mb-4">
                    النظام يسجّل تلقائياً <strong>كل إضافة وتعديل وحذف</strong> تتم على البيانات، مع تسجيل
                    <strong>من قام بالعملية</strong> و<strong>القيمة قبل التعديل والقيمة بعده</strong>،
                    بدون أي إجراء إضافي من المستخدم. الهدف هو توفير مسار تدقيق كامل يمكن الرجوع إليه
                    لمعرفة من غيّر ماذا ومتى.
                </p>

                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <div class="p-3 border rounded h-100">
                            <h6 class="fw-bold text-primary mb-2"><i class="fa-solid fa-list-check me-1"></i> العمليات المسجّلة</h6>
                            <ul class="small text-muted mb-0 ps-3">
                                <li><span class="badge bg-label-success">إضافة</span> — تسجيل سجل جديد بكل بياناته</li>
                                <li><span class="badge bg-label-info">تعديل</span> — الحقول المتغيّرة فقط، قبل وبعد</li>
                                <li><span class="badge bg-label-warning">حذف</span> — النقل لسلة المحذوفات</li>
                                <li><span class="badge bg-label-primary">استرجاع</span> — الإرجاع من السلة</li>
                                <li><span class="badge bg-label-danger">حذف نهائي</span> — الإزالة الكاملة</li>
                            </ul>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-3 border rounded h-100">
                            <h6 class="fw-bold text-success mb-2"><i class="fa-solid fa-fingerprint me-1"></i> بيانات كل حركة</h6>
                            <ul class="small text-muted mb-0 ps-3">
                                <li>اسم المستخدم الذي نفّذ العملية</li>
                                <li>الموديول والسجل المتأثر (بالاسم وليس الرقم فقط)</li>
                                <li>القيم قبل وبعد لكل حقل تغيّر</li>
                                <li>عنوان <span dir="ltr">IP</span>، المتصفح، والرابط المستخدم</li>
                                <li>التاريخ والوقت بالثانية</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <h6 class="fw-bold mb-3 text-dark"><i class="fa-solid fa-eye me-2 text-primary"></i> طريقتان لعرض السجل:</h6>

                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <div class="p-3 border rounded h-100" style="border-right: 4px solid #7367f0 !important;">
                            <h6 class="fw-bold mb-2">السجل الشامل</h6>
                            <p class="small text-muted mb-2">
                                يعرض حركات <strong>كل الموديولات مجتمعة</strong> في شاشة واحدة، مع فلاتر:
                                الموديول، نوع العملية، المستخدم، والفترة الزمنية.
                            </p>
                            <code class="small" dir="ltr">/admin/activity-logs</code>
                            <div class="mt-2">
                                <span class="badge bg-label-warning">يتطلب صلاحية: عرض السجل الشامل</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-3 border rounded h-100" style="border-right: 4px solid #28c76f !important;">
                            <h6 class="fw-bold mb-2">سجل موديول واحد</h6>
                            <p class="small text-muted mb-2">
                                يعرض حركات موديول بعينه فقط — مثلاً كل ما جرى على فواتير المبيعات،
                                أو كل التعديلات على ملفات الموظفين.
                            </p>
                            <code class="small" dir="ltr">/admin/activity-logs/subject/Sale</code>
                            <div class="mt-2">
                                <span class="badge bg-label-warning">يتطلب صلاحية: عرض سجل الحركات</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="p-3 border rounded mb-4" style="background: rgba(115, 103, 240, 0.06);">
                    <h6 class="fw-bold text-primary mb-2"><i class="fa-solid fa-layer-group me-1"></i> الموديولات الخاضعة للتسجيل ({{ count(activity_subjects()) }} موديول)</h6>
                    <div class="d-flex flex-wrap gap-2">
                        @foreach (activity_subjects() as $subjectLabel)
                            <span class="badge bg-label-secondary">{{ $subjectLabel }}</span>
                        @endforeach
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="alert alert-warning mb-0 h-100">
                            <h6 class="alert-heading fw-bold"><i class="fa-solid fa-lock me-1"></i> السجل غير قابل للتعديل</h6>
                            <p class="small mb-0">
                                لا يمكن تعديل أو حذف أي سجل من الواجهة نهائياً — حتى للمدير العام —
                                لضمان أن مسار التدقيق يبقى موثوقاً. الحذف يتم فقط عبر أمر التنظيف الدوري.
                            </p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="alert alert-success mb-0 h-100">
                            <h6 class="alert-heading fw-bold"><i class="fa-solid fa-user-shield me-1"></i> البيانات الحساسة محمية</h6>
                            <p class="small mb-0">
                                كلمات المرور، الأرقام القومية، أرقام الحسابات البنكية، و<span dir="ltr">IBAN</span>
                                <strong>لا تُسجَّل إطلاقاً</strong> في السجل، حتى لو تم تعديلها.
                            </p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="alert alert-info mb-0 h-100">
                            <h6 class="alert-heading fw-bold"><i class="fa-solid fa-broom me-1"></i> التنظيف التلقائي</h6>
                            <p class="small mb-0">
                                السجلات الأقدم من <strong>{{ config('activity.retention_days', 90) }} يوم</strong>
                                تُحذف تلقائياً كل ليلة، للحفاظ على حجم قاعدة البيانات وسرعة الاستعلام.
                            </p>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        {{-- 13. عزل البيانات بين الفروع --}}
        <div class="card border-0 shadow-sm mb-4" id="sec-branch-isolation">
            <div class="card-header border-bottom d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0 fw-bold text-primary">
                    <i class="fa-solid fa-building-lock me-2"></i> 13. عزل البيانات بين الفروع (Branch Isolation)
                </h5>
                <span class="badge bg-label-danger font-monospace">أمان</span>
            </div>
            <div class="card-body pt-4">

                <p class="text-muted mb-4">
                    النظام يفصل بيانات كل فرع عن الآخر تلقائياً. مدير الفرع يرى ويعدّل بيانات فرعه فقط،
                    ولا يستطيع الوصول لبيانات فرع آخر <strong>حتى لو حاول تغيير رقم السجل في الرابط مباشرة</strong>.
                </p>

                <div class="p-3 border rounded mb-4" style="background: rgba(115, 103, 240, 0.06); border-radius: 12px;">
                    <h6 class="fw-bold text-primary mb-2"><i class="fa-solid fa-diagram-project me-1"></i> كيف يحدد النظام فرع المستخدم؟</h6>
                    <div class="fs-6 fw-bold text-dark font-monospace text-center py-2" dir="rtl">
                        حساب المشرف ← الموظف المرتبط به ← الفرع التابع له
                    </div>
                    <p class="small text-muted mb-0 text-center">
                        أي مشرف غير مُعلَّم كـ <span class="badge bg-label-primary">مدير عام</span>
                        ومرتبط بموظف له فرع، سيرى بيانات ذلك الفرع فقط.
                    </p>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <div class="card border border-success h-100 shadow-none">
                            <div class="card-header bg-label-success py-2">
                                <h6 class="mb-0 fw-bold"><i class="fa-solid fa-crown me-1"></i> المدير العام</h6>
                            </div>
                            <div class="card-body">
                                <ul class="small text-muted mb-0 ps-3">
                                    <li>يرى بيانات <strong>كل الفروع</strong> بدون استثناء</li>
                                    <li>يصدر مسير رواتب شامل لكل الموظفين</li>
                                    <li>تقارير الأرباح والخسائر تشمل كل الفروع</li>
                                    <li>يختار أي فرع من الفلاتر</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card border border-warning h-100 shadow-none">
                            <div class="card-header bg-label-warning py-2">
                                <h6 class="mb-0 fw-bold"><i class="fa-solid fa-user-tie me-1"></i> مدير الفرع</h6>
                            </div>
                            <div class="card-body">
                                <ul class="small text-muted mb-0 ps-3">
                                    <li>القوائم تعرض <strong>سجلات فرعه فقط</strong></li>
                                    <li>محاولة فتح سجل فرع آخر ترجع صفحة غير موجودة</li>
                                    <li><strong>لا يستطيع إنشاء</strong> سجل لفرع آخر أو لموظف في فرع آخر، ولا نقل موظف لفرع آخر</li>
                                    <li>قوائم اختيار الفرع في الشاشات تعرض فرعه فقط</li>
                                    <li>التقارير المالية محصورة بأرقام فرعه</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <h6 class="fw-bold mb-3 text-dark"><i class="fa-solid fa-table-list me-2 text-primary"></i> الموديولات المشمولة بالعزل:</h6>
                <div class="d-flex flex-wrap gap-2 mb-2">
                    <span class="badge bg-label-primary">الموظفون</span>
                    <span class="badge bg-label-primary">الرواتب والمستندات والمهارات</span>
                    <span class="badge bg-label-primary">الإجازات وأرصدتها</span>
                    <span class="badge bg-label-primary">الأذونات</span>
                    <span class="badge bg-label-primary">الجزاءات</span>
                    <span class="badge bg-label-primary">المكافآت</span>
                    <span class="badge bg-label-primary">السلف</span>
                    <span class="badge bg-label-primary">مسيرات الرواتب وبنودها</span>
                    <span class="badge bg-label-primary">المصروفات</span>
                    <span class="badge bg-label-primary">أرصدة العهد</span>
                    <span class="badge bg-label-primary">فواتير المبيعات</span>
                    <span class="badge bg-label-primary">اشتراكات الموردين</span>
                    <span class="badge bg-label-primary">الروابط المختصرة</span>
                    <span class="badge bg-label-secondary">لوحة التحكم والتقارير</span>
                    <span class="badge bg-label-secondary">الإشعارات</span>
                </div>
                <p class="small text-muted mb-4">
                    سجلات الموظف (إجازاته وجزاءاته وسلفه...) تتبع فرع الموظف نفسه. أما العملاء والموردون
                    وقنوات البيع ومصادر العملاء والتصنيفات فمشتركة بين كل الفروع.
                </p>

                <div class="alert alert-info mb-0">
                    <h6 class="alert-heading fw-bold"><i class="fa-solid fa-triangle-exclamation me-1"></i> ملاحظة إدارية مهمة</h6>
                    <p class="small mb-0">
                        العزل يعمل على أي مشرف <strong>مرتبط بملف موظف</strong> له فرع، أياً كان دوره.
                        فاربط حسابات <strong>مدير الفرع ومسؤول العهدة</strong> بموظف في فرعهم،
                        واترك حسابات من يحتاج رؤية الشركة كلها (المدير العام، الموارد البشرية، المحاسب، المراجع)
                        <strong>غير مرتبطة بموظف</strong> — <a href="#sec-roles">راجع الأدوار</a>.
                        والمشرف المعلَّم <span class="badge bg-label-primary">مدير عام</span> يرى كل الفروع في كل الأحوال.
                    </p>
                </div>

            </div>
        </div>

        {{-- 14. الدخول واستعادة كلمة المرور --}}
        <div class="card border-0 shadow-sm mb-4" id="sec-auth-security">
            <div class="card-header border-bottom d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0 fw-bold text-primary">
                    <i class="fa-solid fa-key me-2"></i> 14. تسجيل الدخول واستعادة كلمة المرور
                </h5>
                <span class="badge bg-label-danger font-monospace">أمان</span>
            </div>
            <div class="card-body pt-4">

                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <div class="p-3 border rounded h-100">
                            <h6 class="fw-bold text-primary mb-2"><i class="fa-solid fa-right-to-bracket me-1"></i> حماية تسجيل الدخول</h6>
                            <ul class="small text-muted mb-0 ps-3">
                                <li>الحد الأقصى <strong>5 محاولات فاشلة</strong> ثم إيقاف مؤقت 5 دقائق</li>
                                <li>الإيقاف محسوب لكل بريد إلكتروني وكل عنوان <span dir="ltr">IP</span> على حدة</li>
                                <li>تجديد جلسة الدخول عند كل تسجيل ناجح</li>
                                <li><strong>تسجيل الخروج</strong> يُنهي الجلسة بالكامل، ولا يمكن تنفيذه من رابط أو صورة في صفحة خارجية</li>
                                <li>حد للطلبات: <strong>300 طلب في الدقيقة لكل مستخدم</strong> بعد الدخول (60 لكل <span dir="ltr">IP</span> قبله) — فموظفو مكتب واحد خلف نفس الإنترنت لا يعطّلون بعضهم</li>
                            </ul>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-3 border rounded h-100">
                            <h6 class="fw-bold text-success mb-2"><i class="fa-solid fa-envelope-open-text me-1"></i> استعادة كلمة المرور</h6>
                            <ul class="small text-muted mb-0 ps-3">
                                <li>يصل <strong>رابط مؤقت</strong> على البريد، وليس كلمة مرور جاهزة</li>
                                <li>الرابط صالح <strong>60 دقيقة</strong> و<strong>لمرة واحدة فقط</strong></li>
                                <li>كلمة المرور لا تتغيّر إلا عندما يفتح صاحب الحساب الرابط بنفسه</li>
                                <li>الحد الأدنى: 8 أحرف تحتوي حروفاً وأرقاماً</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="alert alert-success mb-0">
                    <h6 class="alert-heading fw-bold"><i class="fa-solid fa-shield-halved me-1"></i> لماذا الرابط المؤقت أكثر أماناً؟</h6>
                    <p class="small mb-0">
                        في الطريقة القديمة كان النظام يولّد كلمة مرور جديدة ويغيّرها فوراً ثم يرسلها،
                        وهذا يعني أن أي شخص يعرف بريد مشرف كان بإمكانه <strong>إقفال حسابه</strong> دون
                        الوصول للبريد أصلاً. الآن الحساب يبقى يعمل بكلمة مروره الحالية حتى يقوم صاحبه
                        فعلياً بفتح الرابط وتحديد كلمة مرور جديدة.
                    </p>
                </div>

            </div>
        </div>

        {{-- 15. الإشعارات --}}
        <div class="card border-0 shadow-sm mb-4" id="sec-notifications">
            <div class="card-header border-bottom d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0 fw-bold text-primary">
                    <i class="fa-solid fa-bell me-2"></i> 15. الإشعارات والتنبيهات اللحظية
                </h5>
                <span class="badge bg-label-success font-monospace">جديد</span>
            </div>
            <div class="card-body pt-4">

                <p class="text-muted mb-4">
                    النظام ينبّه المسؤولين تلقائياً بأي طلب أو حالة تحتاج تدخلاً بشرياً،
                    بدل أن يضطر المدير لفتح كل شاشة ليعرف إن كان هناك طلب معلّق.
                    الإشعار يظهر في <strong>جرس الإشعارات</strong> أعلى الصفحة،
                    ويصل <strong>لحظياً</strong> دون إعادة تحميل الصفحة.
                </p>

                <h6 class="fw-bold mb-3 text-dark"><i class="fa-solid fa-list-check me-2 text-primary"></i> أنواع الإشعارات:</h6>

                <div class="table-responsive mb-4">
                    <table class="table table-bordered align-middle">
                        <thead>
                            <tr>
                                <th style="width:30%">الإشعار</th>
                                <th>متى يُرسل</th>
                                <th style="width:22%">من يستقبله</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><span class="badge bg-label-info">طلب إجازة جديد</span></td>
                                <td class="small">فور تسجيل طلب إجازة</td>
                                <td class="small">من له صلاحية عرض الإجازات في نفس الفرع</td>
                            </tr>
                            <tr>
                                <td><span class="badge bg-label-success">نتيجة طلب الإجازة</span></td>
                                <td class="small">عند الاعتماد أو الرفض</td>
                                <td class="small">الموظف صاحب الإجازة (لو مربوط بحساب مشرف)</td>
                            </tr>
                            <tr>
                                <td><span class="badge bg-label-info">طلب سلفة جديد</span></td>
                                <td class="small">فور تسجيل طلب سلفة</td>
                                <td class="small">من له صلاحية عرض السلف في نفس الفرع</td>
                            </tr>
                            <tr>
                                <td><span class="badge bg-label-success">نتيجة طلب السلفة</span></td>
                                <td class="small">عند الاعتماد أو الرفض</td>
                                <td class="small">الموظف صاحب السلفة (لو مربوط بحساب مشرف)</td>
                            </tr>
                            <tr>
                                <td><span class="badge bg-label-info">مسير بانتظار الاعتماد</span></td>
                                <td class="small">فور إصدار مسير رواتب</td>
                                <td class="small">من له صلاحية عرض الرواتب في نفس الفرع</td>
                            </tr>
                            <tr>
                                <td><span class="badge bg-label-warning">اشتراك يقترب من التجديد</span></td>
                                <td class="small">يومياً — خلال 7 أيام من الاستحقاق</td>
                                <td class="small">من له صلاحية عرض الاشتراكات في فرع الاشتراك</td>
                            </tr>
                            <tr>
                                <td><span class="badge bg-label-danger">رصيد عهدة منخفض</span></td>
                                <td class="small">يومياً — عند نزول المتبقي تحت 20%</td>
                                <td class="small">من له صلاحية عرض المصروفات في نفس الفرع</td>
                            </tr>
                            <tr>
                                <td><span class="badge bg-label-danger">فواتير متأخرة</span></td>
                                <td class="small">
                                    يومياً — <strong>ملخّص واحد لكل فرع</strong>: عدد الفواتير المتأخرة أكثر من 30 يوماً
                                    وإجمالي المستحق لكل عملة، مع رابط للقائمة مفلترة عليها
                                </td>
                                <td class="small">من له صلاحية عرض المبيعات في نفس الفرع</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <div class="alert alert-info mb-0 h-100">
                            <h6 class="alert-heading fw-bold"><i class="fa-solid fa-user-shield me-1"></i> يحترم الصلاحيات</h6>
                            <p class="small mb-0">
                                لا يصل إشعار السلف لمن ليس لديه صلاحية السلف. كل نوع مرتبط
                                بصلاحيته، فلا تتسرب معلومة لمن لا يملك حق الاطلاع عليها.
                            </p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="alert alert-warning mb-0 h-100">
                            <h6 class="alert-heading fw-bold"><i class="fa-solid fa-building-lock me-1"></i> يحترم عزل الفروع</h6>
                            <p class="small mb-0">
                                مدير الفرع يستقبل إشعارات فرعه فقط. المدير العام يستقبل كل شيء.
                                القناة اللحظية خاصة بكل مشرف على حدة.
                            </p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="alert alert-success mb-0 h-100">
                            <h6 class="alert-heading fw-bold"><i class="fa-solid fa-shield-halved me-1"></i> لا يعطّل العمل ولا يتكرر</h6>
                            <p class="small mb-0">
                                فشل إرسال الإشعار لا يوقف العملية الأصلية — طلب الإجازة يُسجَّل
                                حتى لو تعذّر إرسال التنبيه. والتنبيهات اليومية لا تتكرر لنفس الشيء في نفس اليوم
                                حتى لو شُغّل الأمر أكثر من مرة. ولا يصل للشخص إشعار بفعل قام به هو.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="alert alert-warning mb-0">
                    <h6 class="alert-heading fw-bold"><i class="fa-solid fa-plug me-1"></i> تفعيل التنبيه اللحظي (Pusher)</h6>
                    <p class="small mb-2">
                        الإشعارات تعمل بدون Pusher (تظهر عند فتح الجرس). لتفعيل الوصول
                        <strong>اللحظي</strong> دون إعادة تحميل، أضف بيانات حساب Pusher في ملف
                        <code dir="ltr">.env</code> وغيّر الناقل:
                    </p>
                    <pre class="small mb-0 p-2 rounded" dir="ltr" style="background: rgba(0,0,0,0.05);">BROADCAST_CONNECTION=pusher
PUSHER_APP_ID=...
PUSHER_APP_KEY=...
PUSHER_APP_SECRET=...
PUSHER_APP_CLUSTER=eu</pre>
                </div>

            </div>
        </div>

        {{-- 16. أوامر الصيانة --}}
        <div class="card border-0 shadow-sm mb-4" id="sec-maintenance">
            <div class="card-header border-bottom">
                <h5 class="card-title mb-0 fw-bold text-primary">
                    <i class="fa-solid fa-terminal me-2"></i> 16. أوامر الصيانة والتشغيل الدوري
                </h5>
            </div>
            <div class="card-body pt-4">

                <p class="text-muted mb-4">
                    أوامر تُنفَّذ من سطر الأوامر على السيرفر. هذا القسم موجّه للمسؤول التقني.
                </p>

                <div class="table-responsive mb-4">
                    <table class="table table-bordered align-middle">
                        <thead>
                            <tr>
                                <th style="width:38%">الأمر</th>
                                <th>الوظيفة</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><code class="small" dir="ltr">php artisan activity:prune</code></td>
                                <td class="small">حذف سجلات الحركات الأقدم من مدة الاحتفاظ الافتراضية ({{ config('activity.retention_days', 90) }} يوم)</td>
                            </tr>
                            <tr>
                                <td><code class="small" dir="ltr">php artisan activity:prune --days=180</code></td>
                                <td class="small">تحديد مدة احتفاظ مخصصة بعدد الأيام</td>
                            </tr>
                            <tr>
                                <td><code class="small" dir="ltr">php artisan activity:prune --dry-run</code></td>
                                <td class="small">معاينة ما سيُحذف مع جدول توزيع، دون حذف فعلي</td>
                            </tr>
                            <tr>
                                <td><code class="small" dir="ltr">php artisan activity:prune --subject="App\Models\Sale"</code></td>
                                <td class="small">تنظيف سجلات موديول واحد فقط</td>
                            </tr>
                            <tr>
                                <td><code class="small" dir="ltr">php artisan backup:run</code></td>
                                <td class="small">نسخة احتياطية من قاعدة البيانات والمرفقات (تلقائية يومياً 3 صباحاً)</td>
                            </tr>
                            <tr>
                                <td><code class="small" dir="ltr">php artisan backup:run --db-only</code></td>
                                <td class="small">نسخة من قاعدة البيانات فقط بدون المرفقات</td>
                            </tr>
                            <tr>
                                <td><code class="small" dir="ltr">php artisan alerts:send</code></td>
                                <td class="small">إرسال التنبيهات الدورية (تلقائية يومياً 8 صباحاً)</td>
                            </tr>
                            <tr>
                                <td><code class="small" dir="ltr">php artisan alerts:send --dry-run</code></td>
                                <td class="small">معاينة التنبيهات دون إرسالها فعلياً</td>
                            </tr>
                            <tr>
                                <td><code class="small" dir="ltr">php artisan petty-cash:carry-over</code></td>
                                <td class="small">إعادة حساب ترحيل العهدة لكل فرع من أول شهر له (تلقائية أول كل شهر — والترحيل يُحدَّث فوراً أيضاً مع أي تعديل في المصروفات)</td>
                            </tr>
                            <tr>
                                <td><code class="small" dir="ltr">php artisan notifications:prune</code></td>
                                <td class="small">حذف الإشعارات المقروءة الأقدم من {{ config('notifications.read_retention_days', 30) }} يوماً، وأي إشعار أقدم من {{ config('notifications.retention_days', 90) }} يوماً (تلقائية يومياً 2:30 صباحاً — <code dir="ltr">--dry-run</code> للمعاينة)</td>
                            </tr>
                            <tr>
                                <td><code class="small" dir="ltr">php artisan links:flush</code></td>
                                <td class="small">نقل نقرات الروابط المختصرة من الذاكرة المؤقتة لقاعدة البيانات (تلقائية كل 5 دقائق)</td>
                            </tr>
                            <tr>
                                <td><code class="small" dir="ltr">php artisan links:aggregate</code></td>
                                <td class="small">تحديث جدول الإحصائيات لليوم الجاري وأمس (تلقائية كل 5 دقائق)</td>
                            </tr>
                            <tr>
                                <td><code class="small" dir="ltr">php artisan links:prune</code></td>
                                <td class="small">حذف تفاصيل النقرات الأقدم من {{ config('links.clicks_retention', 90) }} يوم — الملخّص اليومي يبقى دائماً (تلقائية 3:30 صباحاً)</td>
                            </tr>
                            <tr>
                                <td><code class="small" dir="ltr">php artisan realtime:test</code></td>
                                <td class="small">فحص جاهزية البث اللحظي (Pusher) وبيان أي إعداد ناقص</td>
                            </tr>
                            <tr>
                                <td><code class="small" dir="ltr">php artisan realtime:test --send</code></td>
                                <td class="small">إرسال إشعار تجريبي للتأكد من وصوله لحظياً للجرس</td>
                            </tr>
                            <tr>
                                <td><code class="small" dir="ltr">php artisan queue:work</code></td>
                                <td class="small">
                                    معالجة الطابور (إرسال البريد) — تلقائية كل دقيقة من الجدولة، لا تحتاج تشغيلها يدوياً.
                                    لو ركّبت worker دائماً عبر Supervisor (<code dir="ltr">deploy/supervisor/hr-queue-worker.conf</code>)
                                    ضع <code dir="ltr">QUEUE_SUPERVISED=true</code> في <code dir="ltr">.env</code> فتتوقف النسخة المجدولة.
                                </td>
                            </tr>
                            <tr>
                                <td><code class="small" dir="ltr">php artisan optimize:clear</code></td>
                                <td class="small">مسح كل الكاش (الإعدادات والمسارات والواجهات) — <strong>مطلوب بعد رفع أي تحديث</strong> للسيرفر</td>
                            </tr>
                            <tr>
                                <td><code class="small" dir="ltr">php artisan db:seed --class=RoleSeeder</code></td>
                                <td class="small">إضافة <a href="#sec-roles">الأدوار الجاهزة</a> الناقصة فقط — لا يكرر دوراً موجوداً ولا يلغي أي تعديل عليه</td>
                            </tr>
                            <tr>
                                <td><code class="small" dir="ltr">php artisan db:seed</code></td>
                                <td class="small">
                                    <strong>بيئة التطوير فقط:</strong> بيانات تجريبية كاملة (فروع وموظفين وفواتير).
                                    التنصيب الجديد يضيف تلقائياً الإعدادات والأدوار والمشرف الرئيسي فقط — بدون بيانات تجريبية.
                                </td>
                            </tr>
                            <tr>
                                <td><code class="small" dir="ltr">php artisan lang:check-missing</code></td>
                                <td class="small">التأكد من أن كل مفتاح ترجمة عربي له مقابل إنجليزي</td>
                            </tr>
                            <tr>
                                <td><code class="small" dir="ltr">php artisan lang:diff</code></td>
                                <td class="small">تقرير تفصيلي بفروق مفاتيح الترجمة في الاتجاهين</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="alert alert-warning mb-0">
                    <h6 class="alert-heading fw-bold"><i class="fa-solid fa-clock me-1"></i> إعداد المهام المجدولة (مطلوب مرة واحدة)</h6>
                    <p class="small mb-2">
                        كل المهام التلقائية في الجدول أعلاه (التنبيهات، النسخ الاحتياطي، ترحيل العهدة، الروابط،
                        التنظيف، ومعالجة الطابور) تعتمد على سطر واحد في جدول
                        <span dir="ltr">Cron</span> على السيرفر يعمل كل دقيقة:
                    </p>
                    <pre class="small mb-0 p-2 rounded" dir="ltr" style="background: rgba(0,0,0,0.05);">* * * * * cd /path-to-project &amp;&amp; php artisan schedule:run &gt;&gt; /dev/null 2&gt;&amp;1</pre>
                </div>

            </div>
        </div>

    </div>
</div>
@endsection

@push('js')
<script>
document.addEventListener('DOMContentLoaded', function () {

    var toc = document.querySelector('#guideToc');
    if (!toc) return;

    var links = Array.prototype.slice.call(toc.querySelectorAll('a[href^="#"]'));

    // نربط كل رابط بالقسم اللي بيوديله
    var items = links.map(function (link) {
        return { link: link, section: document.querySelector(link.getAttribute('href')) };
    }).filter(function (item) {
        return item.section;
    });

    if (!items.length) return;

    function activate(link) {
        links.forEach(function (a) { a.classList.remove('active'); });
        link.classList.add('active');

        // لو القائمة طويلة بنخلّي العنصر النشط ظاهر جواها
        var box = toc.parentElement;
        if (box && box.scrollHeight > box.clientHeight) {
            var top    = link.offsetTop;
            var bottom = top + link.offsetHeight;

            if (top < box.scrollTop) {
                box.scrollTop = top - 8;
            } else if (bottom > box.scrollTop + box.clientHeight) {
                box.scrollTop = bottom - box.clientHeight + 8;
            }
        }
    }

    // الضغط على رابط بيميّزه فوراً من غير ما نستنى التمرير
    var clicked = null;

    items.forEach(function (item) {
        item.link.addEventListener('click', function () {
            clicked = item.link;
            activate(item.link);

            // بعد ما التمرير يهدى نرجّع التتبّع التلقائي
            setTimeout(function () { clicked = null; }, 700);
        });
    });

    // الشريط العلوي بيغطي جزء من الصفحة، فبنبدأ الحساب من تحته
    var OFFSET = 110;

    function currentSection() {
        var found = items[0].link;

        for (var i = 0; i < items.length; i++) {
            if (items[i].section.getBoundingClientRect().top - OFFSET <= 0) {
                found = items[i].link;
            } else {
                break;
            }
        }

        // آخر الصفحة: آخر قسم هو النشط حتى لو مابيوصلش للأعلى
        if (window.innerHeight + window.scrollY >= document.body.scrollHeight - 40) {
            found = items[items.length - 1].link;
        }

        return found;
    }

    var ticking = false;

    function onScroll() {
        if (clicked || ticking) return;

        ticking = true;
        window.requestAnimationFrame(function () {
            activate(currentSection());
            ticking = false;
        });
    }

    window.addEventListener('scroll', onScroll, { passive: true });
    window.addEventListener('resize', onScroll);

    activate(currentSection());
});
</script>
@endpush
