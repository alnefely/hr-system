<?php

namespace App\Http\Requests\Admin;

use App\Models\Settings;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Collection;

/**
 * حفظ شاشة الإعدادات — القواعد بتتبني من نوع كل إعداد في الجدول.
 */
class UpdateSettingsRequest extends FormRequest
{
    /** @var Collection<string, Settings>|null */
    private ?Collection $definitions = null;

    public function authorize(): bool
    {
        return true;
    }

    /** تعريفات الإعدادات (مفتاح => سجل) — بتتجاب مرة واحدة. */
    public function definitions(): Collection
    {
        return $this->definitions ??= Settings::whereNotNull('key')->get()->keyBy('key');
    }

    public function rules(): array
    {
        $rules = [];

        foreach ($this->definitions() as $key => $setting) {
            // sometimes: لو الحقل مش موجود في الطلب خالص بيتجاهل
            $rule = ['sometimes', $setting->is_required ? 'required' : 'nullable'];

            $typeRule = match ($setting->type) {
                'email'          => 'email',
                'url'            => 'url',
                'number'         => 'numeric',
                'checkbox'       => 'boolean',
                'date'           => 'date',
                'time'           => 'date_format:H:i',
                'datetime-local' => 'date_format:Y-m-d\TH:i',
                'file'           => 'string',
                'select'         => $this->selectRule($setting),
                default          => null,
            };

            if ($typeRule) {
                $rule[] = $typeRule;
            }

            $rules["settings.{$key}"] = $rule;
        }

        return $rules;
    }

    /** الخيارات ممكن تكون قائمة قيم أو [قيمة => اسم]. */
    private function selectRule(Settings $setting): ?string
    {
        if (empty($setting->options)) {
            return null;
        }

        $values = is_int(array_key_first($setting->options))
            ? array_values($setting->options)
            : array_keys($setting->options);

        return 'in:' . implode(',', $values);
    }
}
