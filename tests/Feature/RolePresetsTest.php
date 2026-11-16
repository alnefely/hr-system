<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Role;
use App\Services\Role\RolePresets;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * الأدوار الجاهزة حسب التخصص.
 */
class RolePresetsTest extends TestCase
{
    use RefreshDatabase;

    private function permissions(string $key): array
    {
        return RolePresets::all()[$key]['permissions'];
    }

    /** أي صلاحية مكتوبة غلط في دور جاهز = زرار مش هيظهر لصاحبه أبداً. */
    public function test_every_preset_permission_exists(): void
    {
        $known = all_roles();

        foreach (RolePresets::all() as $key => $preset) {
            $unknown = array_diff($preset['permissions'], $known);

            $this->assertSame([], array_values($unknown), "الدور {$key} فيه صلاحيات مش معرّفة");
        }
    }

    public function test_seeder_creates_presets_without_duplicates(): void
    {
        $this->seed(\Database\Seeders\RoleSeeder::class);
        $this->seed(\Database\Seeders\RoleSeeder::class);

        foreach (RolePresets::all() as $preset) {
            $this->assertSame(1, Role::where('name', $preset['name'])->count(), $preset['name']);
        }

        // الصلاحية الاحتياطية لازم تفضل رقم 2 — الكود بينقل لها المشرفين
        $this->assertSame('صلاحية احتياطية', Role::find(2)?->name);
    }

    /**
     * فصل المهام في المسير: مفيش دور جاهز يقدر يصدر ويعتمد ويصرف لوحده.
     */
    public function test_payroll_duties_are_separated(): void
    {
        foreach (RolePresets::all() as $key => $preset) {
            $p = $preset['permissions'];
            $all = in_array('payroll_create', $p) && in_array('payroll_approve', $p) && in_array('payroll_pay', $p);

            if ($key === 'general_manager') {
                continue; // المدير العام مسؤول عن الكل بحكم منصبه
            }

            $this->assertFalse($all, "الدور {$key} يقدر يصدر ويعتمد ويصرف المسير لوحده");
        }

        $this->assertContains('payroll_create', $this->permissions('hr_manager'));
        $this->assertNotContains('payroll_approve', $this->permissions('hr_manager'));
        $this->assertNotContains('payroll_pay', $this->permissions('hr_manager'));

        $this->assertContains('payroll_pay', $this->permissions('accountant'));
        $this->assertNotContains('payroll_create', $this->permissions('accountant'));
        $this->assertNotContains('payroll_approve', $this->permissions('accountant'));
    }

    public function test_presets_never_include_system_administration_or_force_delete(): void
    {
        foreach (RolePresets::all() as $key => $preset) {
            foreach ($preset['permissions'] as $permission) {
                $this->assertFalse(str_ends_with($permission, '_force_delete'), "{$key}: {$permission}");
                $this->assertNotContains($permission, [
                    'admin_create', 'admin_update', 'admin_delete',
                    'role_create', 'role_update', 'role_delete',
                    'setting_update', 'setting_clear_cache',
                ], "{$key}: {$permission}");
            }
        }
    }

    public function test_auditor_is_read_only(): void
    {
        $writes = ['_create', '_update', '_delete', '_approve', '_reject', '_pay', '_restore', '_trash', '_manage', '_backdate'];

        foreach ($this->permissions('auditor') as $permission) {
            if ($permission === 'admin_profile_update') {
                continue; // تعديل ملفه الشخصي بس
            }

            foreach ($writes as $suffix) {
                $this->assertFalse(str_ends_with($permission, $suffix), "المراجع عنده صلاحية كتابة: {$permission}");
            }
        }
    }

    /** الدور بيشتغل فعلاً على المسارات: مسؤول العهدة يسجّل مصروف بس مايحددش المخصص. */
    public function test_petty_cash_officer_routes(): void
    {
        $this->seed(\Database\Seeders\RoleSeeder::class);

        $this->actingAs(Admin::create([
            'name' => 'Cashier', 'email' => 'cash@test.com', 'password' => bcrypt('password'),
            'main' => false, 'role_id' => Role::where('name', 'مسؤول العهدة')->value('id'),
        ]), 'admin');

        $this->get(route('admin.expenses.index'))->assertOk();
        $this->get(route('admin.expenses.create'))->assertOk();

        $this->post(route('admin.expenses.update-balance'), [])->assertRedirect(route('admin.error_403'));
        $this->get(route('admin.payrolls.index'))->assertRedirect(route('admin.error_403'));
        $this->get(route('admin.employees'))->assertRedirect(route('admin.error_403'));
    }
}
