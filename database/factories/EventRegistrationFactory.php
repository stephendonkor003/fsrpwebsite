<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\EventRegistration;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EventRegistration>
 */
class EventRegistrationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $eventStartsAt = now()->addMonth()->startOfDay();

        return [
            'event_id' => Event::factory(),
            'event_slug' => 'inaugural-seed-investment-summit',
            'event_title' => 'Inaugural Seed Investment Summit',
            'event_venue' => 'Ezulwini, Kingdom of Eswatini',
            'event_start_at' => $eventStartsAt,
            'event_end_at' => $eventStartsAt->copy()->addDays(2),
            'locale' => 'en',
            'title' => 'Dr',
            'first_name' => fake()->firstName(),
            'surname' => fake()->lastName(),
            'gender' => 'Prefer not to say',
            'date_of_birth' => fake()->dateTimeBetween('-65 years', '-25 years'),
            'nationality' => 'Ghana',
            'national_id_number' => fake()->numerify('GHA-########'),
            'passport_number' => fake()->bothify('P#######'),
            'passport_expiry_date' => now()->addYears(3)->toDateString(),
            'issuing_country' => 'Ghana',
            'visa_required' => false,
            'organisation' => fake()->company(),
            'member_state' => 'Ghana',
            'delegation_capacity' => 'Delegate',
            'years_in_service' => 8,
            'areas_of_expertise' => 'Seed systems and agricultural policy',
            'mobile_number' => '+233 20 000 0000',
            'official_email' => fake()->unique()->safeEmail(),
            'arrival_date' => $eventStartsAt->copy()->subDay()->toDateString(),
            'departure_date' => $eventStartsAt->copy()->addDays(3)->toDateString(),
            'dietary_requirements' => 'None',
            'dinner_attendance' => true,
            'consent_version' => config('seed_summit.consent_version'),
            'consent_text_hash' => hash('sha256', (string) config('seed_summit.data_protection_notice')),
            'data_protection_accepted_at' => now(),
            'attendance_confirmed_at' => now(),
            'confirmation_email_status' => EventRegistration::EMAIL_PENDING,
        ];
    }
}
