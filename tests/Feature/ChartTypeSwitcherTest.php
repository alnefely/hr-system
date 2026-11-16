<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\JobTitle;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * مبدّل شكل الرسم في لوحة التحكم.
 *
 * كل رسم ليه قائمة أشكال، والاختيار بيتحفظ في المتصفح فالمستخدم
 * مايعيدهوش كل مرة.
 */
class ChartTypeSwitcherTest extends TestCase
{
    use RefreshDatabase;

    private function dashboard(): string
    {
        $role = Role::create(['name' => 'Full', 'roles' => all_roles()]);

        $admin = Admin::create([
            'name' => 'Root', 'email' => 'root@chart.test',
            'password' => bcrypt('x'), 'main' => true, 'role_id' => $role->id,
        ]);

        $branch = Branch::create([
            'name' => 'HQ', 'code' => 'CHT', 'country' => 'Egypt',
            'currency' => 'EGP', 'is_active' => true,
        ]);

        // رسم الفروع بيظهر بس لما يكون فيه موظفين في أكتر من فرع. قبل كده
        // الاختبار كان معتمد على البيانات التجريبية اللي الـ migration كان بيضيفها.
        $second = Branch::create([
            'name' => 'Second', 'code' => 'CHT2', 'country' => 'Egypt',
            'currency' => 'EGP', 'is_active' => true,
        ]);
        $jobTitleId = JobTitle::create(['name' => 'Staff', 'is_active' => true])->id;

        foreach ([$branch, $second] as $i => $b) {
            Employee::create([
                'branch_id' => $b->id, 'job_title_id' => $jobTitleId,
                'name' => 'Chart Emp ' . $i, 'employee_code' => 'CHT-000' . $i,
                'phone' => '0100000000' . $i, 'status' => 'active',
                'basic_salary' => 1000, 'hire_date' => '2024-01-01',
            ]);
        }

        return $this->actingAs($admin, 'admin')
            ->get(route('admin.home'))
            ->assertOk()
            ->getContent();
    }

    /** كل رسم ليه مبدّل شكل. */
    public function test_every_chart_has_a_type_switcher(): void
    {
        $html = $this->dashboard();

        foreach (['trend', 'expenses', 'channels', 'branches'] as $target) {
            $this->assertMatchesRegularExpression(
                '/class="[^"]*chart-type-btn[^"]*"[^>]*data-target="' . $target . '"/',
                $html,
                "الرسم «{$target}» مالوش مبدّل شكل"
            );
        }
    }

    /** الأشكال المعروضة مترجمة ومش مفاتيح خام. */
    public function test_chart_type_labels_are_translated(): void
    {
        $html = $this->dashboard();

        $this->assertStringNotContainsString(
            'dashboard.chart_types.',
            $html,
            'فيه مفتاح شكل خام ظاهر'
        );

        // الأشكال الأساسية لازم تبقى متاحة
        foreach (['line', 'bar', 'column', 'donut'] as $type) {
            $this->assertStringContainsString(
                __('dashboard.chart_types.' . $type),
                $html,
                "الشكل «{$type}» مش ظاهر في القائمة"
            );
        }
    }

    /** الأشكال كلها ليها ترجمة في اللغتين. */
    public function test_chart_type_keys_exist_in_both_languages(): void
    {
        $reference = null;

        foreach (['ar', 'en'] as $locale) {
            app()->setLocale($locale);

            $types = __('dashboard.chart_types');

            $this->assertIsArray($types, "chart_types مش موجودة في {$locale}");
            $this->assertNotEmpty($types);

            if ($reference === null) {
                $reference = array_keys($types);
                continue;
            }

            $this->assertEqualsCanonicalizing($reference, array_keys($types));
        }

        app()->setLocale('ar');
    }

    /** الاختيار بيتحفظ عشان مايضيعش مع كل تحميل. */
    public function test_selected_type_is_persisted(): void
    {
        $html = $this->dashboard();

        $this->assertStringContainsString('localStorage', $html, 'الاختيار مش بيتحفظ');
        $this->assertStringContainsString('setType', $html, 'مفيش دالة لتغيير الشكل');
    }

