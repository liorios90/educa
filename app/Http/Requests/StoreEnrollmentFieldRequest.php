<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEnrollmentFieldRequest extends FormRequest
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
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'label' => ['required', 'string', 'max:120'],
            'type' => ['required', Rule::in(['text', 'email', 'tel', 'date', 'textarea', 'select'])],
            'options' => ['required_if:type,select', 'nullable', 'string', 'max:1000'],
            'is_required' => ['nullable', 'boolean'],
        ];
    }
}
