<?php

/*
|--------------------------------------------------------------------------
| دوال سجل الحركات
|--------------------------------------------------------------------------
| اختصارات للـ Blade والموديل عشان مايتكررش نفس المنطق في كل مكان.
*/

if (!function_exists('activity_subjects')) {
    /**
     * الموديولات المراقَبة: [كلاس => الاسم المترجم]، مرتّبة أبجدياً.
     *
     * @return array<string, string>
     */
    function activity_subjects(): array
    {
        $subjects = [];

        foreach (config('activity.subjects', []) as $class => $langKey) {
            $subjects[$class] = __($langKey);
        }

        asort($subjects);

        return $subjects;
    }
}

if (!function_exists('activity_subject_label')) {
    /** اسم الموديول المترجم من كلاس الموديل. */
    function activity_subject_label(?string $class): string
    {
        if (!$class) {
            return '-';
        }

        $langKey = config('activity.subjects.' . str_replace('\\', '\\', $class));

        // config() مابيقراش المفاتيح اللي فيها backslash، فبنقرا المصفوفة كلها.
        if (!$langKey) {
            $langKey = config('activity.subjects', [])[$class] ?? null;
        }

        return $langKey ? __($langKey) : class_basename($class);
    }
}

if (!function_exists('activity_field_label')) {
    /**
     * اسم الحقل المترجم.
     *
     * بندوّر في ملف ترجمة الموديول الأول، وبعدين في global،
     * وفي الآخر بنرجّع اسم العمود بشكل مقروء.
     */
    function activity_field_label(?string $class, string $field): string
    {
        $file = config('activity.field_labels', [])[$class] ?? null;

        if ($file) {
            $key = "{$file}.fields.{$field}";
            if (__($key) !== $key) {
                return __($key);
            }

            $key = "{$file}.{$field}";
            if (__($key) !== $key) {
                return __($key);
            }
        }

        $key = "global.{$field}";
        if (__($key) !== $key) {
            return __($key);
        }

        return str_replace('_', ' ', $field);
    }
}

if (!function_exists('activity_events')) {
    /**
     * أنواع العمليات: [المفتاح => الاسم المترجم].
     *
     * @return array<string, string>
     */
    function activity_events(): array
    {
        return [
            'created'       => __('activity.events.created'),
            'updated'       => __('activity.events.updated'),
            'deleted'       => __('activity.events.deleted'),
            'restored'      => __('activity.events.restored'),
            'force_deleted' => __('activity.events.force_deleted'),
        ];
    }
}

if (!function_exists('activity_event_badge')) {
    /** كلاس البادج المناسب لنوع العملية. */
    function activity_event_badge(string $event): string
    {
        return match ($event) {
            'created'       => 'bg-label-success',
            'updated'       => 'bg-label-info',
            'deleted'       => 'bg-label-warning',
            'force_deleted' => 'bg-label-danger',
            'restored'      => 'bg-label-primary',
            default         => 'bg-label-secondary',
        };
    }
}

if (!function_exists('activity_value')) {
    /**
     * عرض قيمة الحقل بشكل مقروء.
     *
     * القيم الفارغة بتبقى شرطة، والبوليان نعم/لا، والمصفوفات JSON مختصر.
     */
    function activity_value(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        if (is_bool($value)) {
            return $value ? __('global.yes') : __('global.no');
        }

        if (is_array($value)) {
            $json = json_encode($value, JSON_UNESCAPED_UNICODE);

            return mb_strlen($json) > 120 ? mb_substr($json, 0, 120) . '…' : $json;
        }

        $string = (string) $value;

        return mb_strlen($string) > 120 ? mb_substr($string, 0, 120) . '…' : $string;
    }
}
