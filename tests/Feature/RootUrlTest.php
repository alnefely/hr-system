<?php

namespace Tests\Feature;

use App\Mail\AppMail;
use App\Models\Admin;
use App\Models\Role;
use App\Rules\SafeUrl;
use App\Support\RootUrl;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

/**
 * النظام مابيعتمدش على APP_URL.
 *
 * كل الاختبارات هنا بتثبّت APP_URL على localhost والنظام فعلياً متفتح
 * من دومين تاني — وده بالظبط الوضع اللي كان بيطلّع روابط localhost.
 */
class RootUrlTest extends TestCase
{
    use RefreshDatabase;

    private const REAL = 'https://hr.example.com';

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.url' => 'http://localhost']);
    }

    /** طلب المتصفح بيسجّل الدومين اللي النظام متفتح منه. */
    public function test_browser_request_remembers_its_domain(): void
    {
        $role  = Role::create(['name' => 'R', 'roles' => all_roles()]);
        $admin = Admin::create([
            'name' => 'A', 'email' => 'a@root.test', 'password' => bcrypt('x'),
            'main' => true, 'role_id' => $role->id,
        ]);

        $this->actingAs($admin, 'admin')
            ->get(self::REAL . '/admin/home')
            ->assertOk();

        $this->assertEquals(self::REAL, Cache::get(RootUrl::CACHE_KEY), 'الدومين مااتسجّلش');
    }

    /**
     * الأوامر المجدولة والطابور بيبنوا الروابط على الدومين المسجّل.
     *
     * ده مصدر روابط localhost: التنبيهات اليومية والإيميلات بتتبني
     * من غير طلب متصفح.
     */
    public function test_console_builds_links_on_remembered_domain(): void
    {
        Cache::forever(RootUrl::CACHE_KEY, self::REAL);

        RootUrl::applyToConsole();

        $this->assertStringStartsWith(self::REAL . '/', route('admin.home'), 'الكونسول لسه بياخد APP_URL');
        $this->assertStringStartsWith(self::REAL . '/', url('/logo.png'));
    }

    /** الإيميل (بيترندر في الطابور) مابيطلعش روابط localhost. */
    public function test_email_rendered_in_queue_uses_remembered_domain(): void
    {
        Cache::forever(RootUrl::CACHE_KEY, self::REAL);
        RootUrl::applyToConsole();

        $html = (new AppMail(
            title: 'reset',
            markdownView: 'emails.admin.reset-link',
            // رابط إعادة التعيين من غير دومين: الدومين لازم ييجي من القالب نفسه
            // (اللوجو والأيقونة) — وإلا الاختبار بيعدّي من غير ما يختبر حاجة.
            data: ['name' => 'A', 'reset_url' => 'RESET-LINK', 'expire_minutes' => 60],
        ))->render();

        $this->assertStringContainsString(self::REAL . '/', $html, 'روابط القالب مش على الدومين الحقيقي');
    }

    /** قبل أول زيارة (مفيش دومين مسجّل) الكونسول مابيقعش. */
    public function test_console_falls_back_safely_without_remembered_domain(): void
    {
        Cache::forget(RootUrl::CACHE_KEY);

        // الاحتياطي هو سلوك لارافيل العادي (APP_URL من .env) — مابيتغيّرش
        $before = route('admin.home');

        RootUrl::applyToConsole();

        $this->assertEquals($before, route('admin.home'));
    }

    /** روابط الملفات نسبية — مابتاخدش دومين من الإعدادات. */
    public function test_public_storage_urls_are_relative(): void
    {
        $this->assertEquals('/storage/docs/a.pdf', Storage::disk('public')->url('docs/a.pdf'));
    }

    /** منع اختصار رابط النظام نفسه بيعرف الدومين الحقيقي مش APP_URL. */
    public function test_safe_url_knows_the_real_domain(): void
    {
        Cache::forever(RootUrl::CACHE_KEY, self::REAL);

        $fails = Validator::make(['u' => self::REAL . '/s/Abc1234'], ['u' => [new SafeUrl]])->fails();

        $this->assertTrue($fails, 'اتقبل اختصار رابط مختصر من النظام نفسه');
    }
}
