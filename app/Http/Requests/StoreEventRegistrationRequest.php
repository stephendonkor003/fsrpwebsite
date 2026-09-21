<?php

namespace App\Http\Requests;

use App\Models\Event;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreEventRegistrationRequest extends FormRequest
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
        $eventId = Event::query()
            ->where('slug', config('seed_summit.event_slug'))
            ->value('id');

        return [
            'title' => ['nullable', 'string', Rule::in(config('seed_summit.titles'))],
            'first_name' => ['required', 'string', 'max:120'],
            'surname' => ['required', 'string', 'max:120'],
            'gender' => ['nullable', 'string', Rule::in(config('seed_summit.genders'))],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'nationality' => ['nullable', 'string', Rule::in(config('seed_summit.countries'))],
            'national_id_number' => ['nullable', 'string', 'max:120'],
            'passport_number' => ['required', 'string', 'max:120'],
            'passport_expiry_date' => ['nullable', 'date', 'after_or_equal:today'],
            'issuing_country' => ['nullable', 'string', Rule::in(config('seed_summit.countries'))],
            'visa_required' => ['nullable', 'boolean'],
            'passport_photo' => [
                'required', 'image', 'mimes:jpg,jpeg,png,webp',
                'extensions:jpg,jpeg,png,webp', 'max:5120',
            ],
            'passport_scan' => [
                'nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf',
                'extensions:jpg,jpeg,png,webp,pdf', 'max:10240',
            ],
            'organisation' => ['required', 'string', 'max:255'],
            'member_state' => ['nullable', 'string', Rule::in(config('seed_summit.member_states'))],
            'delegation_capacity' => ['nullable', 'string', Rule::in(config('seed_summit.delegation_capacities'))],
            'years_in_service' => ['nullable', 'integer', 'min:0', 'max:80'],
            'areas_of_expertise' => ['nullable', 'string', 'max:2000'],
            'mobile_number' => ['required', 'string', 'max:40', 'regex:/^[0-9+().\-\sxX]{7,40}$/'],
            'alternative_phone' => ['nullable', 'string', 'max:40', 'regex:/^[0-9+().\-\sxX]{7,40}$/'],
            'official_email' => [
                'required', 'string', 'email:rfc', 'max:254',
            ],
            'personal_email' => ['nullable', 'string', 'email:rfc', 'max:254'],
            'emergency_contact' => ['nullable', 'string', 'max:500'],
            'arrival_date' => ['nullable', 'date'],
            'departure_date' => ['nullable', 'date', 'after_or_equal:arrival_date'],
            'dietary_requirements' => ['nullable', 'string', Rule::in(config('seed_summit.dietary_requirements'))],
            'other_dietary_needs' => [
                Rule::requiredIf(fn (): bool => $this->input('dietary_requirements') === 'Other'),
                'nullable', 'string', 'max:1000',
            ],
            'dinner_attendance' => ['nullable', 'boolean'],
            'data_protection_declaration' => ['required', 'accepted'],
            'attendance_confirmation' => ['required', 'accepted'],
            'website' => ['prohibited'],
        ];
    }

    /** @return array<int, callable(Validator): void> */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $event = Event::query()
                    ->where('slug', config('seed_summit.event_slug'))
                    ->first();
                $passportPhoto = $this->file('passport_photo');

                if ($passportPhoto !== null
                    && ! $validator->errors()->has('passport_photo')
                    && @getimagesize($passportPhoto->getRealPath()) === false) {
                    $validator->errors()->add(
                        'passport_photo',
                        'The passport photo must be a valid JPG, PNG, or WebP image.',
                    );
                }

                if ($event?->end_at !== null
                    && ! $validator->errors()->has('passport_expiry_date')
                    && $this->filled('passport_expiry_date')
                    && $this->date('passport_expiry_date')?->lt($event->end_at->startOfDay())) {
                    $validator->errors()->add(
                        'passport_expiry_date',
                        'The passport must remain valid through the end of the summit.',
                    );
                }

                if ($validator->errors()->hasAny(['passport_expiry_date', 'departure_date'])
                    || ! $this->filled('passport_expiry_date')
                    || ! $this->filled('departure_date')) {
                    return;
                }

                if ($this->date('passport_expiry_date')?->lt($this->date('departure_date'))) {
                    $validator->errors()->add(
                        'passport_expiry_date',
                        'The passport must remain valid through the departure date.',
                    );
                }
            },
        ];
    }

    /** @return array<string, mixed> */
    public function registrationData(): array
    {
        $data = Arr::only($this->validated(), [
            'title', 'first_name', 'surname', 'gender', 'date_of_birth', 'nationality',
            'national_id_number', 'passport_number', 'passport_expiry_date', 'issuing_country',
            'visa_required', 'organisation', 'member_state', 'delegation_capacity',
            'years_in_service', 'areas_of_expertise', 'mobile_number', 'alternative_phone',
            'official_email', 'personal_email', 'emergency_contact', 'arrival_date',
            'departure_date', 'dietary_requirements', 'other_dietary_needs', 'dinner_attendance',
        ]);

        foreach (['visa_required', 'dinner_attendance'] as $booleanField) {
            $data[$booleanField] = $this->filled($booleanField)
                ? $this->boolean($booleanField)
                : null;
        }

        return $data;
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'mobile_number.regex' => 'Enter a valid international mobile number.',
            'alternative_phone.regex' => 'Enter a valid international phone number.',
            'passport_photo.required' => 'Upload a clear delegate profile photo.',
            'passport_photo.max' => 'The passport photo must not exceed 5 MB.',
            'passport_scan.max' => 'The passport scan must not exceed 10 MB.',
            'other_dietary_needs.required' => 'Describe the other dietary needs.',
            'data_protection_declaration.accepted' => 'You must agree to the data protection declaration.',
            'attendance_confirmation.accepted' => 'You must confirm attendance to register.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('official_email'))) {
            $this->merge([
                'official_email' => Str::lower(trim($this->string('official_email')->toString())),
            ]);
        }
    }

    protected function failedValidation(Validator $validator): never
    {
        $input = Arr::only($this->input(), [
            'title', 'first_name', 'surname', 'gender', 'date_of_birth', 'nationality',
            'national_id_number', 'passport_number', 'passport_expiry_date', 'issuing_country',
            'visa_required', 'organisation', 'member_state', 'delegation_capacity',
            'years_in_service', 'areas_of_expertise', 'mobile_number', 'alternative_phone',
            'official_email', 'personal_email', 'emergency_contact', 'arrival_date',
            'departure_date', 'dietary_requirements', 'other_dietary_needs', 'dinner_attendance',
            'data_protection_declaration', 'attendance_confirmation',
        ]);

        $this->session()->flash(
            'seed_summit.encrypted_form_input',
            Crypt::encryptString(json_encode($input, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)),
        );

        parent::failedValidation($validator);
    }
}
