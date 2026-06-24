<?php

namespace App\Http\Requests\Attendance;

use Illuminate\Foundation\Http\FormRequest;

class ClockRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * Coordinates are required only when the geofence is enabled, since they
     * are what we check the office radius against.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $required = config('attendance.geofence_enabled') ? 'required' : 'nullable';

        return [
            'latitude' => [$required, 'numeric', 'between:-90,90'],
            'longitude' => [$required, 'numeric', 'between:-180,180'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'latitude.required' => 'Location access is required to clock in or out. Please enable location and try again.',
            'longitude.required' => 'Location access is required to clock in or out. Please enable location and try again.',
        ];
    }
}
