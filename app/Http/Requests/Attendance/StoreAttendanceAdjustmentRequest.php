<?php

namespace App\Http\Requests\Attendance;

use App\Models\AttendanceAdjustmentRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAttendanceAdjustmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', AttendanceAdjustmentRequest::class);
    }

    public function rules(): array
    {
        $mimes = config('attendance.adjustment_attachment.mimes', 'pdf,jpg,jpeg,png');
        $maxKb = config('attendance.adjustment_attachment.max_kb', 5120);

        return [
            'type' => ['required', Rule::in([
                AttendanceAdjustmentRequest::TYPE_LATE_ENTRY,
                AttendanceAdjustmentRequest::TYPE_EARLY_OUT,
            ])],
            'date' => ['required', 'date'],
            'requested_time' => ['required', 'date_format:H:i'],
            'reason' => ['required', 'string', 'min:1', 'max:1000'],
            'cover_person_id' => [
                'required',
                'exists:users,id',
                Rule::notIn([$this->user()->id]),
            ],
            'attachment' => ['nullable', 'file', "mimes:{$mimes}", "max:{$maxKb}"],
        ];
    }

    public function messages(): array
    {
        return [
            'type.in' => 'Please choose either Late Entry or Early Out.',
            'requested_time.date_format' => 'Please provide a valid time.',
            'reason.required' => 'Please provide a reason for your request.',
            'cover_person_id.required' => 'Please select a cover person.',
            'cover_person_id.not_in' => 'You cannot select yourself as a cover person.',
            'attachment.max' => 'The document may not be larger than 5 MB.',
            'attachment.mimes' => 'The document must be a PDF or image (jpg, jpeg, png).',
        ];
    }
}
