<?php

namespace Tests\Feature;

use App\Models\EventRegistration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminRegistrationManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $administrator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->administrator = User::factory()->create([
            'is_admin' => true,
            'is_active' => true,
        ]);
    }

    public function test_registration_administration_routes_are_private_and_admin_only(): void
    {
        $registration = EventRegistration::factory()->create();

        foreach ([
            route('admin.registrations.index'),
            route('admin.registrations.show', $registration),
            route('admin.registrations.pdf', $registration),
            route('admin.registrations.export', 'csv'),
        ] as $url) {
            $this->get($url)->assertRedirect(route('login'));
        }

        $user = User::factory()->create(['is_admin' => false, 'is_active' => true]);

        foreach ([
            route('admin.registrations.index'),
            route('admin.registrations.show', $registration),
            route('admin.registrations.pdf', $registration),
            route('admin.registrations.export', 'csv'),
        ] as $url) {
            $this->actingAs($user)->get($url)->assertForbidden();
        }
    }

    public function test_dashboard_and_registration_table_filter_by_day_and_member_state(): void
    {
        $ghanaian = EventRegistration::factory()->create([
            'first_name' => 'Ama',
            'surname' => 'Mensah',
            'member_state' => 'Ghana',
            'organisation' => 'Ghana Seed Council',
            'official_email_verified_at' => now(),
            'created_at' => now()->subDay(),
        ]);
        $ghanaian->update(['verified_email_hash' => $ghanaian->official_email_hash]);

        EventRegistration::factory()->create([
            'first_name' => 'Njeri',
            'surname' => 'Kamau',
            'member_state' => 'Kenya',
            'organisation' => 'Kenya Seed Board',
            'created_at' => now()->subDays(8),
        ]);

        $query = ['period' => '7_days', 'member_state' => 'Ghana'];

        $this->actingAs($this->administrator)
            ->get(route('admin.dashboard', $query))
            ->assertOk()
            ->assertSee('Delegate activity')
            ->assertSee('Ama Mensah')
            ->assertDontSee('Njeri Kamau')
            ->assertSee('Ghana');

        $response = $this->actingAs($this->administrator)
            ->get(route('admin.registrations.index', array_merge($query, ['search' => 'Ama'])))
            ->assertOk()
            ->assertSee('Delegate records')
            ->assertSee('Ama Mensah')
            ->assertDontSee('Njeri Kamau')
            ->assertSee('Registration trend')
            ->assertSee('By Member State');

        $this->assertPrivateResponse($response);
    }

    public function test_search_supports_name_organisation_email_and_reference_without_plaintext_indexes(): void
    {
        $registration = EventRegistration::factory()->create([
            'first_name' => 'Stephen',
            'surname' => 'Amodon',
            'organisation' => 'African Union Commission',
            'official_email' => 'stephen@example.test',
        ]);
        EventRegistration::factory()->create([
            'first_name' => 'Different',
            'surname' => 'Delegate',
            'organisation' => 'Regional Office',
            'official_email' => 'other@example.test',
        ]);

        foreach (['Steph', 'Commiss', 'stephen@example.test', $registration->public_id] as $search) {
            $this->actingAs($this->administrator)
                ->get(route('admin.registrations.index', ['search' => $search]))
                ->assertOk()
                ->assertSee('Stephen Amodon')
                ->assertDontSee('Different Delegate');
        }
    }

    public function test_registration_detail_and_individual_pdf_show_the_complete_record(): void
    {
        $registration = EventRegistration::factory()->create([
            'first_name' => 'Linda',
            'surname' => 'Makau',
            'nationality' => 'Kenya',
            'national_id_number' => 'ID-12345',
            'passport_number' => 'P-98765',
            'organisation' => 'African Union Commission',
            'member_state' => 'Kenya',
            'areas_of_expertise' => 'Seed policy and regional trade',
            'official_email' => 'linda@example.test',
            'mobile_number' => '+254 700 000 000',
            'dietary_requirements' => 'Vegetarian',
        ]);

        $detail = $this->actingAs($this->administrator)
            ->get(route('admin.registrations.show', $registration))
            ->assertOk()
            ->assertSee('Linda Makau')
            ->assertSee('ID-12345')
            ->assertSee('P-98765')
            ->assertSee('Seed policy and regional trade')
            ->assertSee('linda@example.test')
            ->assertSee('Vegetarian')
            ->assertSee('Download PDF');

        $this->assertPrivateResponse($detail);

        $pdf = $this->actingAs($this->administrator)
            ->get(route('admin.registrations.pdf', $registration))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertDownload('registration-'.$registration->public_id.'.pdf');

        $this->assertStringStartsWith('%PDF-', (string) $pdf->getContent());
        $this->assertPrivateResponse($pdf);
    }

    public function test_private_registration_documents_are_decrypted_only_for_administrators(): void
    {
        Storage::fake('local');
        $registration = EventRegistration::factory()->create();
        $directory = 'event-registrations/'.$registration->public_id;
        $photoPath = $directory.'/photo.enc';
        $passportPath = $directory.'/passport.enc';
        $photo = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=',
            true,
        );
        $passport = "%PDF-1.4\n1 0 obj\n<<>>\nendobj\n%%EOF";

        $this->assertIsString($photo);
        Storage::disk('local')->put($photoPath, Crypt::encryptString($photo));
        Storage::disk('local')->put($passportPath, Crypt::encryptString($passport));
        $registration->update([
            'passport_photo_path' => $photoPath,
            'passport_photo_original_name' => 'delegate.png',
            'passport_scan_path' => $passportPath,
            'passport_scan_original_name' => 'passport.pdf',
        ]);

        $this->get(route('admin.registrations.document', [$registration, 'photo']))
            ->assertRedirect(route('login'));

        $photoResponse = $this->actingAs($this->administrator)
            ->get(route('admin.registrations.document', [$registration, 'photo']))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/png');
        $this->assertStringContainsString('inline', (string) $photoResponse->headers->get('Content-Disposition'));
        $this->assertSame($photo, $photoResponse->getContent());

        $passportResponse = $this->actingAs($this->administrator)
            ->get(route('admin.registrations.document', [$registration, 'passport']))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringContainsString('attachment', (string) $passportResponse->headers->get('Content-Disposition'));
        $this->assertSame($passport, $passportResponse->getContent());
        $this->assertPrivateResponse($passportResponse);
    }

    public function test_csv_excel_and_pdf_exports_include_the_full_filtered_result(): void
    {
        EventRegistration::factory()->create([
            'first_name' => 'Ama',
            'surname' => 'Mensah',
            'member_state' => 'Ghana',
            'organisation' => '=WEBSERVICE("https://example.test")',
        ]);
        EventRegistration::factory()->create([
            'first_name' => 'Njeri',
            'surname' => 'Kamau',
            'member_state' => 'Kenya',
        ]);

        $csv = $this->actingAs($this->administrator)
            ->get(route('admin.registrations.export', [
                'format' => 'csv',
                'member_state' => 'Ghana',
            ]))
            ->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->assertDownload();
        $csvContents = $csv->streamedContent();
        $this->assertStringContainsString('Registration Reference', $csvContents);
        $this->assertStringContainsString('Ama Mensah', $csvContents);
        $this->assertStringNotContainsString('Njeri Kamau', $csvContents);
        $this->assertStringContainsString("'=WEBSERVICE", $csvContents);

        $excel = $this->actingAs($this->administrator)
            ->get(route('admin.registrations.export', [
                'format' => 'excel',
                'member_state' => 'Ghana',
            ]))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/vnd.ms-excel; charset=UTF-8')
            ->assertDownload();
        $excelContents = $excel->streamedContent();
        $this->assertStringContainsString('<?xml version="1.0"', $excelContents);
        $this->assertStringContainsString('Ama Mensah', $excelContents);
        $this->assertStringNotContainsString('Njeri Kamau', $excelContents);

        $pdf = $this->actingAs($this->administrator)
            ->get(route('admin.registrations.export', [
                'format' => 'pdf',
                'member_state' => 'Ghana',
            ]))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertDownload();
        $this->assertStringStartsWith('%PDF-', (string) $pdf->getContent());
    }

    public function test_invalid_filters_are_rejected_and_pagination_keeps_filters(): void
    {
        EventRegistration::factory()->count(16)->create(['member_state' => 'Ghana']);

        $this->actingAs($this->administrator)
            ->get(route('admin.registrations.index', ['member_state' => 'Atlantis']))
            ->assertSessionHasErrors('member_state');

        $this->actingAs($this->administrator)
            ->get(route('admin.registrations.index', [
                'member_state' => 'Ghana',
                'per_page' => 15,
            ]))
            ->assertOk()
            ->assertSee('page=2', false)
            ->assertSee('member_state=Ghana', false);
    }

    private function assertPrivateResponse(mixed $response): void
    {
        $cacheControl = (string) $response->headers->get('Cache-Control');

        $this->assertStringContainsString('private', $cacheControl);
        $this->assertStringContainsString('no-store', $cacheControl);
        $this->assertSame('no-referrer', $response->headers->get('Referrer-Policy'));
        $this->assertSame('nosniff', $response->headers->get('X-Content-Type-Options'));
        $this->assertStringContainsString('noindex', (string) $response->headers->get('X-Robots-Tag'));
    }
}
