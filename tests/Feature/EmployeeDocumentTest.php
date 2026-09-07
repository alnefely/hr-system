<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\JobTitle;
use App\Models\Role;
use App\Services\HR\EmployeeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * مستندات الموظف: توحيد الطريقتين.
 *
 * كان في اختلاف بين مسارين لرفع نفس المستند:
 *  - شاشة تعديل الموظف: بتطلب الاسم والملف بس، والخدمة بتحط
 *    document_type ثابت ('initial_attachment') وهي قيمة مالهاش
 *    ترجمة فبتظهر كمفتاح خام في جدول البروفايل.
 *  - شاشة البروفايل: بتطلب النوع والتواريخ والتحقق.
 */
class EmployeeDocumentTest extends TestCase
{
    use RefreshDatabase;

    protected Admin $admin;
    protected Branch $branch;
    protected JobTitle $jobTitle;
    protected Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::create(['name' => 'Full', 'roles' => all_roles()]);

        $this->admin = Admin::create([
            'name' => 'Root', 'email' => 'root@doc.test',
            'password' => bcrypt('x'), 'main' => true, 'role_id' => $role->id,
        ]);

        $this->branch = Branch::create([
            'name' => 'HQ', 'code' => 'DOC', 'country' => 'Egypt',
            'currency' => 'EGP', 'is_active' => true,
        ]);

        $this->jobTitle = JobTitle::create(['name' => 'Staff', 'is_active' => true]);

        $this->employee = Employee::create([
            'branch_id' => $this->branch->id, 'job_title_id' => $this->jobTitle->id,
            'name' => 'Doc Owner', 'employee_code' => 'DOC-1', 'phone' => '0100',
            'status' => 'active', 'basic_salary' => 3000, 'hire_date' => '2024-01-01',
        ]);

