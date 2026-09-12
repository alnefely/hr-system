<?php

namespace Tests\Feature;

use App\Rules\SafeUrl;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

/**
 * حماية الوجهة.
 *
 * من غيرها الموديول بيبقى أداة يوصل بيها أي مستخدم لخدمات
 * الشبكة الداخلية عن طريق السيرفر نفسه (SSRF).
 */
class SafeUrlTest extends TestCase
{
    private function fails(string $url): bool
    {
        return Validator::make(
            ['url' => $url],
            ['url' => [new SafeUrl]]
        )->fails();
    }

    public function test_accepts_normal_public_urls(): void
    {
        $this->assertFalse($this->fails('https://example.com'), 'رابط عام اترفض');
        $this->assertFalse($this->fails('http://example.com/path?a=1'), 'رابط عام برابط فرعي اترفض');
    }

    public function test_rejects_non_http_schemes(): void
    {
        foreach (['javascript:alert(1)', 'file:///etc/passwd', 'ftp://example.com', 'data:text/html,x'] as $url) {
            $this->assertTrue($this->fails($url), "البروتوكول الخطر {$url} اتقبل");
        }
    }

    public function test_rejects_internal_hosts(): void
    {
        foreach ([
            'http://localhost/admin',
            'http://127.0.0.1/',
            'http://0.0.0.0/',
            'http://service.local/',
            'http://api.internal/',
        ] as $url) {
            $this->assertTrue($this->fails($url), "عنوان داخلي {$url} اتقبل");
        }
    }

    public function test_rejects_private_ip_ranges(): void
    {
        foreach ([
            'http://192.168.1.1/',
            'http://10.0.0.5/',
            'http://172.16.0.1/',
            'http://169.254.169.254/latest/meta-data/',
        ] as $url) {
            $this->assertTrue($this->fails($url), "عنوان شبكة داخلية {$url} اتقبل");
        }
    }

    /**
     * اختصار رابط مختصر من نفس النظام = لوب لا نهائي.
     *
     * app.url بيتثبّت على دومين عام عشان نختبر منطق الـ self-link
     * لوحده — الدومين المحلي بيترفض أصلاً كعنوان داخلي، فالاختبار
     * كان هيعدّي حتى لو الفحص ده اتشال.
     */
    public function test_rejects_own_short_links(): void
    {
        config(['app.url' => 'https://example.com']);

        $this->assertTrue($this->fails('https://example.com/s/Abc1234'), 'رابط مختصر من نفس النظام اتقبل');
    }

    /** لكن باقي صفحات نفس الدومين مسموحة. */
    public function test_allows_other_pages_on_same_domain(): void
    {
        config(['app.url' => 'https://example.com']);

        $this->assertFalse($this->fails('https://example.com/admin/reports'), 'صفحة عادية من نفس الدومين اترفضت');
    }

    /**
     * القيمة الفاضية مش هنا: Laravel بيتخطى القواعد للقيم الفاضية،
     * والتحقق منها شغلانة required في الـ FormRequest.
     */
    public function test_rejects_malformed_urls(): void
    {
        foreach (['not-a-url', 'http://', '///'] as $url) {
            $this->assertTrue($this->fails($url), "رابط غير صالح {$url} اتقبل");
        }
    }
}
