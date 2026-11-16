<?php

namespace App\Http\Requests\Admin\Advance;

use Illuminate\Foundation\Http\FormRequest;

/** إسقاط جزء من السلفة بقرار إداري. */
class WaiveAdvanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'waived_amount' => ['required', 'numeric', 'min:0.01'],
            'reason'        => ['required', 'string', 'max:500'],
        ];
    }
}
