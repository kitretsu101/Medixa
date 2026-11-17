<?php

namespace App\Http\Requests;

use App\Models\BloodDonor;
use App\Models\BloodRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterBloodDonor extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('phone'))) {
            $this->merge(['phone' => preg_replace('/^(?:\\+88|0088)/', '', trim($this->input('phone')))]);
        }
    }

    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:255'],
            'blood_group' => ['required', Rule::in(BloodRequest::GROUPS)],
            'phone' => ['required', 'string', 'regex:/^01[3-9][0-9]{8}$/'],
            'district' => ['required', 'string', 'max:100'],
            'area' => ['required', 'string', 'max:255'],
            'last_donation_date' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:today'],
            'availability_status' => ['required', Rule::in(BloodDonor::AVAILABILITY)],
            'public_profile' => ['required', 'boolean'],
            'contact_consent' => ['required', 'boolean'],
        ];
    }
}
