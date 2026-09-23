<?php

namespace Tests\Feature;

use App\Models\EventRegistration;
use App\Support\EventRegistrationSearchIndex;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Tests\TestCase;

class EventRegistrationSearchIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_a_registration_builds_private_exact_and_prefix_tokens(): void
    {
        $registration = EventRegistration::factory()->create([
            'first_name' => 'Ama',
            'surname' => 'Mensah',
            'organisation' => 'African Seed Council',
            'member_state' => 'Ghana',
            'nationality' => 'Ghana',
            'gender' => 'Female',
            'delegation_capacity' => 'Delegate',
        ]);
        $searchIndex = app(EventRegistrationSearchIndex::class);
        $memberStateToken = $searchIndex->exactToken(
            EventRegistrationSearchIndex::MEMBER_STATE,
            '  GHANA  ',
        );
        $queryTokens = $searchIndex->queryTokenHashes('Am Afri');

        $this->assertSame(
            hash_hmac(
                'sha256',
                "event-registration-search:v1\0member_state\0ghana",
                (string) config('app.key'),
            ),
            $memberStateToken,
        );
        $this->assertNotSame(
            $memberStateToken,
            $searchIndex->exactToken(EventRegistrationSearchIndex::NATIONALITY, 'Ghana'),
        );
        $this->assertCount(2, $queryTokens);
        $this->assertSame(
            [
                EventRegistrationSearchIndex::NAME_PREFIX,
                EventRegistrationSearchIndex::ORGANISATION_PREFIX,
            ],
            array_keys($queryTokens[0]),
        );
        $this->assertDatabaseHas('event_registration_search_tokens', [
            'event_registration_id' => $registration->id,
            'namespace' => EventRegistrationSearchIndex::MEMBER_STATE,
            'token_hash' => $memberStateToken,
        ]);
        $this->assertDatabaseHas('event_registration_search_tokens', [
            'event_registration_id' => $registration->id,
            'namespace' => EventRegistrationSearchIndex::NAME_PREFIX,
            'token_hash' => $queryTokens[0][EventRegistrationSearchIndex::NAME_PREFIX],
        ]);
        $this->assertDatabaseHas('event_registration_search_tokens', [
            'event_registration_id' => $registration->id,
            'namespace' => EventRegistrationSearchIndex::ORGANISATION_PREFIX,
            'token_hash' => $queryTokens[1][EventRegistrationSearchIndex::ORGANISATION_PREFIX],
        ]);

        $storedTokens = DB::table('event_registration_search_tokens')
            ->where('event_registration_id', $registration->id)
            ->pluck('token_hash');

        $this->assertNotEmpty($storedTokens);
        $this->assertTrue($storedTokens->every(
            static fn (string $token): bool => preg_match('/^[a-f0-9]{64}$/', $token) === 1,
        ));
        $this->assertStringNotContainsString(
            'african',
            Str::lower(DB::table('event_registration_search_tokens')
                ->where('event_registration_id', $registration->id)
                ->get()
                ->toJson()),
        );
    }

    public function test_search_tokens_follow_searchable_changes_but_ignore_operational_updates(): void
    {
        $registration = EventRegistration::factory()->create([
            'first_name' => 'Ama',
            'surname' => 'Zulu',
            'organisation' => 'Seed Council',
            'member_state' => 'Ghana',
        ]);
        $searchIndex = app(EventRegistrationSearchIndex::class);
        $originalTokenIds = $registration->searchTokens()->orderBy('id')->pluck('id')->all();
        $amaToken = $searchIndex->queryTokenHashes('Ama')[0][EventRegistrationSearchIndex::NAME_PREFIX];
        $ghanaToken = $searchIndex->exactToken(EventRegistrationSearchIndex::MEMBER_STATE, 'Ghana');

        $registration->update(['confirmation_email_status' => EventRegistration::EMAIL_SENT]);

        $this->assertSame(
            $originalTokenIds,
            $registration->searchTokens()->orderBy('id')->pluck('id')->all(),
        );

        $registration->update([
            'first_name' => 'Linda',
            'member_state' => 'Kenya',
        ]);
        $lindaToken = $searchIndex->queryTokenHashes('Linda')[0][EventRegistrationSearchIndex::NAME_PREFIX];
        $kenyaToken = $searchIndex->exactToken(EventRegistrationSearchIndex::MEMBER_STATE, 'Kenya');

        $this->assertDatabaseMissing('event_registration_search_tokens', [
            'event_registration_id' => $registration->id,
            'namespace' => EventRegistrationSearchIndex::NAME_PREFIX,
            'token_hash' => $amaToken,
        ]);
        $this->assertDatabaseMissing('event_registration_search_tokens', [
            'event_registration_id' => $registration->id,
            'namespace' => EventRegistrationSearchIndex::MEMBER_STATE,
            'token_hash' => $ghanaToken,
        ]);
        $this->assertDatabaseHas('event_registration_search_tokens', [
            'event_registration_id' => $registration->id,
            'namespace' => EventRegistrationSearchIndex::NAME_PREFIX,
            'token_hash' => $lindaToken,
        ]);
        $this->assertDatabaseHas('event_registration_search_tokens', [
            'event_registration_id' => $registration->id,
            'namespace' => EventRegistrationSearchIndex::MEMBER_STATE,
            'token_hash' => $kenyaToken,
        ]);
    }

    public function test_rebuild_command_repairs_missing_and_stale_tokens(): void
    {
        $missing = EventRegistration::factory()->create(['first_name' => 'Ama']);
        $stale = EventRegistration::factory()->create(['first_name' => 'Oldname']);
        $searchIndex = app(EventRegistrationSearchIndex::class);
        $oldToken = $searchIndex->queryTokenHashes('Oldname')[0][EventRegistrationSearchIndex::NAME_PREFIX];

        $missing->searchTokens()->delete();
        $stale->updateQuietly(['first_name' => 'Newname']);

        $this->artisan('registrations:rebuild-search-index', ['--chunk' => 1])
            ->assertSuccessful();

        $amaToken = $searchIndex->queryTokenHashes('Ama')[0][EventRegistrationSearchIndex::NAME_PREFIX];
        $newToken = $searchIndex->queryTokenHashes('Newname')[0][EventRegistrationSearchIndex::NAME_PREFIX];

        $this->assertDatabaseHas('event_registration_search_tokens', [
            'event_registration_id' => $missing->id,
            'namespace' => EventRegistrationSearchIndex::NAME_PREFIX,
            'token_hash' => $amaToken,
        ]);
        $this->assertDatabaseMissing('event_registration_search_tokens', [
            'event_registration_id' => $stale->id,
            'namespace' => EventRegistrationSearchIndex::NAME_PREFIX,
            'token_hash' => $oldToken,
        ]);
        $this->assertDatabaseHas('event_registration_search_tokens', [
            'event_registration_id' => $stale->id,
            'namespace' => EventRegistrationSearchIndex::NAME_PREFIX,
            'token_hash' => $newToken,
        ]);
    }

    public function test_serialized_registration_omits_sensitive_fields(): void
    {
        $registration = EventRegistration::factory()->create();
        $serialized = $registration->toArray();

        foreach ([
            'title', 'first_name', 'surname', 'gender', 'date_of_birth', 'nationality',
            'national_id_number', 'passport_number', 'passport_expiry_date', 'issuing_country',
            'visa_required', 'passport_photo_path', 'passport_photo_original_name',
            'passport_scan_path', 'passport_scan_original_name', 'organisation', 'member_state',
            'delegation_capacity', 'years_in_service', 'areas_of_expertise', 'mobile_number',
            'alternative_phone', 'official_email', 'official_email_hash', 'verified_email_hash',
            'personal_email', 'emergency_contact', 'arrival_date', 'departure_date',
            'dietary_requirements', 'other_dietary_needs', 'dinner_attendance', 'consent_version',
            'consent_text_hash', 'data_protection_accepted_at', 'attendance_confirmed_at',
            'official_email_verified_at', 'confirmation_email_queued_at',
            'confirmation_email_sent_at', 'confirmation_email_failed_at',
            'receipt_email_queued_at', 'receipt_email_sent_at', 'receipt_email_failed_at',
        ] as $sensitiveField) {
            $this->assertArrayNotHasKey($sensitiveField, $serialized);
        }

        $this->assertArrayHasKey('public_id', $serialized);
        $this->assertArrayHasKey('confirmation_email_status', $serialized);
        $this->assertArrayHasKey('receipt_email_status', $serialized);
    }

    public function test_synchronization_skips_safely_before_the_token_table_is_available(): void
    {
        $registration = EventRegistration::factory()->create();
        $tokenIds = $registration->searchTokens()->orderBy('id')->pluck('id')->all();
        $searchIndex = new EventRegistrationSearchIndex;

        Schema::expects('hasTable')
            ->once()
            ->with('event_registration_search_tokens')
            ->andReturn(false);

        $searchIndex->synchronize($registration);

        $this->assertSame(
            $tokenIds,
            $registration->searchTokens()->orderBy('id')->pluck('id')->all(),
        );
    }

    public function test_exact_tokens_reject_unsupported_fields(): void
    {
        $this->expectException(InvalidArgumentException::class);

        app(EventRegistrationSearchIndex::class)->exactToken('official_email', 'delegate@example.test');
    }
}
