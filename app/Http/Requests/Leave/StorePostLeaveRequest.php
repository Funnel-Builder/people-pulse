<?php

namespace App\Http\Requests\Leave;

use Illuminate\Foundation\Http\FormRequest;

class StorePostLeaveRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'min:10', 'max:1000'],
            'dates' => ['required', 'array', 'min:1'],
            'dates.*' => ['required', 'date', 'before_or_equal:today'],
            'leave_type' => ['sometimes', 'string', 'exists:leave_types,code'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'reason.required' => 'Please provide a reason for your leave.',
            'reason.min' => 'The reason must be at least 10 characters.',
            'dates.required' => 'Please select at least one date.',
            'dates.min' => 'Please select at least one date.',
            'dates.*.before_or_equal' => 'Post leave dates must be today or in the past.',
        ];
    }
}
