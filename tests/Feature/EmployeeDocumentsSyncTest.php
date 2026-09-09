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
 * مزامنة مستندات الموظف عند التعديل.
 *
 * شاشة التعديل بتعرض المستندات المحفوظة وبتبعتها تاني مع الحفظ،
 * والخدمة كانت بتعمل create() دايماً — فكل حفظ كان بيضيف نسخة
 * جديدة وتتكرر المستندات.
 */
class EmployeeDocumentsSyncTest extends TestCase
{
    use RefreshDatabase;

    protected Admin $admin;
    protected Branch $branch;
    protected JobTitle $job;
    protected Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::create(['name' => 'Full', 'roles' => all_roles()]);

        $this->admin = Admin::create([
            'name' => 'Root', 'email' => 'root@docs.test',
            'password' => bcrypt('x'), 'main' => true, 'role_id' => $role->id,
        ]);

        $this->branch = Branch::create([
            'name' => 'HQ', 'code' => 'DOC', 'country' => 'Egypt',
            'currency' => 'EGP', 'is_active' => true,
        ]);

        $this->job = JobTitle::create(['name' => 'Staff', 'is_active' => true]);

        $this->employee = Employee::create([
            'branch_id' => $this->branch->id, 'job_title_id' => $this->job->id,
            'name' => 'موظف', 'employee_code' => 'DOC-1', 'phone' => '0100',
            'status' => 'active', 'basic_salary' => 3000,
            'hire_date' => now()->toDateString(),
        ]);

        $this->actingAs($this->admin, 'admin');
    }

    /** بيانات الموظف الأساسية المطلوبة مع كل حفظ. */
    private function baseData(array $extra = []): array
    {
        return array_merge([
            'name'          => $this->employee->name,
            'employee_code' => $this->employee->employee_code,
            'phone'         => '0100',
            'branch_id'     => $this->branch->id,
            'job_title_id'  => $this->job->id,
            'status'        => 'active',
            'salary'        => 3000,
            'hired_at'      => now()->toDateString(),
        ], $extra);
    }

    private function saveWith(array $documents): void
    {
        $this->put(
            route('admin.employees.update', $this->employee->id),
            $this->baseData(['documents' => $documents])
        );
    }

    /** الحفظ المتكرر مابيكررش المستندات. */
    public function test_repeated_saves_do_not_duplicate_documents(): void
    {
        $doc = $this->employee->documents()->create([
            'document_type' => 'national_id',
            'title'         => 'بطاقة',
            'file_path'     => '/files/id.pdf',
        ]);

        // نفس اللي الفورم بيبعته: المستند المحفوظ ومعاه الـ id
        for ($i = 0; $i < 3; $i++) {
            $this->saveWith([[
                'id'            => $doc->id,
                'document_type' => 'national_id',
                'title'         => 'بطاقة',
                'file_path'     => '/files/id.pdf',
            ]]);
        }

        $this->assertEquals(
            1,
            $this->employee->documents()->count(),
            'المستندات اتكررت مع الحفظ المتكرر'
        );
    }

    /** التعديل على مستند موجود بيتحدّث مش بيتضاف. */
    public function test_editing_a_document_updates_it_in_place(): void
    {
        $doc = $this->employee->documents()->create([
            'document_type' => 'national_id',
            'title'         => 'الاسم القديم',
            'file_path'     => '/files/id.pdf',
        ]);

        $this->saveWith([[
            'id'            => $doc->id,
            'document_type' => 'contract',
            'title'         => 'الاسم الجديد',
            'file_path'     => '/files/new.pdf',
        ]]);

        $fresh = $doc->fresh();

        $this->assertEquals(1, $this->employee->documents()->count());
        $this->assertEquals('الاسم الجديد', $fresh->title);
        $this->assertEquals('contract', $fresh->document_type);
        $this->assertEquals('/files/new.pdf', $fresh->file_path);
    }

    /** المستند الجديد بيتضاف عادي. */
    public function test_a_new_document_is_added(): void
    {
        $doc = $this->employee->documents()->create([
            'document_type' => 'national_id',
            'title'         => 'بطاقة',
            'file_path'     => '/files/id.pdf',
        ]);

        $this->saveWith([
            ['id' => $doc->id, 'document_type' => 'national_id', 'title' => 'بطاقة', 'file_path' => '/files/id.pdf'],
            ['document_type' => 'contract', 'title' => 'عقد', 'file_path' => '/files/c.pdf'],
        ]);

        $this->assertEquals(2, $this->employee->documents()->count());
    }

    /** واللي المستخدم شاله من الفورم بيتحذف. */
    public function test_a_removed_document_is_deleted(): void
    {
        $keep = $this->employee->documents()->create([
            'document_type' => 'national_id', 'title' => 'بطاقة', 'file_path' => '/files/id.pdf',
        ]);

        $this->employee->documents()->create([
            'document_type' => 'contract', 'title' => 'عقد', 'file_path' => '/files/c.pdf',
        ]);

        $this->saveWith([[
            'id' => $keep->id, 'document_type' => 'national_id', 'title' => 'بطاقة', 'file_path' => '/files/id.pdf',
        ]]);

        $this->assertEquals(1, $this->employee->documents()->count());
        $this->assertEquals($keep->id, $this->employee->documents()->first()->id);
    }

    /**
     * مستند موظف تاني مايتعدلش من هنا.
     *
     * من غير التحقق ده حد يبعت id بتاع موظف تاني ويعدّل مستنداته.
     */
    public function test_cannot_touch_another_employees_document(): void
    {
        $other = Employee::create([
            'branch_id' => $this->branch->id, 'job_title_id' => $this->job->id,
            'name' => 'تاني', 'employee_code' => 'DOC-2', 'phone' => '0101',
            'status' => 'active', 'basic_salary' => 3000,
            'hire_date' => now()->toDateString(),
        ]);

        $foreign = $other->documents()->create([
            'document_type' => 'national_id', 'title' => 'بطاقة الغير', 'file_path' => '/files/x.pdf',
        ]);

        $this->saveWith([[
            'id'            => $foreign->id,
            'document_type' => 'contract',
            'title'         => 'محاولة تعديل',
            'file_path'     => '/files/hack.pdf',
        ]])
        ;

        $this->assertEquals('بطاقة الغير', $foreign->fresh()->title, 'اتعدّل مستند موظف تاني');
    }

    /** الفورم بيبعت الـ id مع كل مستند محفوظ. */
    public function test_edit_form_carries_the_document_id(): void
    {
        $doc = $this->employee->documents()->create([
            'document_type' => 'national_id', 'title' => 'بطاقة', 'file_path' => '/files/id.pdf',
        ]);

        $html = $this->get(route('admin.employees.edit', $this->employee->id))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString(
            'name="documents[0][id]"',
            $html,
            'الفورم مش بيبعت id المستند — الحفظ هيكرر'
        );

        $this->assertStringContainsString('value="' . $doc->id . '"', $html);
    }
}