    /**
     * الفترة المخصصة بتظهر حقولها.
     *
     * القائمة select2 بترفع الحدث عن طريق jQuery، والمستمع الأصلي
     * مكانش بيشوفه — فالحقول مكانتش بتظهر خالص.
     */
    public function test_custom_range_toggle_is_bound_through_jquery(): void
    {
        $html = $this->dashboard();

        $this->assertStringContainsString('toggleCustomRange', $html, 'مفيش دالة لإظهار حقول الفترة');
        $this->assertMatchesRegularExpression(
            '/jQuery\(preset\)\.on\(\s*[\'"]change[\'"]/',
            $html,
            'الحدث مش مربوط عن طريق jQuery — الحقول مش هتظهر'
        );
    }

    /** حقول الفترة المخصصة موجودة في الصفحة. */
    public function test_custom_range_inputs_exist(): void
    {
        $html = $this->dashboard();

        $this->assertStringContainsString('id="customRange"', $html);
        $this->assertStringContainsString('name="date_from"', $html);
        $this->assertStringContainsString('name="date_to"', $html);
    }

    /**
     * كل الرسوم ليها نفس قائمة الأشكال.
     *
     * رسم الاتجاه كان معاه 4 أشكال بس بينما الباقي 7 — فالمستخدم
     * مالوش نفس الخيارات في كل مكان.
     */
    public function test_all_charts_offer_the_same_types(): void
    {
        $html = $this->dashboard();

        $counts = [];

        foreach (['trend', 'expenses', 'channels', 'branches'] as $target) {
            preg_match_all(
                '/data-target="' . $target . '" data-type="([a-zA-Z]+)"/',
                $html,
                $m
            );

            $counts[$target] = $m[1];
        }

        $reference = $counts['trend'];

        $this->assertNotEmpty($reference, 'رسم الاتجاه مالوش أشكال');

        foreach ($counts as $target => $types) {
            $this->assertEqualsCanonicalizing(
                $reference,
                $types,
                "الرسم «{$target}» قايمة أشكاله مختلفة عن الباقي"
            );
        }
    }

    /** الشكل الدائري بخطوط التسميات متاح. */
    public function test_labeled_pie_type_is_available(): void
    {
        $html = $this->dashboard();

        $this->assertStringContainsString('data-type="pieLabeled"', $html, 'الشكل الدائري المعنون مش موجود');
        $this->assertStringContainsString(__('dashboard.chart_types.pieLabeled'), $html);
    }

    /**
     * تبديل الشكل بينضّف خيارات الشكل السابق.
     *
     * الرادار كان بايظ لأن خيارات الأعمدة (formatter و dashArray
     * و plotOptions.bar) كانت بتفضل معه بعد التبديل.
     */
    public function test_type_switch_resets_previous_options(): void
    {
        $html = $this->dashboard();

        $this->assertStringContainsString('TYPE_SPECIFIC', $html, 'مفيش تنضيف لخيارات الشكل السابق');

        // الخيارات اللي لازم تتشال قبل بناء الشكل الجديد
        foreach (['plotOptions', 'dataLabels', 'stroke', 'xaxis', 'yaxis'] as $key) {
            $this->assertMatchesRegularExpression(
                "/TYPE_SPECIFIC[^;]*'" . $key . "'/s",
                $html,
                "الخيار «{$key}» مش بيتنضّف عند تبديل الشكل"
            );
        }
    }

    /**
     * الرادار بيشيل السلاسل الفاضية وبيسيب المحور شغّال.
     *
     * كان بينهار على نقطة في النص: كل عملة ليها تسميات مختلفة
     * فالتبطين بأصفار بيخلي سلاسل كاملة أصفار، و yaxis:{show:false}
     * كان بيمنع ApexCharts من حساب نصف قطر الشبكة.
     */
    public function test_radar_drops_empty_series_and_keeps_an_axis(): void
    {
        $html = $this->dashboard();

        // بيفلتر السلاسل اللي كلها أصفار
        $this->assertStringContainsString(
            'meaningful',
            $html,
            'الرادار مش بيشيل السلاسل الفاضية'
        );

        // والمحور متعرّف مش متشال
        $this->assertMatchesRegularExpression(
            '/out\.yaxis\s*=\s*\{\s*show:\s*false,\s*labels:/',
            $html,
            'محور الرادار متشال — الشبكة هتنهار على نقطة'
        );

        // وشبكة الرادار متظبّطة
        $this->assertStringContainsString('polygons', $html, 'شبكة الرادار مش متظبّطة');
    }

