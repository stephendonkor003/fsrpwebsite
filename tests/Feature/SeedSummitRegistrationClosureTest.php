<?php

namespace Tests\Feature;

use App\Jobs\SendSeedSummitRegistrationReceipt;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\HomeSection;
use App\Models\Slide;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class SeedSummitRegistrationClosureTest extends TestCase
{
    use RefreshDatabase;

    #[TestWith(['en', 'Applications are closed'])]
    #[TestWith(['fr', 'Les candidatures sont closes'])]
    #[TestWith(['ar', 'أُغلق باب التقديم'])]
    #[TestWith(['pt', 'As candidaturas estão encerradas'])]
    #[TestWith(['es', 'Las solicitudes están cerradas'])]
    #[TestWith(['sw', 'Maombi yamefungwa'])]
    public function test_registration_is_closed_by_default_in_every_supported_language(string $locale, string $title): void
    {
        $this->assertFalse(config('seed_summit.registration_open'));
        $this->summit();

        $this->get(route('seed-summit.registration.create', ['locale' => $locale]))
            ->assertViewIs('site.seed-summit.closed')
            ->assertHeader('Cache-Control', 'max-age=0, no-store, private')
            ->assertSee($title)
            ->assertDontSee('name="passport_number"', false)
            ->assertDontSee('data-registration-form', false)
            ->assertDontSee('seed-summit-registration.js', false)
            ->assertDontSee(__('ui.actions.register_now', locale: $locale));
    }

    #[TestWith(['en'])]
    #[TestWith(['fr'])]
    #[TestWith(['ar'])]
    #[TestWith(['pt'])]
    #[TestWith(['es'])]
    #[TestWith(['sw'])]
    public function test_closed_registration_returns_403_without_saving_uploads_or_dispatching_mail(string $locale): void
    {
        Storage::fake('local');
        Queue::fake();
        $this->summit();

        $this->post(route('seed-summit.registration.store', ['locale' => $locale]), [
            'first_name' => 'Ama',
            'surname' => 'Mensah',
            'passport_number' => 'P1234567',
            'passport_photo' => UploadedFile::fake()->image('delegate.jpg'),
            'organisation' => 'Ministry of Agriculture',
            'mobile_number' => '+233 20 123 4567',
            'official_email' => 'closed-application@example.test',
            'data_protection_declaration' => '1',
            'attendance_confirmation' => '1',
            'registration_open' => true,
        ], ['Accept' => 'application/json'])->assertForbidden()
            ->assertJsonPath('message', __('portal.registration_closed_text', locale: $locale));

        $this->assertDatabaseCount('event_registrations', 0);
        $this->assertSame([], Storage::disk('local')->allFiles());
        Queue::assertNothingPushed();
    }

    public function test_empty_submission_is_rejected_before_validation_and_private_input_is_not_flashed(): void
    {
        Queue::fake();

        $this->post(route('seed-summit.registration.store', ['locale' => 'en']))
            ->assertForbidden()
            ->assertSessionMissing('_old_input')
            ->assertSessionMissing('seed_summit.encrypted_form_input');

        $this->assertDatabaseCount('event_registrations', 0);
        Queue::assertNothingPushed();
    }

    #[TestWith(['en'])]
    #[TestWith(['fr'])]
    #[TestWith(['ar'])]
    #[TestWith(['pt'])]
    #[TestWith(['es'])]
    #[TestWith(['sw'])]
    public function test_public_home_and_event_pages_show_closure_and_suppress_internal_and_external_application_links(string $locale): void
    {
        $summit = $this->summit();
        HomeSection::query()->create(['key' => 'hero', 'label' => 'Hero', 'is_active' => true, 'sort_order' => 0]);
        HomeSection::query()->create(['key' => 'cta', 'label' => 'Participation', 'is_active' => true, 'sort_order' => 1]);
        Slide::query()->create([
            'title' => ['en' => 'Inaugural Seed Investment Summit'],
            'button_url' => '/events/'.$summit->slug,
            'is_active' => true,
            'sort_order' => 0,
        ]);
        $externalUrl = 'https://registration.example.test/closed-summit';
        $summit->update(['registration_url' => $externalUrl]);

        foreach ([route('home', $locale), route('events.show', [$locale, $summit->slug])] as $url) {
            $this->get($url)
                ->assertSee(__('portal.registration_closed', locale: $locale))
                ->assertDontSee('/'.$locale.'/events/'.$summit->slug.'/register', false)
                ->assertDontSee($externalUrl, false)
                ->assertDontSee(__('ui.actions.register_now', locale: $locale));
        }
    }

    public function test_other_events_keep_their_registration_links_while_summit_registration_is_closed(): void
    {
        $otherEvent = Event::factory()->make(['registration_url' => 'https://events.example.test/other-event']);

        $this->assertSame('https://events.example.test/other-event', $otherEvent->registrationUrlForLocale('en'));
    }

    public function test_existing_applicant_receipt_and_email_verification_remain_available_after_closure(): void
    {
        Queue::fake([SendSeedSummitRegistrationReceipt::class]);
        $this->freezeTime();
        $registration = EventRegistration::factory()->for($this->summit())->create();
        $parameters = ['locale' => 'en', 'registration' => $registration];
        $receiptUrl = URL::temporarySignedRoute('seed-summit.registrations.show', now()->addMinutes(30), $parameters);
        $verifyUrl = URL::temporarySignedRoute('seed-summit.registrations.verify-email', now()->addMinutes(30), $parameters);

        $this->withSession(['seed_summit.receipts.'.$registration->public_id => now()->addMinutes(30)->timestamp])
            ->get($receiptUrl)
            ->assertViewIs('site.seed-summit.confirmation')
            ->assertViewHas('registration', fn (EventRegistration $shown): bool => $shown->is($registration));

        $this->post($verifyUrl)->assertSee('Official email confirmed.');

        $this->assertNotNull($registration->refresh()->official_email_verified_at);
        Queue::assertPushed(SendSeedSummitRegistrationReceipt::class);
    }

    private function summit(): Event
    {
        $this->freezeTime();

        return Event::factory()->create([
            'slug' => config('seed_summit.event_slug'),
            'title' => ['en' => 'Inaugural Seed Investment Summit'],
            'registration_url' => '/events/inaugural-seed-investment-summit/register',
            'is_featured' => true,
        ]);
    }
}
