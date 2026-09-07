<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Role;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

/**
 * تدفق إعادة تعيين كلمة مرور المشرف.
 *
 * الطريقة القديمة كانت بتولّد كلمة مرور وتغيّرها فوراً وتبعتها بالإيميل،
 * يعني أي حد يعرف إيميل مشرف كان يقدر يقفل حسابه من غير ما يوصل للإيميل.
 * الاختبارات دي بتتأكد إن كلمة المرور مابتتغيّرش غير بتوكن صالح.
 */
class AdminPasswordResetTest extends TestCase
{
    use RefreshDatabase;

    protected Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();

        $role = Role::create(['name' => 'Super Admin', 'roles' => all_roles()]);

        $this->admin = Admin::create([
            'name' => 'Reset Target', 'email' => 'target@test.com',
            'password' => bcrypt('original-password'), 'main' => true,
            'role_id' => $role->id,
        ]);
    }

    /** طلب الرابط مابيغيّرش كلمة المرور. */
    public function test_requesting_reset_link_does_not_change_password(): void
    {
        $originalHash = $this->admin->password;

        $this->post(route('admin.sendPassword'), ['email' => 'target@test.com'])
            ->assertSessionHas('success');

        $this->assertEquals(
            $originalHash,
            $this->admin->fresh()->password,
            'كلمة المرور اتغيّرت بمجرد طلب الرابط'
        );

        $this->assertDatabaseHas('admin_password_reset_tokens', ['email' => 'target@test.com']);
    }

    /** إيميل غير مسجّل بيدي نفس الرد عشان مايكشفش المستخدمين. */
    public function test_unknown_email_gets_the_same_generic_response(): void
    {
        $this->post(route('admin.sendPassword'), ['email' => 'nobody@test.com'])
            ->assertSessionHas('success');

        $this->assertDatabaseCount('admin_password_reset_tokens', 0);
    }

    /** التوكن الصالح بيغيّر كلمة المرور فعلاً. */
    public function test_valid_token_resets_the_password(): void
    {
        Event::fake([PasswordReset::class]);

        $token = Password::broker('admins')->createToken($this->admin);

        $this->post(route('admin.password.update'), [
            'token'                 => $token,
            'email'                 => 'target@test.com',
            'password'              => 'NewSecret123',
            'password_confirmation' => 'NewSecret123',
        ])->assertSessionHas('success');

        $this->assertTrue(
            Hash::check('NewSecret123', $this->admin->fresh()->password),
            'كلمة المرور الجديدة ماتحفظتش'
        );
    }

    /** التوكن غير الصالح مابيغيّرش حاجة. */
    public function test_invalid_token_is_rejected(): void
    {
        $originalHash = $this->admin->password;

        $this->post(route('admin.password.update'), [
            'token'                 => 'totally-invalid-token',
            'email'                 => 'target@test.com',
            'password'              => 'NewSecret123',
            'password_confirmation' => 'NewSecret123',
        ])->assertSessionHas('error');

        $this->assertEquals(
            $originalHash,
            $this->admin->fresh()->password,
            'كلمة المرور اتغيّرت بتوكن غير صالح'
        );
    }

    /** كلمة المرور الضعيفة مرفوضة. */
    public function test_weak_password_is_rejected(): void
    {
        $token = Password::broker('admins')->createToken($this->admin);
        $originalHash = $this->admin->password;

        $this->post(route('admin.password.update'), [
            'token'                 => $token,
            'email'                 => 'target@test.com',
            'password'              => '123',
            'password_confirmation' => '123',
        ])->assertSessionHasErrors('password');

        $this->assertEquals($originalHash, $this->admin->fresh()->password);
    }

    /** التوكن مابيشتغلش مرتين. */
    public function test_token_cannot_be_reused(): void
    {
        $token = Password::broker('admins')->createToken($this->admin);

        $this->post(route('admin.password.update'), [
            'token'                 => $token,
            'email'                 => 'target@test.com',
            'password'              => 'FirstSecret123',
            'password_confirmation' => 'FirstSecret123',
        ])->assertSessionHas('success');

        $this->post(route('admin.password.update'), [
            'token'                 => $token,
            'email'                 => 'target@test.com',
            'password'              => 'SecondSecret123',
            'password_confirmation' => 'SecondSecret123',
        ])->assertSessionHas('error');

        $this->assertTrue(
            Hash::check('FirstSecret123', $this->admin->fresh()->password),
            'التوكن اشتغل مرتين'
        );
    }
}