    /**
     * البادج بيقول العملات المعروضة فعلاً مش "كل العملات" دايماً.
     *
     * الأشكال الدائرية وخريطة المساحات والرادار بتعرض عملة واحدة
     * بس — فالبادج كان بيكذب لما يقول "كل العملات".
     */
    public function test_currency_badge_reflects_the_chart_type(): void
    {
        $html = $this->dashboard();

        // الدالة اللي بتحدد الوصف موجودة
        $this->assertStringContainsString('currencyLabel', $html, 'مفيش دالة لوصف العملات');
        $this->assertStringContainsString('isSingleCurrency', $html, 'مفيش تفرقة بين الأشكال');

        // وكل رسم ليه بادج بمعرّف عشان يتحدّث
        foreach (['trendCurrency', 'expenseCurrency', 'channelsCurrency'] as $id) {
            $this->assertStringContainsString(
                'id="' . $id . '"',
                $html,
                "البادج «{$id}» مالوش معرّف — مش هيتحدّث مع الشكل"
            );
        }
    }

    /** نص "من كذا عملة" مترجم في اللغتين. */
    public function test_currency_count_hint_is_translated(): void
    {
        foreach (['ar', 'en'] as $locale) {
            app()->setLocale($locale);

            $text = __('dashboard.of_currencies');

            $this->assertNotEquals('dashboard.of_currencies', $text, "مش مترجم في {$locale}");
            $this->assertStringContainsString(':count', $text, 'العدد مش في النص');
        }

        app()->setLocale('ar');
    }

    /**
     * كل رقم في الرسوم معاه عملته.
     *
     * الدونات كان بيعرض 2700 في النص من غير عملة ولا فاصلة،
     * والتلميحات كمان — فالمستخدم مش عارف جنيه ولا ريال.
     */
    public function test_figures_carry_their_currency(): void
    {
        $html = $this->dashboard();

        // دالة موحّدة بتحط العملة جنب الرقم
        $this->assertStringContainsString(
            'var money = function',
            $html,
            'مفيش دالة موحّدة لتنسيق المبالغ'
        );

        // الإجمالي في الدونات بيتحسب من كل الشرايح
        $this->assertStringContainsString(
            'seriesTotals',
            $html,
            'إجمالي الدونات مش بيتحسب من كل الشرايح'
        );

        // والتلميح بياخد العملة من اسم السلسلة في الأشكال المتعددة
        $this->assertStringContainsString(
            'seriesNames',
            $html,
            'التلميح مش بياخد العملة من اسم السلسلة'
        );
    }

    /**
     * الأشكال الدائرية بتعرض كل عملة برقمها.
     *
     * كانت بتعرض أول عملة بس، فقسم فيه مصروف بالجنيه والريال كان
     * بيبان برقم واحد — والمستخدم مش عارف ده جنيه ولا ريال.
     */
    public function test_circular_charts_break_down_by_currency(): void
    {
        $html = $this->dashboard();

        // كل عملة بتتحفظ لوحدها بدل ما تتجمع في رقم واحد
        $this->assertStringContainsString(
            'breakdown',
            $html,
            'الأشكال الدائرية مش بتفصّل بالعملة'
        );

        $this->assertStringContainsString(
            'currencyLines',
            $html,
            'مفيش دالة بتبني سطر لكل عملة'
        );

        // والدائرية مابقتش ضمن اللي بتعرض عملة واحدة
        $this->assertMatchesRegularExpression(
            "/isSingleCurrency[^}]*treemap[^}]*radar/s",
            $html,
            'الدائرية لسه محسوبة كعملة واحدة'
        );
    }

    /**
     * ألوان الرسوم بتتغيّر مع الوضع الليلي والنهاري.
     *
     * التسميات كانت بلون النص الثانوي الباهت — صعبة القراءة في
     * الوضعين، خصوصاً فوق الشرايح الملوّنة.
     */
    public function test_chart_colors_adapt_to_the_theme(): void
    {
        $html = $this->dashboard();

        // بيقرا الوضع الحالي
        $this->assertStringContainsString(
            "getAttribute('data-bs-theme')",
            $html,
            'الرسوم مش بتقرا الوضع الحالي'
        );

        // لون نص أساسي للقراءة + لون فوق الشرايح
        foreach (['textStrong', 'textOnFill', 'surfaceColor'] as $variable) {
            $this->assertStringContainsString(
                'var ' . $variable,
                $html,
                "المتغيّر «{$variable}» مش معرّف"
            );
        }

        // وقيم احتياطية للوضعين لو المتغيّرات مش متاحة
        $this->assertMatchesRegularExpression(
            '/isDark \?[^:]*:[^;]*;/',
            $html,
            'مفيش قيم احتياطية للوضعين'
        );
    }
}
