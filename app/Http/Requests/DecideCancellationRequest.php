<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class DecideCancellationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $isApproval = $this->route()?->getActionMethod() === 'approve';

        return [
            'final_refund' => [
                Rule::requiredIf($isApproval),
                'string',
                'regex:/^\d+(?:\.\d{1,2})?$/',
            ],
            'explanation' => ['nullable', 'string', 'max:1000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        foreach (['final_refund', 'explanation'] as $field) {
            if (is_string($this->input($field))) {
                $this->merge([$field => trim($this->input($field))]);
            }
        }
    }
}
