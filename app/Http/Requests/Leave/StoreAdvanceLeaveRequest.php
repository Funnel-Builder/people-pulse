<?php

namespace App\Http\Requests\Leave;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAdvanceLeaveRequest extends FormRequest
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
            'leave_type' => ['required', 'string', 'exists:leave_types,code'],
            'reason' => ['required', 'string', 'min:10', 'max:1000'],
            'dates' => ['required', 'array', 'min:1'],
            'dates.*' => ['required', 'date', 'after:today'],
            'cover_person_id' => [
                'required',
                'exists:users,id',
                Rule::notIn([$this->user()->id]), // Can't be yourself
            ],
            'warning_confirmed' => ['sometimes', 'boolean'],
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
            'date.required' => 'Please select a date for your leave.',
            'date.after' => 'Advance leave must be for a future date.',
            'cover_person_id.required' => 'Please select a cover person.',
            'cover_person_id.not_in' => 'You cannot select yourself as a cover person.',
        ];
    }
}
