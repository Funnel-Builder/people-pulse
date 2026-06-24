<?php

namespace App\Http\Requests\Attendance;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProcessAttendanceAdjustmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Fine-grained authorization is handled in the controller via the policy.
        return true;
    }

    public function rules(): array
    {
        return [
            'action' => ['required', Rule::in(['approve', 'reject'])],
            'comment' => ['nullable', 'string', 'max:1000', 'required_if:action,reject'],
        ];
    }

    public function messages(): array
    {
        return [
            'comment.required_if' => 'Please provide a reason for rejection.',
        ];
    }
}