        $this->actingAs($this->admin, 'admin');
    }

    /** كل نوع مستند مخزّن لازم يكون له ترجمة. */
    public function test_every_stored_document_type_has_a_translation(): void
    {
        $types = array_keys(__('document.types'));

        foreach ($types as $type) {
            $key = 'document.types.' . $type;

            $this->assertNotEquals(
                $key,
                __($key),
                "النوع [{$type}] مالوش ترجمة"
            );
        }

        // الأنواع الثابتة القديمة اتشالت من الكود
        $service = file_get_contents(app_path('Services/HR/EmployeeService.php'));

        $this->assertStringNotContainsString("'initial_attachment'", $service);
        $this->assertStringNotContainsString("'general_attachment'", $service);
    }

    /** شاشة تعديل الموظف بتحفظ النوع اللي المستخدم اختاره. */
    public function test_employee_form_saves_the_selected_document_type(): void
    {
        app(EmployeeService::class)->update($this->employee, [
            'name'         => 'Doc Owner',
            'employee_code'=> 'DOC-1',
            'phone'        => '0100',
            'branch_id'    => $this->branch->id,
            'job_title_id' => $this->jobTitle->id,
            'status'       => 'active',
            'hire_date'    => '2024-01-01',
            'documents'    => [
                ['document_type' => 'passport', 'title' => 'جواز', 'file_path' => '/x/passport.pdf'],
            ],
        ]);

        $document = EmployeeDocument::where('employee_id', $this->employee->id)->latest('id')->first();

        $this->assertNotNull($document);
        $this->assertEquals('passport', $document->document_type, 'النوع المختار مااتحفظش');
    }

    /** لو النوع مابعتش، بيقع على 'other' اللي ليها ترجمة. */
    public function test_missing_document_type_falls_back_to_other(): void
    {
        app(EmployeeService::class)->update($this->employee, [
            'name'         => 'Doc Owner',
            'employee_code'=> 'DOC-1',
            'phone'        => '0100',
            'branch_id'    => $this->branch->id,
            'job_title_id' => $this->jobTitle->id,
            'status'       => 'active',
            'hire_date'    => '2024-01-01',
            'documents'    => [
                ['title' => 'بدون نوع', 'file_path' => '/x/unknown.pdf'],
            ],
        ]);

        $document = EmployeeDocument::where('employee_id', $this->employee->id)->latest('id')->first();

        $this->assertEquals('other', $document->document_type);
        $this->assertNotEquals(
            'document.types.other',
            __('document.types.' . $document->document_type),
            'النوع الافتراضي مالوش ترجمة'
        );
    }

    /** شاشة تعديل الموظف فيها قائمة الأنواع زي البروفايل. */
    public function test_employee_form_offers_the_document_type_list(): void
    {
        $html = $this->get(route('admin.employees.edit', $this->employee->id))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('[document_type]', $html, 'حقل نوع المستند مش موجود');

        // نفس الأنواع المتاحة في البروفايل
        foreach (['contract', 'passport', 'national_id'] as $type) {
            $this->assertStringContainsString(
                __('document.types.' . $type),
                $html,
                "النوع [{$type}] مش في القائمة"
            );
        }
    }

    /** البروفايل بيتحمّل ومفيش مفاتيح ترجمة خام في جدول المستندات. */
    public function test_profile_shows_translated_type_labels(): void
    {
        EmployeeDocument::create([
            'employee_id'   => $this->employee->id,
            'document_type' => 'contract',
            'title'         => 'عقد',
            'file_path'     => '/x/contract.pdf',
        ]);

        $html = $this->get(route('admin.employees.show', $this->employee->id))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString(__('document.types.contract'), $html);
        $this->assertStringNotContainsString('document.types.', $html, 'في مفتاح ترجمة خام ظاهر');
    }

    /** نوع غير معروف بيتعرض كـ "مستندات أخرى" مش كمفتاح خام. */
    public function test_unknown_type_falls_back_in_the_profile_view(): void
    {
        EmployeeDocument::create([
            'employee_id'   => $this->employee->id,
            'document_type' => 'legacy_unknown_type',
            'title'         => 'قديم',
            'file_path'     => '/x/legacy.pdf',
        ]);

        $html = $this->get(route('admin.employees.show', $this->employee->id))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString(
            'document.types.legacy_unknown_type',
            $html,
            'النوع غير المعروف ظهر كمفتاح خام'
        );
    }

    /** رأس الجدول وخلايا الصف متطابقين — كان 3 أعمدة مقابل 4 خلايا. */
    public function test_documents_table_columns_align(): void
    {
        $html = $this->get(route('admin.employees.edit', $this->employee->id))
            ->assertOk()->getContent();

        $start = strpos($html, 'id="documents_table"');
        $this->assertNotFalse($start, 'جدول المستندات مش موجود');

        $table = substr($html, $start, strpos($html, '</table>', $start) - $start);

        // preg_match_all عشان نعد وسوم الفتح بس — substr_count كانت
        // بتلقط 'align-middle' جوّه الخصائص وتدي عدد غلط.
        $head = substr($table, 0, strpos($table, '</thead>'));
        $headers = preg_match_all('/<th[\s>]/', $head);

        // أول صف في الجدول
        $bodyStart = strpos($table, '<tbody');
        $firstRow  = substr($table, strpos($table, '<tr', $bodyStart));
        $firstRow  = substr($firstRow, 0, strpos($firstRow, '</tr>'));

        // بنعد خلايا المستوى الأول بس (مفيش جداول متداخلة هنا)
        $cells = preg_match_all('/<td[\s>]/', $firstRow);

        $this->assertEquals(
            $headers,
            $cells,
            "عدد أعمدة الرأس ({$headers}) مش مطابق لخلايا الصف ({$cells}) — الجدول هيتلغبط"
        );
    }

    /** كل حقول المستند ظاهرة في شاشة التعديل زي البروفايل. */
    public function test_edit_form_has_all_document_fields(): void
    {
        $html = $this->get(route('admin.employees.edit', $this->employee->id))
            ->assertOk()->getContent();

        foreach (['document_type', 'title', 'file_path', 'issue_date', 'expiry_date', 'is_verified'] as $field) {
            $this->assertStringContainsString(
                "[{$field}]",
                $html,
                "الحقل [{$field}] مش موجود في شاشة التعديل"
            );
        }
    }

    /** الحقول الجديدة بتتحفظ فعلاً. */
    public function test_dates_and_verification_are_saved(): void
    {
        app(EmployeeService::class)->update($this->employee, [
            'name' => 'Doc Owner', 'employee_code' => 'DOC-1', 'phone' => '0100',
            'branch_id' => $this->branch->id, 'job_title_id' => $this->jobTitle->id,
            'status' => 'active', 'hire_date' => '2024-01-01',
            'documents' => [[
                'document_type' => 'passport',
                'title'         => 'جواز سفر',
                'file_path'     => '/x/p.pdf',
                'issue_date'    => '2024-03-01',
                'expiry_date'   => '2029-03-01',
                'is_verified'   => '1',
            ]],
        ]);

        $doc = EmployeeDocument::where('employee_id', $this->employee->id)->latest('id')->first();

        $this->assertEquals('2024-03-01', $doc->issue_date?->format('Y-m-d'));
        $this->assertEquals('2029-03-01', $doc->expiry_date?->format('Y-m-d'));
        $this->assertTrue((bool) $doc->is_verified);
        $this->assertNotNull($doc->verified_at, 'وقت التحقق مااتسجّلش');
    }

    // ========================================================
    //  الاسم الاختياري (الخيار ب)
    // ========================================================

    /** الاسم الفاضي بياخد اسم النوع بدل ما المستخدم يكرّر نفس المعنى. */
    public function test_title_falls_back_to_the_type_name(): void
    {
        app(EmployeeService::class)->update($this->employee, [
            'name' => 'Doc Owner', 'employee_code' => 'DOC-1', 'phone' => '0100',
            'branch_id' => $this->branch->id, 'job_title_id' => $this->jobTitle->id,
            'status' => 'active', 'hire_date' => '2024-01-01',
            'documents' => [
                ['document_type' => 'contract', 'title' => '', 'file_path' => '/x/c.pdf'],
            ],
        ]);

        $doc = EmployeeDocument::where('employee_id', $this->employee->id)->latest('id')->first();

        $this->assertEquals(
            __('document.types.contract'),
            $doc->title,
            'الاسم الفاضي مااخدش اسم النوع'
        );
    }

    /** ولو المستخدم كتب اسماً، اسمه هو اللي يفضل. */
    public function test_custom_title_is_kept(): void
    {
        app(EmployeeService::class)->update($this->employee, [
            'name' => 'Doc Owner', 'employee_code' => 'DOC-1', 'phone' => '0100',
            'branch_id' => $this->branch->id, 'job_title_id' => $this->jobTitle->id,
            'status' => 'active', 'hire_date' => '2024-01-01',
            'documents' => [
                ['document_type' => 'contract', 'title' => 'عقد 2026', 'file_path' => '/x/c.pdf'],
            ],
        ]);

        $doc = EmployeeDocument::where('employee_id', $this->employee->id)->latest('id')->first();

        $this->assertEquals('عقد 2026', $doc->title);
    }

    /** رفع من البروفايل بدون اسم ولا نوع بيشتغل عادي. */
    public function test_profile_upload_works_without_title_or_type(): void
    {
        $this->post(route('admin.documents.store'), [
            'employee_id' => $this->employee->id,
            'file_path'   => '/x/minimal.pdf',
        ])->assertSessionHasNoErrors();

        $doc = EmployeeDocument::where('employee_id', $this->employee->id)->latest('id')->first();

        $this->assertNotNull($doc, 'المستند مااترفعش');
        $this->assertEquals('other', $doc->document_type, 'النوع مااخدش القيمة الافتراضية');
        $this->assertEquals(__('document.types.other'), $doc->title, 'الاسم مااخدش اسم النوع');
    }

    /** الحقلان مش إجباريين بصرياً في الشاشتين. */
    public function test_type_and_title_are_not_marked_required(): void
    {
        foreach ([
            route('admin.employees.edit', $this->employee->id),
            route('admin.employees.show', $this->employee->id),
        ] as $url) {
            $html = $this->get($url)->assertOk()->getContent();

            $pos = strpos($html, __('document.fields.document_type'));
            $this->assertNotFalse($pos, 'عنوان النوع مش موجود');

            // مفيش نجمة حمرا بعد عنوان النوع مباشرة
            $after = substr($html, $pos, 120);
            $this->assertStringNotContainsString('text-danger">*', $after, 'النوع لسه معلّم كإجباري');
        }
    }
}
