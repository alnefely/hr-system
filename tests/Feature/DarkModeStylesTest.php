<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * ألوان الوضع الداكن.
 *
 * select2 كانت خلفيتها بيضا في الوضع الداكن لأن الـ CSS كان
 * بيستخدم var(--bs-card-bg) — والمتغيّر ده معرّف جوه .card بس،
 * والقائمة المنسدلة بتتعلّق على <body> برّه أي .card، فالقيمة
 * الاحتياطية البيضا هي اللي كانت بتشتغل.
 */
class DarkModeStylesTest extends TestCase
{
    private function css(): string
    {
        $path = public_path('admin/custom.css');

        $this->assertFileExists($path, 'ملف الأنماط مش موجود');

        return file_get_contents($path);
    }

    /** مفيش اعتماد على متغيّر مش معرّف على مستوى الصفحة. */
    public function test_no_reliance_on_card_scoped_variables(): void
    {
        $this->assertStringNotContainsString(
            '--bs-card-bg',
            $this->css(),
            'الأنماط لسه بتعتمد على --bs-card-bg، وهو معرّف جوه .card بس'
        );
    }

    /** قوائم select2 ليها ألوان صريحة في الوضع الداكن. */
    public function test_select2_has_dark_mode_rules(): void
    {
        $css = $this->css();

        foreach ([
            '.select2-dropdown',
            '.select2-selection--single',
            '.select2-results__option',
            '.select2-search__field',
        ] as $selector) {
            $this->assertMatchesRegularExpression(
                '/\[data-bs-theme="dark"\][^{]*' . preg_quote($selector, '/') . '/',
                $css,
                "مفيش قاعدة للوضع الداكن على {$selector}"
            );
        }
    }

    /** وتلميحات الرسوم كمان — المكتبة بتحقن خلفية بيضا ثابتة. */
    public function test_chart_tooltips_have_dark_mode_rules(): void
    {
        $css = $this->css();

        foreach (['.apexcharts-tooltip', '.apexcharts-tooltip-title'] as $selector) {
            $this->assertMatchesRegularExpression(
                '/\[data-bs-theme="dark"\][^{]*' . preg_quote($selector, '/') . '/',
                $css,
                "مفيش قاعدة للوضع الداكن على {$selector}"
            );
        }
    }

    /**
     * مفيش خلفية بيضا ثابتة في شاشات اللوحة.
     *
     * صفحات الطباعة مستثناة — الأبيض مقصود فيها.
     */
    public function test_no_hardcoded_white_backgrounds_in_admin_views(): void
    {
        $offenders = [];

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(resource_path('views/admin'))
        );

        foreach ($files as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $path = $file->getPathname();

            // صفحات الطباعة والبانرات الملوّنة مستثناة
            if (str_contains($path, 'payslip') || str_contains($path, 'guide')) {
                continue;
            }

            $content = file_get_contents($path);

            if (preg_match('/class="[^"]*\bbg-white\b/', $content)) {
                $offenders[] = str_replace(resource_path('views/'), '', $path);
            }
        }

        $this->assertSame(
            [],
            $offenders,
            "شاشات فيها bg-white ثابت — هتفضل بيضا في الوضع الداكن:\n" . implode("\n", $offenders)
        );
    }
}
