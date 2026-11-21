<?php

namespace App\Http\Requests;

use App\Models\BloodRequest;
use App\Support\BangladeshDistricts;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDonorContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->route('bloodDonor')?->acceptsContactRequests() ?? false;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('requester_phone'))) {
            $this->merge(['requester_phone' => preg_replace('/^(?:\\+88|0088)/', '', trim($this->input('requester_phone')))]);
        }
    }

    public function rules(): array
    {
        return [
            'requester_name' => ['nullable', 'string', 'max:255'],
            'requester_phone' => ['required', 'string', 'regex:/^01[3-9][0-9]{8}$/'],
            'blood_group_needed' => ['required', Rule::in(BloodRequest::GROUPS)],
            'units_needed' => ['required', 'integer', 'min:1', 'max:20'],
            'hospital_name' => ['required', 'string', 'max:255'],
            'district' => ['required', Rule::in(BangladeshDistricts::ALL)],
            'area' => ['required', 'string', 'max:150'],
            'required_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today', 'before_or_equal:'.today()->addDays(30)->toDateString()],
            'emergency_level' => ['required', Rule::in(['normal', 'urgent', 'critical'])],
            'message' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
