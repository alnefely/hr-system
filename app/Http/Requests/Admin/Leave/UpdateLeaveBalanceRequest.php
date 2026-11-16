<?php

namespace App\Http\Requests\Admin\Leave;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLeaveBalanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'annual_balance' => ['required', 'numeric', 'min:0'],
            'casual_balance' => ['required', 'numeric', 'min:0'],
            'sick_balance'   => ['required', 'numeric', 'min:0'],
        ];
    }
}
