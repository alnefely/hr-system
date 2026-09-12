<?php

namespace Tests\Feature;

use App\Models\Link;
use App\Services\Links\ShortCodeGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShortCodeGeneratorTest extends TestCase
{
    use RefreshDatabase;

    private function generator(): ShortCodeGenerator
    {
        return app(ShortCodeGenerator::class);
    }

    public function test_generates_unique_codes(): void
    {
        $codes = [];

        for ($i = 0; $i < 200; $i++) {
            $codes[] = $this->generator()->generate();
        }

        $this->assertCount(200, array_unique($codes), 'اتولّد كود مكرر');
    }

    public function test_avoids_ambiguous_characters(): void
    {
        for ($i = 0; $i < 50; $i++) {
            $code = $this->generator()->generate();

            foreach (['0', 'O', '1', 'l', 'I'] as $bad) {
                $this->assertStringNotContainsString($bad, $code, 'الكود فيه حرف ملخبط');
            }
        }
    }

    /**
     * التفرّد لازم يتفحص case-sensitive.
     *
     * لو المقارنة case-insensitive بنفقد ثلثين الـ keyspace
     * وبتحصل تصادمات وهمية.
     */
    public function test_uniqueness_is_case_sensitive(): void
    {
        Link::create([
            'short_code'   => 'aB7xK9m',
            'original_url' => 'https://example.com',
            'is_active'    => true,
        ]);

        $this->assertFalse($this->generator()->isAvailable('aB7xK9m'), 'الكود المستخدم اتقال عليه متاح');
        $this->assertTrue($this->generator()->isAvailable('Ab7Xk9M'), 'كود مختلف في حالة الأحرف اتقال عليه مستخدم');
    }

    /** الأكواد المحجوزة بتبلع راوتات النظام. */
    public function test_reserved_codes_are_rejected(): void
    {
        foreach (['admin', 'ADMIN', 'filemanager', 'login', 's', 'api'] as $reserved) {
            $this->assertFalse(
                $this->generator()->isAvailable($reserved),
                "الكود المحجوز {$reserved} اتقال عليه متاح"
            );
        }
    }

    /** الكود المحذوف يفضل محجوز — وإلا رابط قديم يفتح وجهة جديدة. */
    public function test_trashed_codes_stay_reserved(): void
    {
        $link = Link::create([
            'short_code'   => 'Del1234',
            'original_url' => 'https://example.com',
            'is_active'    => true,
        ]);

        $link->delete();

        $this->assertFalse($this->generator()->isAvailable('Del1234'), 'كود محذوف اتقال عليه متاح');
    }

    /**
     * الميجريشن لازم يفرض utf8mb4_bin.
     *
     * اختبار التفرّد فوق بيعدّي على SQLite حتى لو الـ collation اتشال،
     * لأن SQLite case-sensitive افتراضياً. الإنتاج MySQL والافتراضي
     * فيها case-insensitive — فبنتحقق من الميجريشن نفسه.
     */
    public function test_migration_enforces_binary_collation(): void
    {
        $path = database_path('migrations/2026_09_12_100001_create_links_table.php');

        $this->assertFileExists($path);

        $contents = file_get_contents($path);

        $this->assertStringContainsString(
            'utf8mb4_bin',
            $contents,
            'الميجريشن مابيفرضش utf8mb4_bin — الأكواد هتتصادم في MySQL'
        );

        $this->assertStringContainsString(
            'links_short_code_unique',
            $contents,
            'مافيش unique index على الكود'
        );
    }

    public function test_custom_code_validation(): void
    {
        $max = ShortCodeGenerator::maxLength();

        $this->assertNotEmpty($this->generator()->validateCustom('ab'), 'كود قصير اتقبل');
        $this->assertNotEmpty($this->generator()->validateCustom('has space'), 'كود بمسافة اتقبل');
        $this->assertNotEmpty($this->generator()->validateCustom('admin'), 'كود محجوز اتقبل');

        // أطول من المسموح
        $this->assertNotEmpty(
            $this->generator()->validateCustom(str_repeat('a', $max + 1)),
            'كود أطول من الحد اتقبل'
        );

        // وكود سليم بالطول المسموح — مشتق من الإعداد مش قيمة مثبتة
        $this->assertEmpty(
            $this->generator()->validateCustom(substr('valid-code_1', 0, $max)),
            'كود سليم اترفض'
        );
    }
}
