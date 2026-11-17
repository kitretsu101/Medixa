<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBloodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'admin';
    }

    public function rules(): array
    {
        return ['status' => ['required', Rule::in(['active', 'rejected', 'fulfilled', 'expired'])], 'notes' => ['required', 'string', 'min:5', 'max:2000']];
    }
}
