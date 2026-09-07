<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * د.٥ — ترحيل المصادر من النص الثابت لجدول المصادر.
 *
 * المصادر الست كانت مكتوبة في الـ blade كـ options ثابتة. بننشئها
 * كسجلات، وبنربط كل عميل بالسجل اللي يقابل النص المخزّن عنده.
 *
 * العمود النصي القديم بيفضل زي ما هو — الترحيل مايمسحش بيانات.
 */
return new class extends Migration
{
    /** المصادر الست اللي كانت ثابتة في القائمة. */
    private const DEFAULTS = [
        ['code' => 'SRC-REFERRAL',  'key' => 'referral',     'name' => 'إحالة من عميل حالي',     'sort' => 1],
        ['code' => 'SRC-WEBSITE',   'key' => 'website',      'name' => 'الموقع الإلكتروني',       'sort' => 2],
        ['code' => 'SRC-SOCIAL',    'key' => 'social_media', 'name' => 'وسائل التواصل الاجتماعي', 'sort' => 3],
        ['code' => 'SRC-DIRECT',    'key' => 'direct',       'name' => 'تواصل مباشر',             'sort' => 4],
        ['code' => 'SRC-EXPO',      'key' => 'exhibition',   'name' => 'معرض أو مؤتمر',           'sort' => 5],
        ['code' => 'SRC-PARTNER',   'key' => 'partner',      'name' => 'شريك أعمال',              'sort' => 6],
    ];

    public function up(): void
    {
        // القواعد اللي شغّلت جدول العملاء قبل إضافة العمود مش هيبقى
        // عندها العمود، فبنضيفه هنا بدل ملف ميجريشن add_ منفصل.
        if (! Schema::hasColumn('customers', 'customer_source_id')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->foreignId('customer_source_id')->nullable()->after('customer_type')
                    ->constrained('customer_sources')->nullOnDelete();
            });
        }

        // أعمدة الدولة للهاتف — الشاشة بقت تستخدم مكوّن الهاتف
        // الدولي زي شاشة الموظف، فبيبعت كود الدولة معاه.
        foreach (['phone_country', 'secondary_phone_country'] as $column) {
            if (! Schema::hasColumn('customers', $column)) {
                Schema::table('customers', function (Blueprint $table) use ($column) {
                    $table->string($column, 10)->nullable();
                });
            }
        }

        $now = now();

        foreach (self::DEFAULTS as $row) {
            $exists = DB::table('customer_sources')->where('code', $row['code'])->exists();

            if (! $exists) {
                DB::table('customer_sources')->insert([
                    'code'       => $row['code'],
                    'name'       => $row['name'],
                    'is_active'  => true,
                    'sort_order' => $row['sort'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            // ربط العملاء اللي عندهم النص القديم بالسجل الجديد
            $id = DB::table('customer_sources')->where('code', $row['code'])->value('id');

            DB::table('customers')
                ->where('source', $row['key'])
                ->whereNull('customer_source_id')
                ->update(['customer_source_id' => $id]);
        }

        // قيم اتكتبت بإيد مش ضمن الست دول: بننشئ لكل قيمة مصدر
        // خاص بيها بدل ما نرميها.
        $custom = DB::table('customers')
            ->whereNotNull('source')
            ->whereNull('customer_source_id')
            ->distinct()
            ->pluck('source');

        foreach ($custom as $index => $value) {
            $value = trim((string) $value);

            if ($value === '') {
                continue;
            }

            $code = 'SRC-LEGACY-' . ($index + 1);

            if (! DB::table('customer_sources')->where('code', $code)->exists()) {
                DB::table('customer_sources')->insert([
                    'code'       => $code,
                    'name'       => mb_substr($value, 0, 150),
                    'is_active'  => true,
                    'sort_order' => 90 + $index,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            $id = DB::table('customer_sources')->where('code', $code)->value('id');

            DB::table('customers')
                ->where('source', $value)
                ->whereNull('customer_source_id')
                ->update(['customer_source_id' => $id]);
        }
    }

    public function down(): void
    {
        // النص القديم لسه موجود، فبنفك الربط بس
        DB::table('customers')->update(['customer_source_id' => null]);

        DB::table('customer_sources')
            ->where('code', 'like', 'SRC-%')
            ->delete();
    }
};
