<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegistrationIndexRequest extends FormRequest
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
            'search' => ['nullable', 'string', 'max:150'],
            'event' => ['nullable', 'string', 'max:255'],
            'member_state' => ['nullable', 'string', Rule::in(config('seed_summit.member_states', []))],
            'verification_status' => ['nullable', Rule::in(['verified', 'unverified'])],
            'mail_status' => ['nullable', Rule::in(['pending', 'queued', 'sending', 'sent', 'failed'])],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
            'period' => ['nullable', Rule::in(['today', '7_days', '30_days', 'all'])],
            'per_page' => ['nullable', 'integer', Rule::in([15, 25, 50, 100])],
        ];
    }

    /**
     * @return array<string, int|string>
     */
    public function registrationFilters(string $defaultPeriod = 'all'): array
    {
        $filters = array_filter(
            $this->validated(),
            static fn (mixed $value): bool => $value !== null && $value !== '',
        );

        $filters['period'] = (string) ($filters['period'] ?? $defaultPeriod);
        $filters['per_page'] = (int) ($filters['per_page'] ?? 25);

        return $filters;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'search' => $this->trimmedString('search'),
            'event' => $this->trimmedString('event'),
            'member_state' => $this->trimmedString('member_state'),
        ]);
    }

    private function trimmedString(string $key): ?string
    {
        $value = $this->input($key);

        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value !== '' ? $value : null;
    }
}
