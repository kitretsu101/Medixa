<?php

namespace App\Http\Requests;

use App\Models\BloodRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBloodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('contact_number'))) {
            $this->merge(['contact_number' => preg_replace('/^(?:\\+88|0088)/', '', trim($this->input('contact_number')))]);
        }
    }

    public function rules(): array
    {
        return [
            'patient_name' => ['required', 'string', 'max:255'],
            'contact_name' => ['required', 'string', 'max:255'],
            'blood_group' => ['required', Rule::in(BloodRequest::GROUPS)],
            'units_required' => ['required', 'integer', 'min:1', 'max:20'],
            'hospital_name' => ['required', 'string', 'max:255'],
            'district' => ['required', 'string', 'max:100'],
            'area' => ['required', 'string', 'max:255'],
            'required_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today', 'before_or_equal:'.today()->addDays(30)->toDateString()],
            'emergency_level' => ['required', Rule::in(BloodRequest::LEVELS)],
            'contact_number' => ['required', 'string', 'regex:/^01[3-9][0-9]{8}$/'],
            'description' => ['nullable', 'string', 'max:2000'],
            'contact_consent' => ['required', 'boolean'],
            'website' => ['nullable', 'max:0'],
        ];
    }
}
