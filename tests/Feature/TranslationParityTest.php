<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * و.٢ — تكافؤ الترجمة بين العربي والإنجليزي.
 *
 * كان في 127 مفتاح ناقص، أغلبها ناقص في العربي — وهي لغة الواجهة
 * الأساسية، يعني المستخدم كان بيشوف المفتاح الخام. الاختبار ده
 * بيمنع النقص إنه يرجع تاني من غير ما حد ياخد باله.
 */
class TranslationParityTest extends TestCase
{
    /**
     * ملفات الواجهة اللي لازم تفضل متطابقة.
     *
     * validation مستثنى: مفاتيح attributes و custom بتتكتب في
     * لغة الواجهة بس، ومفيش داعي تتكرر في الاتنين.
     */
    private const EXCLUDED = ['validation'];

    /** @return array<string, mixed> */
    private function flatten(array $items, string $prefix = ''): array
    {
        $out = [];

        foreach ($items as $key => $value) {
            $path = $prefix === '' ? (string) $key : $prefix . '.' . $key;

            if (is_array($value)) {
                $out += $this->flatten($value, $path);
            } else {
                $out[$path] = $value;
            }
        }

        return $out;
    }

    private function load(string $locale, string $file): array
    {
        return $this->flatten(require base_path("lang/{$locale}/{$file}.php"));
    }

    /** @return list<string> */
    private function files(): array
    {
        $names = [];

        foreach (glob(base_path('lang/ar/*.php')) as $path) {
            $name = basename($path, '.php');

            if (in_array($name, self::EXCLUDED, true)) {
                continue;
            }

            if (file_exists(base_path("lang/en/{$name}.php"))) {
                $names[] = $name;
            }
        }

        return $names;
    }

    /** مفيش مفتاح إنجليزي من غير مقابل عربي. */
    public function test_no_key_is_missing_in_arabic(): void
    {
        $gaps = [];

        foreach ($this->files() as $file) {
            $missing = array_diff_key($this->load('en', $file), $this->load('ar', $file));

            foreach (array_keys($missing) as $key) {
                $gaps[] = "{$file}.{$key}";
            }
        }

        $this->assertSame([], $gaps, "مفاتيح ناقصة في العربي:\n" . implode("\n", $gaps));
    }

    /** ومفيش مفتاح عربي من غير مقابل إنجليزي. */
    public function test_no_key_is_missing_in_english(): void
    {
        $gaps = [];

        foreach ($this->files() as $file) {
            $missing = array_diff_key($this->load('ar', $file), $this->load('en', $file));

            foreach (array_keys($missing) as $key) {
                $gaps[] = "{$file}.{$key}";
            }
        }

        $this->assertSame([], $gaps, "مفاتيح ناقصة في الإنجليزي:\n" . implode("\n", $gaps));
    }

    /**
     * مفيش نص ظاهر للمستخدم فاضي.
     *
     * hint و placeholder الفاضيين مقصودين (الحقل مالوش شرح)، لكن
     * label أو رسالة فاضية بتبان كفراغ مكان النص.
     */
    public function test_no_visible_label_is_empty(): void
    {
        $optional = ['hint', 'placeholder', 'help', 'note'];
        $empty    = [];

        foreach (['ar', 'en'] as $locale) {
            foreach ($this->files() as $file) {
                foreach ($this->load($locale, $file) as $key => $value) {
                    if (! is_string($value) || trim($value) !== '') {
                        continue;
                    }

                    $leaf = substr($key, strrpos($key, '.') === false ? 0 : strrpos($key, '.') + 1);

                    if (in_array($leaf, $optional, true)) {
                        continue;
                    }

                    $empty[] = "{$locale}/{$file}.{$key}";
                }
            }
        }

        $this->assertSame([], $empty, "قيم فاضية:\n" . implode("\n", $empty));
    }

    /** كل ملفات الترجمة بترجّع مصفوفة سليمة. */
    public function test_every_language_file_returns_an_array(): void
    {
        foreach (['ar', 'en'] as $locale) {
            foreach (glob(base_path("lang/{$locale}/*.php")) as $path) {
                $this->assertIsArray(require $path, basename($path) . " مش بيرجّع مصفوفة");
            }
        }
    }
}
