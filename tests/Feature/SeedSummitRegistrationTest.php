<?php

namespace Tests\Feature;

use App\Jobs\SendSeedSummitRegistrationConfirmation;
use App\Jobs\SendSeedSummitRegistrationReceipt;
use App\Mail\SeedSummitRegistrationConfirmation;
use App\Mail\SeedSummitRegistrationReceipt;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Support\SeedSummitRegistrationPdf;
use App\Support\UnicodePdfFont;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class SeedSummitRegistrationTest extends TestCase
{
    use RefreshDatabase;

    private Event $event;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo('2026-09-21 12:00:00');
        $this->event = Event::factory()->create([
            'slug' => config('seed_summit.event_slug'),
            'title' => ['en' => 'Inaugural Seed Investment Summit'],
            'venue' => ['en' => 'Ezulwini, Kingdom of Eswatini'],
            'start_at' => '2026-10-05 09:00:00',
            'end_at' => '2026-10-07 17:00:00',
            'registration_url' => '/events/inaugural-seed-investment-summit/register',
            'image' => '/images/seed-investment-summit/seed-investment-summit-2026.jpeg',
            'is_published' => true,
            'is_featured' => true,
        ]);
    }

    public function test_registration_page_renders_the_delegate_form_and_event_flyer(): void
    {
        $this->get(route('seed-summit.registration.create', ['locale' => 'en']))
            ->assertOk()
            ->assertSee('Inaugural Seed Investment Summit')
            ->assertSee('name="passport_number"', false)
            ->assertSee('name="passport_photo" type="file" accept="image/jpeg,image/png,image/webp" required', false)
            ->assertSee('Delegate Profile Photo')
            ->assertSee('name="official_email"', false)
            ->assertSee('name="data_protection_declaration"', false)
            ->assertSee('/images/seed-investment-summit/seed-investment-summit-2026.jpeg', false)
            ->assertSee('class="seed-event-hero-media"', false)
            ->assertDontSee('class="seed-event-flyer"', false);
    }

    public function test_all_55_au_member_states_are_available_in_each_registration_country_field(): void
    {
        $memberStates = array_values(array_diff(config('seed_summit.member_states'), ['Not applicable']));
        $this->assertCount(55, $memberStates);
        $this->assertCount(55, array_unique($memberStates));
        $this->assertContains('Sahrawi Arab Democratic Republic', $memberStates);

        $response = $this->get(route('seed-summit.registration.create', ['locale' => 'en']))->assertOk();
        $previous = libxml_use_internal_errors(true);
        $document = new DOMDocument;
        $document->loadHTML('<?xml encoding="utf-8" ?>'.$response->getContent(), LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        $xpath = new DOMXPath($document);

        foreach (['field-nationality', 'field-issuing-country', 'field-member-state'] as $fieldId) {
            $options = $xpath->query('//*[@id="'.$fieldId.'"]/option[@value != ""]');
            $values = [];

            foreach ($options as $option) {
                $values[] = $option->getAttribute('value');
            }

            $this->assertSame([], array_values(array_diff($memberStates, $values)), $fieldId);
            $this->assertContains('Sahrawi Arab Democratic Republic', $values, $fieldId);
        }
    }

    public function test_sahrawi_republic_is_accepted_in_all_three_registration_country_fields(): void
    {
        Storage::fake('local');
        Queue::fake();

        $this->post(route('seed-summit.registration.store', ['locale' => 'en']), $this->validPayload([
            'nationality' => 'Sahrawi Arab Democratic Republic',
            'issuing_country' => 'Sahrawi Arab Democratic Republic',
            'member_state' => 'Sahrawi Arab Democratic Republic',
        ]))->assertRedirect();

        $registration = EventRegistration::query()->sole();
        $this->assertSame('Sahrawi Arab Democratic Republic', $registration->nationality);
        $this->assertSame('Sahrawi Arab Democratic Republic', $registration->issuing_country);
        $this->assertSame('Sahrawi Arab Democratic Republic', $registration->member_state);
        Queue::assertPushed(SendSeedSummitRegistrationConfirmation::class);
    }

    public function test_delegate_can_register_and_receive_a_private_signed_receipt(): void
    {
        Storage::fake('local');
        Queue::fake();

        $response = $this->post(
            route('seed-summit.registration.store', ['locale' => 'en']),
            $this->validPayload(),
        );

        $response->assertRedirect();
        $location = (string) $response->headers->get('Location');
        $this->assertTrue(URL::hasValidSignature(Request::create($location)));

        $registration = EventRegistration::query()->sole();

        $this->assertSame('delegate@agriculture.gov.gh', $registration->official_email);
        $this->assertSame('P1234567', $registration->passport_number);
        $this->assertSame(EventRegistration::EMAIL_QUEUED, $registration->confirmation_email_status);
        $this->assertSame(EventRegistration::EMAIL_PENDING, $registration->receipt_email_status);
        $this->assertNotSame(
            'P1234567',
            DB::table('event_registrations')->where('id', $registration->id)->value('passport_number'),
        );
        $this->assertNotSame(
            '+233 20 123 4567',
            DB::table('event_registrations')->where('id', $registration->id)->value('mobile_number'),
        );
        $this->assertNotSame(
            'Ama',
            DB::table('event_registrations')->where('id', $registration->id)->value('first_name'),
        );
        $this->assertNotSame(
            'delegate@agriculture.gov.gh',
            DB::table('event_registrations')->where('id', $registration->id)->value('official_email'),
        );
        $this->assertSame(
            hash('sha256', (string) config('seed_summit.data_protection_notice')),
            $registration->consent_text_hash,
        );
        Storage::disk('local')->assertExists($registration->passport_photo_path);
        Storage::disk('local')->assertExists($registration->passport_scan_path);
        $this->assertStringNotContainsString(
            '%PDF',
            Storage::disk('local')->get($registration->passport_scan_path),
        );
        $this->assertNotSame(
            Storage::disk('local')->get($registration->passport_scan_path),
            Crypt::decryptString(Storage::disk('local')->get($registration->passport_scan_path)),
        );
        Queue::assertPushed(
            SendSeedSummitRegistrationConfirmation::class,
            fn (SendSeedSummitRegistrationConfirmation $job): bool => $job->registration->is($registration),
        );

        $this->get($location)
            ->assertOk()
            ->assertHeader('Cache-Control', 'max-age=0, no-store, private')
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow')
            ->assertSee('Ama Mensah')
            ->assertSee('P1234567')
            ->assertSee('+233 20 123 4567')
            ->assertSee('passport-photo.jpg')
            ->assertSee('passport-scan.pdf')
            ->assertSee('class="seed-registration-dialog" open', false)
            ->assertSee('<meta name="robots" content="noindex, nofollow">', false)
            ->assertSee('/events/inaugural-seed-investment-summit"', false)
            ->assertDontSee('rel="alternate" hreflang=', false)
            ->assertDontSee('data-language-trigger', false)
            ->assertDontSee('index, follow', false);
    }

    public function test_required_fields_are_validated_without_persisting_data_or_files(): void
    {
        Storage::fake('local');
        Queue::fake();

        $this->from(route('seed-summit.registration.create', ['locale' => 'en']))
            ->post(route('seed-summit.registration.store', ['locale' => 'en']), [
                'website' => 'automated submission',
            ])
            ->assertRedirect(route('seed-summit.registration.create', ['locale' => 'en']))
            ->assertSessionHasErrors([
                'first_name',
                'surname',
                'passport_number',
                'passport_photo',
                'organisation',
                'mobile_number',
                'official_email',
                'data_protection_declaration',
                'attendance_confirmation',
                'website',
            ]);

        $this->assertDatabaseCount('event_registrations', 0);
        $this->assertSame([], Storage::disk('local')->allFiles());
        $this->assertSame([], session('_old_input', []));
        $this->assertSame('Upload a clear delegate profile photo.', session('errors')?->first('passport_photo'));
        Queue::assertNothingPushed();
    }

    public function test_validation_repopulates_the_form_from_an_encrypted_one_time_payload(): void
    {
        $formUrl = route('seed-summit.registration.create', ['locale' => 'en']);
        $payload = $this->validPayload([
            'first_name' => 'Sensitive First Name',
            'passport_number' => 'PRIVATE-PASSPORT-123',
            'mobile_number' => 'invalid',
        ]);

        $this->from($formUrl)
            ->post(route('seed-summit.registration.store', ['locale' => 'en']), $payload)
            ->assertRedirect($formUrl)
            ->assertSessionHasErrors('mobile_number');

        $encrypted = session('seed_summit.encrypted_form_input');
        $this->assertIsString($encrypted);
        $this->assertStringNotContainsString('Sensitive First Name', $encrypted);
        $this->assertStringNotContainsString('PRIVATE-PASSPORT-123', $encrypted);
        $this->assertSame([], session('_old_input', []));

        $response = $this->get($formUrl);

        $response
            ->assertOk()
            ->assertHeader('Pragma', 'no-cache')
            ->assertHeader('Referrer-Policy', 'no-referrer')
            ->assertSee('value="Sensitive First Name"', false)
            ->assertSee('value="PRIVATE-PASSPORT-123"', false)
            ->assertSee('value="invalid"', false);

        foreach (['private', 'no-store', 'max-age=0'] as $cacheDirective) {
            $this->assertStringContainsString(
                $cacheDirective,
                (string) $response->headers->get('Cache-Control'),
            );
        }

        foreach (['noindex', 'nofollow'] as $robotsDirective) {
            $this->assertStringContainsString(
                $robotsDirective,
                (string) $response->headers->get('X-Robots-Tag'),
            );
        }

        $this->assertNull(session('seed_summit.encrypted_form_input'));
    }

    public function test_passport_must_remain_valid_through_the_delegate_departure(): void
    {
        $payload = $this->validPayload([
            'passport_expiry_date' => '2026-10-06',
            'departure_date' => '2026-10-08',
        ]);

        $this->post(route('seed-summit.registration.store', ['locale' => 'en']), $payload)
            ->assertSessionHasErrors('passport_expiry_date');

        $this->assertDatabaseCount('event_registrations', 0);
    }

    public function test_passport_must_remain_valid_through_the_summit_when_travel_dates_are_omitted(): void
    {
        $payload = $this->validPayload([
            'passport_expiry_date' => '2026-10-06',
            'arrival_date' => null,
            'departure_date' => null,
        ]);

        $this->post(route('seed-summit.registration.store', ['locale' => 'en']), $payload)
            ->assertSessionHasErrors('passport_expiry_date');

        $this->assertDatabaseCount('event_registrations', 0);
    }

    public function test_duplicate_unverified_email_submissions_do_not_block_or_disclose_an_existing_registration(): void
    {
        Storage::fake('local');
        Queue::fake();

        $this->post(
            route('seed-summit.registration.store', ['locale' => 'en']),
            $this->validPayload(['official_email' => 'Delegate@Agriculture.gov.gh']),
        )->assertRedirect();

        $this->post(
            route('seed-summit.registration.store', ['locale' => 'en']),
            $this->validPayload(['official_email' => 'DELEGATE@AGRICULTURE.GOV.GH']),
        )->assertRedirect()->assertSessionHasNoErrors();

        $this->assertDatabaseCount('event_registrations', 2);
        $this->assertSame(
            ['delegate@agriculture.gov.gh', 'delegate@agriculture.gov.gh'],
            EventRegistration::query()->get()->map->official_email->all(),
        );
    }

    public function test_validation_retries_do_not_exhaust_the_successful_registration_limit(): void
    {
        Storage::fake('local');
        Queue::fake();
        $formUrl = route('seed-summit.registration.create', ['locale' => 'en']);
        $storeUrl = route('seed-summit.registration.store', ['locale' => 'en']);

        for ($attempt = 1; $attempt <= 4; $attempt++) {
            $this->from($formUrl)
                ->post($storeUrl, $this->validPayload(['mobile_number' => 'invalid']))
                ->assertRedirect($formUrl)
                ->assertSessionHasErrors('mobile_number');
        }

        $this->post($storeUrl, $this->validPayload())
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('event_registrations', 1);
        Queue::assertPushed(SendSeedSummitRegistrationConfirmation::class, 1);
    }

    public function test_fourth_successful_registration_for_the_same_email_is_rate_limited(): void
    {
        Storage::fake('local');
        Queue::fake();
        $storeUrl = route('seed-summit.registration.store', ['locale' => 'en']);

        for ($registrationNumber = 1; $registrationNumber <= 3; $registrationNumber++) {
            $this->post($storeUrl, $this->validPayload([
                'passport_number' => 'P123456'.$registrationNumber,
            ]))->assertRedirect()->assertSessionHasNoErrors();
        }

        $response = $this->post($storeUrl, $this->validPayload([
            'passport_number' => 'P1234564',
        ]));

        $response
            ->assertTooManyRequests()
            ->assertHeader('Retry-After');
        $this->assertGreaterThan(0, (int) $response->headers->get('Retry-After'));
        $this->assertDatabaseCount('event_registrations', 3);
        Queue::assertPushed(SendSeedSummitRegistrationConfirmation::class, 3);
    }

    public function test_disguised_non_image_passport_photo_is_rejected(): void
    {
        $payload = $this->validPayload([
            'passport_photo' => UploadedFile::fake()->createWithContent(
                'passport-photo.jpg',
                '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>',
            ),
        ]);

        $this->post(route('seed-summit.registration.store', ['locale' => 'en']), $payload)
            ->assertSessionHasErrors('passport_photo');

        $this->assertDatabaseCount('event_registrations', 0);
    }

    public function test_receipt_and_pdf_routes_reject_missing_or_invalid_signatures(): void
    {
        $registration = EventRegistration::factory()->for($this->event)->create();

        $this->get(route('seed-summit.registrations.show', [
            'locale' => 'en',
            'registration' => $registration,
        ]))->assertForbidden();
        $this->get(route('seed-summit.registrations.pdf', [
            'locale' => 'en',
            'registration' => $registration,
        ]))->assertForbidden();

        $signedUrl = URL::temporarySignedRoute(
            'seed-summit.registrations.show',
            now()->addHour(),
            ['locale' => 'en', 'registration' => $registration],
        );
        $this->get($signedUrl)->assertForbidden();

        $expiredUrl = URL::temporarySignedRoute(
            'seed-summit.registrations.show',
            now()->subMinute(),
            ['locale' => 'en', 'registration' => $registration],
        );
        $this->withSession([
            'seed_summit' => ['receipts' => [$registration->public_id => now()->addHour()->timestamp]],
        ])->get($expiredUrl)->assertForbidden();
    }

    public function test_signed_pdf_download_contains_the_complete_delegate_record(): void
    {
        $registration = EventRegistration::factory()->for($this->event)->create([
            'first_name' => 'Ama',
            'surname' => 'Mensah',
            'passport_number' => 'P1234567',
        ]);
        $url = URL::temporarySignedRoute(
            'seed-summit.registrations.pdf',
            now()->addHour(),
            ['locale' => 'en', 'registration' => $registration],
        );

        $response = $this->withSession([
            'seed_summit' => ['receipts' => [$registration->public_id => now()->addHour()->timestamp]],
        ])->get($url)
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('Cache-Control', 'max-age=0, no-store, private')
            ->assertDownload('seed-summit-registration-'.$registration->public_id.'.pdf');

        $pdf = (string) $response->getContent();
        $this->assertStringStartsWith('%PDF-1.4', $pdf);
        $this->assertStringContainsString('/Subtype /Type0', $pdf);
        $this->assertStringContainsString('/FontFile2', $pdf);
        $this->assertStringContainsString($this->pdfHex('First Name: Ama'), $pdf);
        $this->assertStringContainsString($this->pdfHex('Surname: Mensah'), $pdf);
        $this->assertStringContainsString($this->pdfHex('Passport Number: P1234567'), $pdf);
    }

    public function test_pdf_neatly_embeds_the_encrypted_delegate_profile_photo(): void
    {
        Storage::fake('local');
        $registration = EventRegistration::factory()->for($this->event)->create([
            'first_name' => 'Ama',
            'surname' => 'Mensah',
        ]);
        $photo = UploadedFile::fake()->image('delegate-profile.png', 1200, 500);
        $photoContents = file_get_contents($photo->getRealPath());
        $photoPath = 'event-registrations/'.$registration->public_id.'/profile-photo.enc';
        $this->assertIsString($photoContents);
        Storage::disk('local')->put($photoPath, Crypt::encryptString($photoContents));
        $registration->update([
            'passport_photo_path' => $photoPath,
            'passport_photo_original_name' => 'delegate-profile.png',
        ]);

        $pdf = app(SeedSummitRegistrationPdf::class)->render($registration->fresh());

        $this->assertStringStartsWith('%PDF-1.4', $pdf);
        $this->assertStringContainsString('/Subtype /Image', $pdf);
        $this->assertStringContainsString('/Width 280 /Height 350', $pdf);
        $this->assertStringContainsString('/Filter /DCTDecode', $pdf);
        $this->assertStringContainsString('/Photo Do', $pdf);
        $this->assertStringContainsString(
            $this->pdfHex('Delegate Profile Photo: delegate-profile.png'),
            $pdf,
        );
        $this->assertStringNotContainsString($photoContents, $pdf);
        $this->assertLessThan(
            (int) config('services.microsoft_graph.max_attachment_bytes', 3_000_000),
            strlen($pdf),
        );
    }

    public function test_pdf_uses_a_professional_placeholder_for_a_legacy_registration_without_a_photo(): void
    {
        $registration = EventRegistration::factory()->for($this->event)->create([
            'passport_photo_path' => null,
            'passport_photo_original_name' => null,
        ]);

        $pdf = app(SeedSummitRegistrationPdf::class)->render($registration);

        $this->assertStringStartsWith('%PDF-1.4', $pdf);
        $this->assertStringNotContainsString('/Subtype /Image', $pdf);
        $this->assertStringContainsString($this->pdfHex('PHOTO'), $pdf);
        $this->assertStringContainsString(
            $this->pdfHex('Privacy notice: This record contains personal data.'),
            $pdf,
        );
    }

    public function test_pdf_shapes_arabic_preserves_logical_text_and_segments_mixed_font_runs(): void
    {
        $registration = EventRegistration::factory()->for($this->event)->create([
            'first_name' => 'أحمد ሰላም',
            'surname' => 'Mensah',
        ]);

        $pdf = app(SeedSummitRegistrationPdf::class)->render($registration);
        $arabicVisual = implode('', array_map(
            static fn (int $codePoint): string => mb_chr($codePoint, 'UTF-8'),
            [0xFEAA, 0xFEE4, 0xFEA3, 0xFE83],
        ));

        $this->assertStringContainsString('/BaseFont /DejaVuSans', $pdf);
        $this->assertStringContainsString('/BaseFont /AbyssinicaSIL', $pdf);
        $this->assertStringContainsString(
            '/ActualText <FEFF'.$this->pdfHex('First Name: أحمد ሰላም').'>',
            $pdf,
        );
        $this->assertStringContainsString(
            '<'.$this->pdfHex('First Name: '.$arabicVisual.' ')."> Tj\n/F3 9 Tf\n<".$this->pdfHex('ሰላም').'> Tj',
            $pdf,
        );
        $this->assertStringContainsString('<FEAA> <062F>', $pdf);
        $this->assertStringContainsString('<FEE4> <0645>', $pdf);
        $this->assertStringContainsString('<FEA3> <062D>', $pdf);
        $this->assertStringContainsString('<FE83> <0623>', $pdf);
        $this->assertStringNotContainsString('<'.$this->pdfHex('First Name: أحمد ሰላም').'> Tj', $pdf);
        $this->assertNotSame(0, app(UnicodePdfFont::class)->glyphId(0x0623));
        $this->assertNotSame(
            0,
            (new UnicodePdfFont('fonts/AbyssinicaSIL-Regular.ttf', 'AbyssinicaSIL'))->glyphId(0x1230),
        );
    }

    public function test_pdf_wraps_wide_unbroken_text_by_rendered_glyph_width(): void
    {
        $wideValue = str_repeat('W', 180);
        $registration = EventRegistration::factory()->for($this->event)->create([
            'first_name' => 'Ama',
            'surname' => 'Mensah',
            'organisation' => 'Seed Office',
            'areas_of_expertise' => $wideValue,
        ]);

        $pdf = app(SeedSummitRegistrationPdf::class)->render($registration);
        preg_match_all('/<([0-9A-F]+)> Tj/', $pdf, $matches);
        $wideLines = [];

        foreach ($matches[1] as $encodedLine) {
            $binary = hex2bin($encodedLine);

            if ($binary === false) {
                continue;
            }

            $line = mb_convert_encoding($binary, 'UTF-8', 'UTF-16BE');

            if (str_contains($line, 'W')) {
                $wideLines[] = $line;
            }
        }

        $this->assertGreaterThan(1, count($wideLines));
        $this->assertSame(
            $wideValue,
            implode('', array_map(
                static fn (string $line): string => preg_replace('/[^W]/', '', $line) ?? '',
                $wideLines,
            )),
        );

        $font = app(UnicodePdfFont::class);

        foreach ($wideLines as $line) {
            $width = array_sum(array_map(
                fn (int $codePoint): int => $font->width($codePoint),
                $font->codePoints($line),
            )) * 9 / 1000;

            $this->assertLessThanOrEqual(499.0, $width);
        }
    }

    public function test_confirmation_job_sends_a_privacy_safe_acknowledgement_and_marks_it_sent(): void
    {
        Mail::fake();
        $registration = EventRegistration::factory()->for($this->event)->create([
            'first_name' => 'PrivateFirstName',
            'surname' => 'PrivateSurname',
            'organisation' => 'Private Organisation',
            'member_state' => 'Ghana',
            'delegation_capacity' => 'Delegate',
            'national_id_number' => 'GHA-PRIVATE-ID',
            'passport_number' => 'PRIVATE-PASSPORT',
            'mobile_number' => '+233 20 999 9999',
            'personal_email' => 'private@example.test',
        ]);

        (new SendSeedSummitRegistrationConfirmation($registration))->handle();
        (new SendSeedSummitRegistrationConfirmation($registration))->handle();

        Mail::assertSent(
            SeedSummitRegistrationConfirmation::class,
            fn (SeedSummitRegistrationConfirmation $mail): bool => $mail->hasTo($registration->official_email),
        );
        Mail::assertSent(SeedSummitRegistrationConfirmation::class, 1);
        $this->assertSame(EventRegistration::EMAIL_SENT, $registration->fresh()->confirmation_email_status);

        $mail = new SeedSummitRegistrationConfirmation($registration);
        $mail->assertHasSubject('Registration received - Confirm your email - Inaugural Seed Investment Summit');
        $html = $mail->render();
        $content = $mail->content();
        $text = view($content->text, $content->with)->render();
        $this->assertStringContainsString($registration->public_id, $html);
        $this->assertStringNotContainsString('PrivateFirstName', $html);
        $this->assertStringNotContainsString('PrivateSurname', $html);
        $this->assertStringNotContainsString('Private Organisation', $html);
        $this->assertStringNotContainsString('GHA-PRIVATE-ID', $html);
        $this->assertStringNotContainsString('PRIVATE-PASSPORT', $html);
        $this->assertStringNotContainsString('+233 20 999 9999', $html);
        $this->assertStringNotContainsString('private@example.test', $html);
        $this->assertStringContainsString('/verify-email?', $html);
        $this->assertStringContainsString('Resilient Seed Systems for a Food Secure Africa', $html);
        $this->assertStringContainsString('Step 1 of 2', $html);
        $this->assertStringContainsString('alt="African Union"', $html);
        $this->assertStringNotContainsString('CAADP', $html);
        $this->assertStringNotContainsString('/pdf?', $html);
        $this->assertStringNotContainsString('View registration', $html);
        $this->assertStringContainsString('&signature=', $text);
        $this->assertStringNotContainsString('&amp;signature=', $text);
        $this->assertStringContainsString('Resilient Seed Systems for a Food Secure Africa', $text);
        $this->assertSame([], (new SeedSummitRegistrationConfirmation($registration))->attachments());
    }

    public function test_mail_job_timeout_lease_and_database_retry_window_are_safely_ordered(): void
    {
        $registration = EventRegistration::factory()->for($this->event)->create();
        $jobTimeout = (new SendSeedSummitRegistrationConfirmation($registration))->timeout;
        $leaseSeconds = (int) config('seed_summit.email_send_lease_seconds');
        $retryAfter = (int) config('queue.connections.database.retry_after');

        $this->assertSame(170, $jobTimeout);
        $this->assertSame(170, (new SendSeedSummitRegistrationReceipt($registration))->timeout);
        $this->assertGreaterThan($jobTimeout, $leaseSeconds);
        $this->assertGreaterThan($leaseSeconds, $retryAfter);
    }

    public function test_confirmation_job_releases_a_fresh_sending_claim_instead_of_losing_the_email(): void
    {
        Mail::fake();
        $registration = EventRegistration::factory()->for($this->event)->create([
            'confirmation_email_status' => EventRegistration::EMAIL_SENDING,
            'confirmation_email_queued_at' => now(),
        ]);
        $job = (new SendSeedSummitRegistrationConfirmation($registration))->withFakeQueueInteractions();

        $job->handle();

        $job->assertReleased();
        Mail::assertNothingSent();
        $this->assertSame(EventRegistration::EMAIL_SENDING, $registration->fresh()->confirmation_email_status);
    }

    public function test_confirmation_job_reclaims_an_expired_sending_lease_after_worker_loss(): void
    {
        Mail::fake();
        $registration = EventRegistration::factory()->for($this->event)->create([
            'confirmation_email_status' => EventRegistration::EMAIL_SENDING,
            'confirmation_email_queued_at' => now()->subSeconds(
                (int) config('seed_summit.email_send_lease_seconds') + 1,
            ),
        ]);

        (new SendSeedSummitRegistrationConfirmation($registration))->handle();

        Mail::assertSent(SeedSummitRegistrationConfirmation::class, 1);
        $this->assertSame(EventRegistration::EMAIL_SENT, $registration->fresh()->confirmation_email_status);
    }

    public function test_delegate_can_retry_a_failed_verification_email_from_the_private_receipt(): void
    {
        Queue::fake();
        $registration = EventRegistration::factory()->for($this->event)->create([
            'confirmation_email_status' => EventRegistration::EMAIL_FAILED,
            'confirmation_email_failed_at' => now(),
        ]);
        $receiptSession = [
            'seed_summit.receipts.'.$registration->public_id => now()->addMinutes(30)->timestamp,
        ];
        $showUrl = URL::temporarySignedRoute(
            'seed-summit.registrations.show',
            now()->addMinutes(30),
            ['locale' => 'en', 'registration' => $registration],
        );
        $resendUrl = URL::temporarySignedRoute(
            'seed-summit.registrations.resend-confirmation',
            now()->addMinutes(30),
            ['locale' => 'en', 'registration' => $registration],
        );

        $this->withSession($receiptSession)
            ->get($showUrl)
            ->assertOk()
            ->assertSee('Resend verification email')
            ->assertSee('/resend-confirmation?', false);

        $this->post($resendUrl)->assertRedirect();

        $registration->refresh();
        $this->assertSame(EventRegistration::EMAIL_QUEUED, $registration->confirmation_email_status);
        $this->assertNull($registration->confirmation_email_failed_at);
        Queue::assertPushed(SendSeedSummitRegistrationConfirmation::class, 1);
    }

    public function test_reconciliation_recovers_pending_and_stale_registration_emails(): void
    {
        Queue::fake();
        $pendingVerified = EventRegistration::factory()->for($this->event)->create([
            'official_email' => 'verified@example.test',
        ]);
        $pendingVerified->update([
            'official_email_verified_at' => now(),
            'verified_email_hash' => $pendingVerified->official_email_hash,
        ]);
        $staleAcknowledgement = EventRegistration::factory()->for($this->event)->create([
            'confirmation_email_status' => EventRegistration::EMAIL_QUEUED,
            'confirmation_email_queued_at' => now()->subSeconds(
                (int) config('seed_summit.email_dispatch_stale_seconds') + 1,
            ),
        ]);
        $freshAcknowledgement = EventRegistration::factory()->for($this->event)->create([
            'confirmation_email_status' => EventRegistration::EMAIL_QUEUED,
            'confirmation_email_queued_at' => now(),
        ]);

        $this->artisan('seed-summit:reconcile-registration-emails')
            ->assertSuccessful();

        Queue::assertPushed(SendSeedSummitRegistrationConfirmation::class, 2);
        Queue::assertPushed(
            SendSeedSummitRegistrationConfirmation::class,
            fn (SendSeedSummitRegistrationConfirmation $job): bool => $job->registration->is($pendingVerified),
        );
        Queue::assertPushed(
            SendSeedSummitRegistrationConfirmation::class,
            fn (SendSeedSummitRegistrationConfirmation $job): bool => $job->registration->is($staleAcknowledgement),
        );
        Queue::assertNotPushed(
            SendSeedSummitRegistrationConfirmation::class,
            fn (SendSeedSummitRegistrationConfirmation $job): bool => $job->registration->is($freshAcknowledgement),
        );
        Queue::assertPushed(
            SendSeedSummitRegistrationReceipt::class,
            fn (SendSeedSummitRegistrationReceipt $job): bool => $job->registration->is($pendingVerified),
        );
        Queue::assertPushed(SendSeedSummitRegistrationReceipt::class, 1);
    }

    public function test_failed_job_callbacks_do_not_downgrade_a_sent_email(): void
    {
        $registration = EventRegistration::factory()->for($this->event)->create([
            'confirmation_email_status' => EventRegistration::EMAIL_SENT,
            'confirmation_email_sent_at' => now(),
            'receipt_email_status' => EventRegistration::EMAIL_SENT,
            'receipt_email_sent_at' => now(),
        ]);

        (new SendSeedSummitRegistrationConfirmation($registration))->failed(
            new \RuntimeException('Stale acknowledgement failure'),
        );
        (new SendSeedSummitRegistrationReceipt($registration))->failed(
            new \RuntimeException('Stale receipt failure'),
        );

        $registration->refresh();
        $this->assertSame(EventRegistration::EMAIL_SENT, $registration->confirmation_email_status);
        $this->assertSame(EventRegistration::EMAIL_SENT, $registration->receipt_email_status);
        $this->assertNull($registration->confirmation_email_failed_at);
        $this->assertNull($registration->receipt_email_failed_at);
    }

    public function test_receipt_job_refuses_to_email_an_unverified_registration(): void
    {
        Mail::fake();
        $registration = EventRegistration::factory()->for($this->event)->create();
        $mismatchedRegistration = EventRegistration::factory()->for($this->event)->create([
            'official_email' => 'mismatched@example.test',
            'official_email_verified_at' => now(),
            'verified_email_hash' => EventRegistration::emailHash('different@example.test'),
        ]);

        $this->app->call([
            new SendSeedSummitRegistrationReceipt($registration),
            'handle',
        ]);
        $this->app->call([
            new SendSeedSummitRegistrationReceipt($mismatchedRegistration),
            'handle',
        ]);

        Mail::assertNothingSent();
        $this->assertSame(EventRegistration::EMAIL_PENDING, $registration->fresh()->receipt_email_status);
        $this->assertSame(
            EventRegistration::EMAIL_PENDING,
            $mismatchedRegistration->fresh()->receipt_email_status,
        );
    }

    public function test_receipt_job_sends_the_complete_pdf_once_after_email_verification(): void
    {
        Mail::fake();
        $registration = EventRegistration::factory()->for($this->event)->create([
            'first_name' => '<script>alert(1)</script>',
            'surname' => 'Mensah',
            'official_email' => 'verified@example.test',
            'event_start_at' => '2026-10-05 09:00:00',
            'event_end_at' => '2026-10-07 17:00:00',
            'event_venue' => 'Palazzo Convention Centre, Ezulwini, Eswatini',
        ]);
        $registration->update([
            'official_email_verified_at' => now(),
            'verified_email_hash' => $registration->official_email_hash,
            'receipt_email_status' => EventRegistration::EMAIL_QUEUED,
            'receipt_email_queued_at' => now(),
        ]);
        $pdf = app(SeedSummitRegistrationPdf::class)->render($registration->fresh());
        $job = new SendSeedSummitRegistrationReceipt($registration);

        $this->app->call([$job, 'handle']);
        $this->app->call([$job, 'handle']);

        Mail::assertSent(
            SeedSummitRegistrationReceipt::class,
            function (SeedSummitRegistrationReceipt $mail) use ($registration, $pdf): bool {
                $mail->assertHasAttachedData(
                    $pdf,
                    'seed-summit-registration-'.$registration->public_id.'.pdf',
                    ['mime' => 'application/pdf'],
                );

                return $mail->hasTo('verified@example.test');
            },
        );
        Mail::assertSent(SeedSummitRegistrationReceipt::class, 1);
        $this->assertSame(EventRegistration::EMAIL_SENT, $registration->fresh()->receipt_email_status);
        $this->assertNotNull($registration->fresh()->receipt_email_sent_at);

        $this->assertLessThan(
            (int) config('services.microsoft_graph.max_attachment_bytes', 3_000_000),
            strlen($pdf),
        );
        $mail = new SeedSummitRegistrationReceipt($registration->fresh(), $pdf);
        $mail->assertHasSubject(
            'Registration acknowledgement - Inaugural Seed Investment Summit - '.$registration->public_id,
        );
        $html = $mail->render();
        $content = $mail->content();
        $text = view($content->text, $content->with)->render();

        $this->assertStringContainsString('Registration acknowledgement', $html);
        $this->assertStringContainsString('Email confirmed', $html);
        $this->assertStringContainsString('Complete registration copy attached', $html);
        $this->assertStringContainsString('profile photo', $html);
        $this->assertStringContainsString('OFFICE OF THE COMMISSIONER - ARBE', $html);
        $this->assertStringNotContainsString('CAADP', $html);
        $this->assertStringContainsString('5-7 October 2026', $html);
        $this->assertStringContainsString('Resilient Seed Systems for a Food Secure Africa', $html);
        $this->assertStringContainsString(
            $this->pdfHex('Inaugural Seed Investment Summit Registration'),
            $pdf,
        );
        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
        $this->assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $html);
        $this->assertStringContainsString('alert(1) Mensah', $text);
        $this->assertStringNotContainsString('&lt;script&gt;', $text);
        $mail->assertHasAttachedData(
            $pdf,
            'seed-summit-registration-'.$registration->public_id.'.pdf',
            ['mime' => 'application/pdf'],
        );
    }

    public function test_receipt_job_releases_a_fresh_sending_claim(): void
    {
        Mail::fake();
        $registration = EventRegistration::factory()->for($this->event)->create([
            'official_email' => 'verified@example.test',
        ]);
        $registration->update([
            'official_email_verified_at' => now(),
            'verified_email_hash' => $registration->official_email_hash,
            'receipt_email_status' => EventRegistration::EMAIL_SENDING,
            'receipt_email_queued_at' => now(),
        ]);
        $job = (new SendSeedSummitRegistrationReceipt($registration))->withFakeQueueInteractions();

        $this->app->call([$job, 'handle']);

        $job->assertReleased();
        Mail::assertNothingSent();
        $this->assertSame(EventRegistration::EMAIL_SENDING, $registration->fresh()->receipt_email_status);
    }

    public function test_verified_registration_can_requeue_a_failed_receipt_email(): void
    {
        Queue::fake();
        $registration = EventRegistration::factory()->for($this->event)->create([
            'official_email' => 'verified@example.test',
        ]);
        $registration->update([
            'official_email_verified_at' => now(),
            'verified_email_hash' => $registration->official_email_hash,
            'receipt_email_status' => EventRegistration::EMAIL_FAILED,
            'receipt_email_failed_at' => now(),
        ]);
        $url = URL::temporarySignedRoute(
            'seed-summit.registrations.verify-email.show',
            now()->addHour(),
            ['locale' => 'en', 'registration' => $registration],
        );

        $this->get($url)
            ->assertOk()
            ->assertSee('Retry PDF email')
            ->assertSee('method="POST"', false);
        Queue::assertNothingPushed();

        $this->post($url)
            ->assertOk()
            ->assertSee('Official email already confirmed')
            ->assertSee('complete registration PDF is being prepared');

        $registration->refresh();
        $this->assertSame(EventRegistration::EMAIL_QUEUED, $registration->receipt_email_status);
        $this->assertNull($registration->receipt_email_failed_at);
        Queue::assertPushed(SendSeedSummitRegistrationReceipt::class, 1);
    }

    public function test_signed_email_verification_requires_an_explicit_post_without_exposing_registration_data(): void
    {
        Queue::fake();
        $registration = EventRegistration::factory()->for($this->event)->create([
            'passport_number' => 'PRIVATE-PASSPORT',
            'mobile_number' => '+233 20 999 9999',
        ]);
        $url = URL::temporarySignedRoute(
            'seed-summit.registrations.verify-email.show',
            now()->addHour(),
            ['locale' => 'en', 'registration' => $registration],
        );

        $this->get($url)
            ->assertOk()
            ->assertHeader('Cache-Control', 'max-age=0, no-store, private')
            ->assertSee('Confirm your official email')
            ->assertSee('method="POST"', false)
            ->assertDontSee('PRIVATE-PASSPORT')
            ->assertDontSee('+233 20 999 9999');

        $this->assertNull($registration->fresh()->official_email_verified_at);
        Queue::assertNothingPushed();

        $this->post($url)
            ->assertOk()
            ->assertSee('Official email confirmed')
            ->assertDontSee('PRIVATE-PASSPORT')
            ->assertDontSee('+233 20 999 9999');

        $registration->refresh();
        $this->assertNotNull($registration->official_email_verified_at);
        $this->assertTrue($registration->hasVerifiedOfficialEmail());
        $this->assertSame(EventRegistration::EMAIL_QUEUED, $registration->receipt_email_status);
        Queue::assertPushed(
            SendSeedSummitRegistrationReceipt::class,
            fn (SendSeedSummitRegistrationReceipt $job): bool => $job->registration->is($registration),
        );

        $this->post($url)->assertOk()->assertSee('Official email already confirmed');
        Queue::assertPushed(SendSeedSummitRegistrationReceipt::class, 1);
    }

    public function test_only_one_registration_can_verify_the_same_official_email_for_an_event(): void
    {
        Queue::fake();
        $first = EventRegistration::factory()->for($this->event)->create([
            'official_email' => 'delegate@example.test',
        ]);
        $second = EventRegistration::factory()->for($this->event)->create([
            'official_email' => 'DELEGATE@example.test',
        ]);

        foreach ([$first, $second] as $registration) {
            $url = URL::temporarySignedRoute(
                'seed-summit.registrations.verify-email.show',
                now()->addHour(),
                ['locale' => 'en', 'registration' => $registration],
            );
            $this->get($url)->assertOk();
            $response = $this->post($url)->assertOk();

            if ($registration->is($second)) {
                $response->assertSee('Official email already in use');
            }
        }

        $this->assertNotNull($first->fresh()->official_email_verified_at);
        $this->assertNull($second->fresh()->official_email_verified_at);
        $this->assertSame(EventRegistration::EMAIL_PENDING, $second->fresh()->receipt_email_status);
        Queue::assertPushed(
            SendSeedSummitRegistrationReceipt::class,
            fn (SendSeedSummitRegistrationReceipt $job): bool => $job->registration->is($first),
        );
        Queue::assertPushed(SendSeedSummitRegistrationReceipt::class, 1);
    }

    public function test_deleting_a_registration_removes_its_private_document_directory(): void
    {
        Storage::fake('local');
        $registration = EventRegistration::factory()->for($this->event)->create([
            'passport_photo_path' => 'event-registrations/test-reference/photo.enc',
            'passport_scan_path' => 'event-registrations/test-reference/scan.enc',
        ]);
        $registration->update(['public_id' => 'test-reference']);
        Storage::disk('local')->put($registration->passport_photo_path, 'encrypted-photo');
        Storage::disk('local')->put($registration->passport_scan_path, 'encrypted-scan');

        $registration->delete();

        Storage::disk('local')->assertMissing('event-registrations/test-reference/photo.enc');
        Storage::disk('local')->assertMissing('event-registrations/test-reference/scan.enc');
    }

    public function test_pruning_removes_stale_unverified_and_post_retention_records_with_their_files(): void
    {
        Storage::fake('local');
        $staleUnverified = EventRegistration::factory()->for($this->event)->create([
            'public_id' => 'stale-unverified',
            'created_at' => now()->subDays(31),
            'updated_at' => now()->subDays(31),
            'passport_scan_path' => 'event-registrations/stale-unverified/scan.enc',
        ]);
        $pastRetention = EventRegistration::factory()->for($this->event)->create([
            'public_id' => 'past-retention',
            'event_end_at' => now()->subDays(366),
            'official_email_verified_at' => now()->subDays(360),
            'verified_email_hash' => EventRegistration::emailHash('past-retention@example.test'),
            'passport_scan_path' => 'event-registrations/past-retention/scan.enc',
        ]);
        $recent = EventRegistration::factory()->for($this->event)->create([
            'public_id' => 'recent-registration',
            'passport_scan_path' => 'event-registrations/recent-registration/scan.enc',
        ]);

        foreach ([$staleUnverified, $pastRetention, $recent] as $registration) {
            Storage::disk('local')->put($registration->passport_scan_path, 'encrypted-scan');
        }

        $this->artisan('model:prune', [
            '--model' => [EventRegistration::class],
            '--no-interaction' => true,
        ])->assertSuccessful();

        $this->assertModelMissing($staleUnverified);
        $this->assertModelMissing($pastRetention);
        $this->assertModelExists($recent);
        Storage::disk('local')->assertMissing('event-registrations/stale-unverified/scan.enc');
        Storage::disk('local')->assertMissing('event-registrations/past-retention/scan.enc');
        Storage::disk('local')->assertExists('event-registrations/recent-registration/scan.enc');
    }

    /** @param array<string, mixed> $overrides */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Dr',
            'first_name' => 'Ama',
            'surname' => 'Mensah',
            'gender' => 'Female',
            'date_of_birth' => '1982-04-14',
            'nationality' => 'Ghana',
            'national_id_number' => 'GHA-12345678',
            'passport_number' => 'P1234567',
            'passport_expiry_date' => '2030-04-14',
            'issuing_country' => 'Ghana',
            'visa_required' => '1',
            'passport_photo' => UploadedFile::fake()->image('passport-photo.jpg', 300, 400),
            'passport_scan' => UploadedFile::fake()->create('passport-scan.pdf', 100, 'application/pdf'),
            'organisation' => 'Ministry of Food and Agriculture',
            'member_state' => 'Ghana',
            'delegation_capacity' => 'Delegate',
            'years_in_service' => 12,
            'areas_of_expertise' => 'Seed policy and crop resilience',
            'mobile_number' => '+233 20 123 4567',
            'alternative_phone' => '+233 24 765 4321',
            'official_email' => 'delegate@agriculture.gov.gh',
            'personal_email' => 'ama@example.test',
            'emergency_contact' => 'Kofi Mensah, +233 55 123 4567',
            'arrival_date' => '2026-10-04',
            'departure_date' => '2026-10-08',
            'dietary_requirements' => 'Other',
            'other_dietary_needs' => 'Gluten free',
            'dinner_attendance' => '1',
            'data_protection_declaration' => '1',
            'attendance_confirmation' => '1',
            'website' => '',
        ], $overrides);
    }

    private function pdfHex(string $value): string
    {
        return strtoupper(bin2hex(mb_convert_encoding($value, 'UTF-16BE', 'UTF-8')));
    }
}
