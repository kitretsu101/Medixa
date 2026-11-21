<?php

namespace App\Http\Requests;

use App\Models\BloodDonor;
use App\Models\BloodRequest;
use App\Support\BangladeshDistricts;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterIndependentDonor extends FormRequest
{
    public function authorize(): bool
    {
        return true;
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
            'phone' => ['required', 'string', 'regex:/^01[3-9][0-9]{8}$/', Rule::unique('blood_donors', 'phone')],
            'district' => ['required', Rule::in(BangladeshDistricts::ALL)],
            'area' => ['required', 'string', 'max:255'],
            'last_donation_date' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:today'],
            'password' => ['required', 'string', 'min:8', 'max:72', 'regex:/\A[^\x00]{0,72}\z/', 'confirmed'],
            'availability_status' => ['required', Rule::in(BloodDonor::AVAILABILITY)],
            'public_profile' => ['required', 'boolean'],
            'contact_consent' => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return ['password.regex' => 'The password must not exceed 72 bytes or contain null characters.'];
    }
}
