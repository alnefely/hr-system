<?php

namespace App\Http\Requests\Portal;

use App\Enums\LeaveType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;

/**
 * طلب إجازة من الموظف لنفسه.
 *
 * عدد الأيام مابيتكتبش — بيتحسب من التاريخين (شاملين الاتنين). الاستثناء
 * الوحيد نص يوم، ومسموح بس لو الإجازة يوم واحد. أي days_count جاي في
 * الطلب بيتجاهل.
 */
class PortalLeaveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'leave_type' => ['required', LeaveType::rule()],
            'start_date' => ['required', 'date'],
            'end_date'   => ['required', 'date', 'after_or_equal:start_date'],
            'half_day'   => ['nullable', 'boolean'],
            'reason'     => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function after(): array
    {
        return [
            function ($validator) {
                if ($this->boolean('half_day') && $this->input('start_date') !== $this->input('end_date')) {
                    $validator->errors()->add('half_day', __('portal.leaves.half_day_single'));
                }
            },
        ];
    }

    /** البيانات للخدمة: عدد الأيام محسوب، ومن غير half_day. */
    public function validated($key = null, $default = null)
    {
        $data = parent::validated();

        $days = Carbon::parse($data['start_date'])->diffInDays(Carbon::parse($data['end_date'])) + 1;
        $data['days_count'] = ! empty($data['half_day']) && $days == 1 ? 0.5 : $days;
        unset($data['half_day']);

        return $key === null ? $data : data_get($data, $key, $default);
    }

    public function attributes(): array
    {
        return [
            'leave_type' => __('portal.leaves.type'),
            'start_date' => __('portal.leaves.from'),
            'end_date'   => __('portal.leaves.to'),
            'half_day'   => __('portal.leaves.half_day'),
            'reason'     => __('portal.fields.reason'),
        ];
    }
}
