<?php

namespace App\Http\Requests\Admin\Permission;

use App\Rules\AccessibleEmployee;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;

class StorePermissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_id'     => ['required', new AccessibleEmployee],
            'permission_date' => ['required', 'date'],
            'start_time'      => ['required', 'date_format:H:i'],
            'end_time'        => ['required', 'date_format:H:i', 'after:start_time'],
            'hours_count'     => ['required', 'numeric', 'min:0.1', 'max:12'],
            'reason'          => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * البيانات الجاهزة للحفظ.
     *
     * الساعات بتتحسب من الوقت مش من إدخال المستخدم: كان ممكن يسجّل إذن
     * من 9 لـ 10 ويكتب 8 ساعات، والمسير يخصم على أساسها.
     */
    public function permissionData(): array
    {
        $data = $this->validated();

        $start = Carbon::createFromFormat('H:i', $data['start_time']);
        $end   = Carbon::createFromFormat('H:i', $data['end_time']);
        $data['hours_count'] = round($start->floatDiffInHours($end), 2);

        return $data;
    }
}
