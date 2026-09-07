@extends('layouts.admin.app')
@section('title', 'دليل الاستخدام والتشغيل التفصيلي للمنظومة المالية والإدارية')

@section('content')
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
                    <a class="nav-link py-2 px-3" href="#sec-dashboard"><i class="fa-solid fa-gauge-high me-2"></i> لوحة التحكم والمؤشرات</a>
                    <a class="nav-link py-2 px-3" href="#sec-formula"><i class="fa-solid fa-calculator me-2"></i> 1. معادلة صافي الراتب والشرح بالعربي</a>
                    <a class="nav-link py-2 px-3" href="#sec-example"><i class="fa-solid fa-receipt me-2"></i> 2. مثال عملي رقمي خطوة بخطوة</a>
                    <a class="nav-link py-2 px-3" href="#sec-permissions"><i class="fa-solid fa-clock me-2"></i> 3. الأذونات والاستئذان وحساب الساعات</a>
                    <a class="nav-link py-2 px-3" href="#sec-leaves"><i class="fa-solid fa-calendar-check me-2"></i> 4. الإجازات، الأرصدة، وسلة المحذوفات</a>
                    <a class="nav-link py-2 px-3" href="#sec-advances"><i class="fa-solid fa-hand-holding-dollar me-2"></i> 5. السلف والقروض والإسقاط الإداري</a>
                    <a class="nav-link py-2 px-3" href="#sec-rewards-allowances"><i class="fa-solid fa-award me-2"></i> 6. البدلات الثابتة vs المكافآت الاستثنائية</a>
                    <a class="nav-link py-2 px-3" href="#sec-approval-cycle"><i class="fa-solid fa-user-check me-2"></i> دورة اعتماد الجزاءات والمكافآت</a>
                    <a class="nav-link py-2 px-3" href="#sec-penalties"><i class="fa-solid fa-gavel me-2"></i> 7. الجزاءات وحساب سعر يوم العمل</a>
                    <a class="nav-link py-2 px-3" href="#sec-payroll-lifecycle"><i class="fa-solid fa-money-check-dollar me-2"></i> 8. مسير الرواتب ودورة الاعتماد والصرف</a>
                    <a class="nav-link py-2 px-3" href="#sec-sales-channels-customers"><i class="fa-solid fa-cart-shopping me-2"></i> 9. المبيعات، الفواتير، وقنوات البيع</a>
                    <a class="nav-link py-2 px-3" href="#sec-customer-sources"><i class="fa-solid fa-share-nodes me-2"></i> مصادر العملاء <span class="badge bg-label-success ms-1">جديد</span></a>
                    <a class="nav-link py-2 px-3" href="#sec-vendors-subscriptions"><i class="fa-solid fa-repeat me-2"></i> 10. الموردين والاشتراكات الدورية</a>
                    <a class="nav-link py-2 px-3" href="#sec-expenses-petty-cash"><i class="fa-solid fa-wallet me-2"></i> 11. العهد النقدية وتعدد العملات</a>

                    <hr class="my-2">
                    <div class="px-3 pb-1"><small class="text-muted fw-bold">الحوكمة والأمان</small></div>

                    <a class="nav-link py-2 px-3" href="#sec-table-filters"><i class="fa-solid fa-filter me-2"></i> البحث والفلترة في الجداول</a>
                    <a class="nav-link py-2 px-3" href="#sec-activity-log"><i class="fa-solid fa-shield-halved me-2"></i> 12. سجل حركات النظام (Audit Log)</a>
                    <a class="nav-link py-2 px-3" href="#sec-branch-isolation"><i class="fa-solid fa-building-lock me-2"></i> 13. عزل البيانات بين الفروع</a>
                    <a class="nav-link py-2 px-3" href="#sec-auth-security"><i class="fa-solid fa-key me-2"></i> 14. الدخول واستعادة كلمة المرور</a>
                    <a class="nav-link py-2 px-3" href="#sec-notifications"><i class="fa-solid fa-bell me-2"></i> 15. الإشعارات والتنبيهات اللحظية</a>
                    <a class="nav-link py-2 px-3" href="#sec-maintenance"><i class="fa-solid fa-terminal me-2"></i> 16. أوامر الصيانة والتشغيل الدوري</a>
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
                                    <small class="text-muted">تشمل: خصم الأيام (عدد الأيام &times; سعر يوم العمل) بالإضافة إلى أي خصومات نقدية مباشرة معتمدة خلال الشهر.</small>
                                </div>
                                <div class="mb-3 border-bottom pb-2">
                                    <strong class="text-dark d-block">2. أقساط السلف والقروض (Advances Installments):</strong>
                                    <small class="text-muted">القسط الشهري المستحق سداده من السلفة المعتمدة، ولا يتجاوز الرصيد المتبقي الفعلي بذمة الموظف.</small>
                                </div>
                                <div class="mb-3 border-bottom pb-2">
                                    <strong class="text-dark d-block">3. خصم الإجازات بدون راتب (Unpaid Leaves):</strong>
                                    <small class="text-muted">إجمالي أيام الإجازات غير المدفوعة خلال الشهر &times; سعر يوم العمل للموظف.</small>
                                </div>
                                <div>
                                    <strong class="text-dark d-block">4. خصم ساعات الاستئذان المتجاوزة (Excess Permission Hours):</strong>
                                    <small class="text-muted">الساعات التي تتعدى الحد الشهري المسموح للموظف &times; سعر ساعة العمل الفعلية.</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="alert alert-primary border-0 small mb-0">
                    <i class="fa-solid fa-shield-check me-2"></i>
                    <strong>قاعدة حماية الصافي:</strong> يضمن النظام ألا يصبح صافي الراتب قيمة سالبة؛ فإذا كانت الاستقطاعات أكبر من الاستحقاقات، يكون الناتج <span dir="ltr">0.00</span> ويتم ترحيل المتبقي كمديونية أو معالجته إدارياً وفق لائحة العمل.
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
                                يدعم النظام كتابة وحساب الساعات بدقة (مثل 1.25 ساعة = ساعة و15 دقيقة، 0.5 ساعة = 30 دقيقة). يتم احتساب فارق الدقائق بين وقت الخروج والعودة وقسمته على 60 تلقائياً.
                            </p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-3 border rounded h-100">
                            <h6 class="fw-bold mb-2 text-warning"><i class="fa-solid fa-gauge-high me-1"></i> الرصيد الشهري وعداد التجاوز</h6>
                            <p class="small text-muted mb-0">
                                يمتلك كل موظف رصيد استئذان شهري مسموح (افتراضياً ساعتان أو 4 ساعات وفق سياسة الفرع). يوضح الفلتر العلوي فوراً مجموع الساعات المستهلكة، والمتبقي، وما إذا كان الموظف قد تجاوز الحد المسموح.
                            </p>
                        </div>
                    </div>
                </div>
                <div class="alert alert-secondary border-0 small mb-0">
                    <strong><i class="fa-solid fa-info-circle me-1"></i> الأثر في المسير:</strong>
                    إذا بلغ مجموع ساعات الاستئذان المعتمدة 5 ساعات، والحد المسموح 4 ساعات: يتم خصم ساعة واحدة متجاوزة في مسير الرواتب بحساب <span dir="ltr">1 &times; Hourly Rate</span>.
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
                                <li><strong>الإجازة السنوية:</strong> 21 يوماً سنوياً (أو 30 يوماً لمن تجاوزت خدمته 10 سنوات أو تجاوز سن الخمسين).</li>
                                <li><strong>الإجازة العارضة:</strong> 7 أيام كحد أقصى خلال العام (بحد أقصى يومين في المرة الواحدة).</li>
                                <li><strong>الإجازة المرضية:</strong> 14 يوماً وفق التقرير الطبي المعتمد.</li>
                                <li><strong>الأثر المالي:</strong> لا يخصم أي مليم من الراتب؛ فقط يُخصم عدد الأيام من رصيد الموظف التراكمي.</li>
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
                    <h6 class="alert-heading fw-bold"><i class="fa-solid fa-gear me-1"></i> ملاحظة إدارية</h6>
                    <p class="small mb-0">
                        صلاحيات الاعتماد الأربع <strong>غير ممنوحة لأي دور تلقائياً</strong>.
                        افتح شاشة الأدوار وامنحها للمسؤولين المخوّلين فقط —
                        ويُفضَّل ألا تُمنح لنفس الدور الذي يملك صلاحية التسجيل، حتى يبقى فصل المهام فعّالاً.
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
                            </small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-3 border rounded">
                            <h6 class="fw-bold text-info mb-2">معادلة سعر الساعة (Hourly Rate Formula)</h6>
                            <div class="font-monospace fw-bold text-dark fs-6" dir="ltr">Hourly Rate = Daily Rate / 8 Working Hours</div>
                            <small class="text-muted mt-1 d-block">
                                يقسم سعر اليوم على 8 ساعات عمل يومية (أو عدد الساعات المسجل للموظف) لاستخدامه في خصم ساعات الاستئذان أو حساب الإضافي.
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
                        <div class="p-3 border rounded">
                            <span class="badge bg-warning mb-2">1. مسودة (Draft)</span>
                            <p class="small text-muted mb-0">يتم توليد الكشف آلياً وسحب كافة استحقاقات وخصومات وسلف الشهر. يمكن التعديل أو الحذف.</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="p-3 border rounded">
                            <span class="badge bg-info mb-2">2. معتمد (Approved)</span>
                            <p class="small text-muted mb-0">اعتماد الكشف من المدير المالي أو صاحب العمل؛ يتم قفل التعديل تمهيداً لبدء التحويلات المالية.</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="p-3 border rounded">
                            <span class="badge bg-success mb-2">3. مدفوع (Paid)</span>
                            <p class="small text-muted mb-0">تم تحويل الرواتب للموظفين نقداً أو عبر البنك، ويتم فورياً تسجيل سداد أقساط السلف والمكافآت.</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="p-3 border rounded">
                            <span class="badge bg-danger mb-2">4. مرفوض (Rejected)</span>
                            <p class="small text-muted mb-0">في حال وجود ملاحظات جوهرية مع تسجيل سبب الرفض كتابياً للرجوع إليه.</p>
                        </div>
                    </div>
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
                    <h6 class="fw-bold text-primary mb-2"><i class="fa-solid fa-layer-group me-1"></i> الموديولات الخاضعة للتسجيل (17 موديول)</h6>
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
                                    <li>التقارير المالية محصورة بأرقام فرعه</li>
                                    <li>فلتر الفروع يعرض فرعه فقط</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <h6 class="fw-bold mb-3 text-dark"><i class="fa-solid fa-table-list me-2 text-primary"></i> الموديولات المشمولة بالعزل:</h6>
                <div class="d-flex flex-wrap gap-2 mb-4">
                    <span class="badge bg-label-primary">الموظفون</span>
                    <span class="badge bg-label-primary">فواتير المبيعات</span>
                    <span class="badge bg-label-primary">المصروفات والعهد</span>
                    <span class="badge bg-label-primary">الإجازات</span>
                    <span class="badge bg-label-primary">السلف</span>
                    <span class="badge bg-label-secondary">التقارير المالية</span>
                </div>

                <div class="alert alert-info mb-0">
                    <h6 class="alert-heading fw-bold"><i class="fa-solid fa-triangle-exclamation me-1"></i> ملاحظة إدارية مهمة</h6>
                    <p class="small mb-0">
                        لكي يعمل العزل بشكل صحيح، يجب <strong>ربط حساب كل مشرف بملف موظف</strong> له فرع محدد.
                        أما المشرف غير المرتبط بموظف، فسيرى كل البيانات — لذا يجب مراجعة حسابات المشرفين
                        والتأكد من ضبط علامة <span class="badge bg-label-primary">مدير عام</span> بدقة.
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
                                <td class="small">من له صلاحية الإجازات في نفس الفرع</td>
                            </tr>
                            <tr>
                                <td><span class="badge bg-label-success">نتيجة طلب الإجازة</span></td>
                                <td class="small">عند الاعتماد أو الرفض</td>
                                <td class="small">مقدّم الطلب</td>
                            </tr>
                            <tr>
                                <td><span class="badge bg-label-info">طلب سلفة جديد</span></td>
                                <td class="small">فور تسجيل طلب سلفة</td>
                                <td class="small">من له صلاحية السلف في نفس الفرع</td>
                            </tr>
                            <tr>
                                <td><span class="badge bg-label-success">نتيجة طلب السلفة</span></td>
                                <td class="small">عند الاعتماد أو الرفض</td>
                                <td class="small">مقدّم الطلب</td>
                            </tr>
                            <tr>
                                <td><span class="badge bg-label-info">مسير بانتظار الاعتماد</span></td>
                                <td class="small">فور إصدار مسير رواتب</td>
                                <td class="small">من له صلاحية اعتماد الرواتب</td>
                            </tr>
                            <tr>
                                <td><span class="badge bg-label-warning">اشتراك يقترب من التجديد</span></td>
                                <td class="small">يومياً — خلال 7 أيام من الاستحقاق</td>
                                <td class="small">المسؤول المالي للفرع</td>
                            </tr>
                            <tr>
                                <td><span class="badge bg-label-danger">رصيد عهدة منخفض</span></td>
                                <td class="small">يومياً — عند نزول المتبقي تحت 20%</td>
                                <td class="small">مدير الفرع</td>
                            </tr>
                            <tr>
                                <td><span class="badge bg-label-danger">فاتورة متأخرة</span></td>
                                <td class="small">يومياً — بعد 30 يوماً من تاريخ الفاتورة</td>
                                <td class="small">مسؤول المبيعات</td>
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
                            <h6 class="alert-heading fw-bold"><i class="fa-solid fa-shield-halved me-1"></i> لا يعطّل العمل</h6>
                            <p class="small mb-0">
                                فشل إرسال الإشعار لا يوقف العملية الأصلية — طلب الإجازة يُسجَّل
                                حتى لو تعذّر إرسال التنبيه.
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
                                <td class="small">ترحيل أرصدة العهدة للشهر الحالي (تلقائية أول كل شهر)</td>
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
                                <td class="small">تشغيل معالج الطوابير — مطلوب لإرسال البريد وتسجيل الحركات في الخلفية</td>
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
                        لكي يعمل التنظيف التلقائي لسجل الحركات كل ليلة، يجب إضافة هذا السطر لجدول
                        <span dir="ltr">Cron</span> على السيرفر:
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
