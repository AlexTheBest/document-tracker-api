<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDocumentRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $maxDate = now()->addYears(5)->format('Y-m-d');

        return [
            'name' => ['required', 'string', 'max:255'],
            'expires_at' => ['required', 'date', 'after:today', 'before_or_equal:' . $maxDate],
            'file' => ['required', 'file', 'mimes:pdf', 'max:10240'], // 10MB max
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'file.mimes' => 'Only PDF files are allowed.',
            'expires_at.after' => 'The expiry date must be in the future.',
            'expires_at.before_or_equal' => 'The expiry date cannot be more than 5 years in the future.',
        ];
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator): void
    {
        // Log warning if expiry date exceeds 5 years
        if ($validator->errors()->has('expires_at')) {
            $expiresAt = $this->input('expires_at');
            $maxAllowed = now()->addYears(5);
            
            if ($expiresAt && strtotime($expiresAt) > $maxAllowed->timestamp) {
                \Log::warning('Document creation attempted with expiry date beyond 5 years', [
                    'user_id' => $this->user()?->id,
                    'email' => $this->user()?->email,
                    'requested_expiry_date' => $expiresAt,
                    'max_allowed_date' => $maxAllowed->format('Y-m-d'),
                    'document_name' => $this->input('name'),
                ]);
            }
        }

        parent::failedValidation($validator);
    }
}
