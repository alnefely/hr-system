<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Branch;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * مصدر واحد لكل قائمة قيم محددة.
 *
 * كان في مفاتيح ترجمة مكرّرة لنفس العمود بقيم مختلفة تماماً:
 *   penalty.types      = financial, warning, notice, suspension  (غلط)
 *   penalty.type_enums = days_deduction, amount_deduction, warning (الصح)
 *
 * فالفلتر فوق كان بيعرض كلام والجدول بيعرض كلام تاني. الاختبار ده
 * بيقفل الباب ده: مفاتيح الترجمة لازم تطابق enum الجدول بالظبط.
 */
class EnumSourceConsistencyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * العمود في قاعدة البيانات ← مفتاح الترجمة المعتمد.
     *
     * @return array<string, array{0: string, 1: string, 2: string}>
     */
    public static function enumColumns(): array
    {
        return [
            'penalty type'      => ['employee_penalties', 'penalty_type', 'penalty.type_enums'],
            'penalty status'    => ['employee_penalties', 'status', 'penalty.status_enums'],
            'advance status'    => ['employee_advances', 'status', 'advance.status_enums'],
            'reward type'       => ['employee_rewards', 'reward_type', 'reward.type_enums'],
            'reward status'     => ['employee_rewards', 'status', 'reward.status_enums'],
            'leave type'        => ['employee_leaves', 'leave_type', 'leave.type_enums'],
            'leave status'      => ['employee_leaves', 'status', 'leave.status_enums'],
            'permission status' => ['employee_permissions', 'status', 'permission.status_enums'],
            'sale status'       => ['sales', 'payment_status', 'sale.statuses'],
            'subscription'      => ['vendor_subscriptions', 'status', 'vendor_subscription.statuses'],
            'project status'    => ['projects', 'status', 'project.status_enums'],
            'project member'    => ['project_members', 'role', 'project.member_role_enums'],
            'task status'       => ['tasks', 'status', 'task.status_enums'],
            'task priority'     => ['tasks', 'priority', 'task.priority_enums'],
            'task progress src' => ['tasks', 'progress_source', 'task.progress_source_enums'],
            'task progress int' => ['tasks', 'progress_update_interval', 'task.progress_interval_enums'],
            'task assignee'     => ['task_assignees', 'status', 'task.assignee_status_enums'],
            'task review'       => ['task_reviews', 'status', 'task.review_status_enums'],
            'task frequency'    => ['task_recurrences', 'frequency', 'task.frequency_enums'],
            'task reminder'     => ['task_reminders', 'type', 'task.reminder_type_enums'],
        ];
    }

    /**
     * قيم enum زي ما هي معرّفة في الميجريشن.
     *
     * SQLite (قاعدة الاختبارات) مابتحفظش تعريف enum، فقراءتها من
     * الـ schema بترجع فاضية. الميجريشن هي المصدر الأصلي وبتشتغل
     * في أي قاعدة.
     */
    private function schemaValues(string $table, string $column): array
    {
        foreach (glob(database_path('migrations/*.php')) as $file) {
            $src = file_get_contents($file);

            if (! str_contains($src, "Schema::create('{$table}'")) {
                continue;
            }

            $pattern = "/enum\(\s*'" . preg_quote($column, '/') . "'\s*,\s*\[(.*?)\]/s";

            if (preg_match($pattern, $src, $m) && preg_match_all("/'([^']+)'/", $m[1], $v)) {
                return $v[1];
            }
        }

        return [];
    }

    /**
     * كل مفتاح ترجمة معتمد بيغطي قيم الجدول كلها.
     *
     */
    #[DataProvider('enumColumns')]
    public function test_translation_covers_every_schema_value(string $table, string $column, string $key): void
    {
        $schema = $this->schemaValues($table, $column);

        if (! $schema) {
            $this->markTestSkipped("العمود {$table}.{$column} مش enum في قاعدة الاختبار");
        }

        $translated = __($key);

        $this->assertIsArray($translated, "{$key} مش موجود");

        $missing = array_diff($schema, array_keys($translated));

        $this->assertSame(
            [],
            array_values($missing),
            "{$key}: قيم موجودة في الجدول ومالهاش ترجمة: " . implode(', ', $missing)
        );
    }

    /**
     * ومفيش مفتاح زيادة مش موجود في الجدول — ده معناه قائمة
     * بتعرض خيار المستخدم يختاره وبعدين الحفظ يرفضه.
     *
     */
    #[DataProvider('enumColumns')]
    public function test_translation_has_no_values_outside_the_schema(string $table, string $column, string $key): void
    {
        $schema = $this->schemaValues($table, $column);

        if (! $schema) {
            $this->markTestSkipped("العمود {$table}.{$column} مش enum في قاعدة الاختبار");
        }

        $extra = array_diff(array_keys(__($key)), $schema);

        $this->assertSame(
            [],
            array_values($extra),
            "{$key}: خيارات مش مقبولة في الجدول: " . implode(', ', $extra)
        );
    }

    /** المفاتيح المكرّرة اللي اتشالت ماترجعش تاني. */
    public function test_duplicate_enum_keys_are_gone(): void
    {
        foreach ([
            'penalty.types',
            'reward.types',
            'vendor_subscription.status_enums',
            'sale.status_enums',
        ] as $removed) {
            $this->assertFalse(
                is_array(__($removed)),
                "{$removed}: المفتاح المكرّر رجع تاني — لازم مصدر واحد بس"
            );
        }
    }

    /** الفلتر فوق والجدول بياخدوا من نفس المصدر. */
    public function test_advances_filter_matches_the_table(): void
    {
        $role = Role::create(['name' => 'Full', 'roles' => all_roles()]);

        $admin = Admin::create([
            'name' => 'Root', 'email' => 'root@enum.test',
            'password' => bcrypt('x'), 'main' => true, 'role_id' => $role->id,
        ]);

        Branch::create([
            'name' => 'HQ', 'code' => 'ENM', 'country' => 'Egypt',
            'currency' => 'EGP', 'is_active' => true,
        ]);

        $html = $this->actingAs($admin, 'admin')
            ->get(route('admin.advances.index'))
            ->assertOk()
            ->getContent();

        // كل تسمية حالة لازم تظهر مرتين على الأقل: فلتر الصفحة والجدول
        foreach (__('advance.status_enums') as $label) {
            $this->assertGreaterThanOrEqual(
                2,
                substr_count($html, $label),
                "التسمية «{$label}» مش ظاهرة في الفلتر والجدول مع بعض"
            );
        }
    }
}
