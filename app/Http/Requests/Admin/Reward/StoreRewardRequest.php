<?php

namespace App\Http\Requests\Admin\Reward;

use App\Rules\AccessibleEmployee;
use Illuminate\Foundation\Http\FormRequest;

class StoreRewardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_id' => ['required', new AccessibleEmployee],
            'reward_type' => ['required', 'in:bonus,incentive,allowance,appreciation'],
            'title'       => ['required', 'string', 'max:255'],
            'amount'      => ['required', 'numeric', 'min:0.01'],
            'reward_date' => ['required', 'date'],
            'reason'      => ['nullable', 'string', 'max:1000'],
            'notes'       => ['nullable', 'string', 'max:1000'],
        ];
    }
}
