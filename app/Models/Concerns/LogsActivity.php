<?php

namespace App\Models\Concerns;

use App\Models\ActivityLog;
use App\Services\Activity\ActivityLogger;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * تسجيل تلقائي لكل إضافة/تعديل/حذف على الموديل.
 *
 * الاستخدام: ضيف الـ trait على الموديل وخلاص.
 *
 *     class Sale extends Model
 *     {
 *         use LogsActivity;
 *     }
 *
 * التخصيص (كله اختياري) عبر خصائص على الموديل:
 *
 *   // حقول ماتتسجّلش نهائياً (حساسة أو ضوضاء)
 *   protected array $activityHidden = ['password', 'remember_token'];
 *
 *   // لو محددة، دي بس اللي تتسجّل — أي حاجة تانية تتجاهل
 *   protected array $activityOnly = ['status', 'amount'];
 *
 *   // الحقل اللي بيوصف السجل في القوائم
 *   protected string $activityLabelAttribute = 'name';
 */
trait LogsActivity
{
    /** الحقول اللي مابتتسجّلش في أي موديل — ضوضاء أو بيانات حساسة. */
    protected static array $activityGlobalHidden = [
        'password',
        'remember_token',
        'updated_at',
        'created_at',
        'deleted_at',
    ];

    public static function bootLogsActivity(): void
    {
        static::created(function ($model) {
            $model->recordActivity('created', [], $model->activityAttributes($model->getAttributes()));
        });

        static::updated(function ($model) {
            $new = $model->activityAttributes($model->getChanges());

            // مفيش تغيير حقيقي (يمكن اتغيّر updated_at بس) => مانسجّلش
            if (empty($new)) {
                return;
            }

            $old = [];
            foreach (array_keys($new) as $field) {
                $old[$field] = $model->getOriginal($field);
            }

            $model->recordActivity('updated', $old, $new);
        });

        static::deleted(function ($model) {
            $isSoft = method_exists($model, 'isForceDeleting') && !$model->isForceDeleting();

            $model->recordActivity(
                $isSoft ? 'deleted' : 'force_deleted',
                $model->activityAttributes($model->getOriginal()),
                []
            );
        });

        if (method_exists(static::class, 'restored')) {
            static::restored(function ($model) {
                $model->recordActivity('restored', [], $model->activityAttributes($model->getAttributes()));
            });
        }
    }

    /** كل الحركات المسجّلة على السجل ده. */
    public function activities(): MorphMany
    {
        return $this->morphMany(ActivityLog::class, 'subject')->latest('id');
    }

    /**
     * تنقية الحقول قبل التسجيل.
     *
     * بنشيل الحقول المخفية والحساسة، وبنقصّ أي قيمة طويلة جداً عشان
     * الـ JSON مايتضخّمش (مرفقات base64 مثلاً).
     */
    public function activityAttributes(array $attributes): array
    {
        $only = property_exists($this, 'activityOnly') ? $this->activityOnly : null;

        $hidden = array_merge(
            static::$activityGlobalHidden,
            property_exists($this, 'activityHidden') ? $this->activityHidden : []
        );

        $clean = [];

        foreach ($attributes as $key => $value) {
            if (in_array($key, $hidden, true)) {
                continue;
            }

            if ($only !== null && !in_array($key, $only, true)) {
                continue;
            }

            if (is_object($value)) {
                $value = method_exists($value, '__toString') ? (string) $value : json_encode($value);
            }

            if (is_string($value) && mb_strlen($value) > 500) {
                $value = mb_substr($value, 0, 500) . '…';
            }

            $clean[$key] = $value;
        }

        return $clean;
    }

    /** الوصف اللي يظهر للسجل في قوائم اللوج. */
    public function activityLabel(): ?string
    {
        $attribute = property_exists($this, 'activityLabelAttribute')
            ? $this->activityLabelAttribute
            : null;

        if ($attribute && !empty($this->{$attribute})) {
            return (string) $this->{$attribute};
        }

        foreach (['name', 'title', 'invoice_number', 'employee_code', 'code', 'email'] as $candidate) {
            if (!empty($this->{$candidate})) {
                return (string) $this->{$candidate};
            }
        }

        return null;
    }

    public function recordActivity(string $event, array $old, array $new): void
    {
        app(ActivityLogger::class)->log($this, $event, $old, $new);
    }
}
