<?php

namespace Tests\Feature;

use App\Models\EventResource;
use App\Models\Faq;
use App\Models\HomeSection;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminContentTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_sent_to_the_administrator_login(): void
    {
        $this->get('/admin')->assertRedirect(route('login'));
        $this->get('/admin/login')->assertOk();
    }

    public function test_an_administrator_can_sign_in_and_view_the_dashboard(): void
    {
        $admin = User::factory()->create([
            'email' => 'administrator@example.test',
            'password' => 'secure-password',
            'is_admin' => true,
            'is_active' => true,
        ]);

        $this->post('/admin/login', [
            'email' => $admin->email,
            'password' => 'secure-password',
        ])->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($admin);
        $this->get('/admin')->assertOk()->assertSee('Workspace overview');
    }

    public function test_a_non_administrator_cannot_open_the_back_office(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'is_active' => true]);

        $this->actingAs($user)->get('/admin')->assertForbidden();
    }

    public function test_existing_content_create_and_edit_forms_render_for_administrators(): void
    {
        $this->seed();
        $admin = User::factory()->create(['is_admin' => true, 'is_active' => true]);
        $this->actingAs($admin);

        foreach (config('admin-content.types') as $type => $definition) {
            if ($type === 'resources') {
                continue;
            }

            $item = $definition['model']::firstOrFail();

            $this->get(route('admin.content.create', $type))
                ->assertOk()->assertSee('enctype="multipart/form-data"', false)
                ->assertSee('Add '.$definition['singular']);
            $this->get(route('admin.content.edit', [$type, $item]))
                ->assertOk()->assertSee('name="_method" value="PUT"', false)
                ->assertSee('Edit '.$definition['singular']);
        }
    }

    public function test_resource_create_and_edit_forms_render_the_document_upload_and_current_file(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'is_active' => true]);
        $resource = EventResource::factory()->create(['original_filename' => 'participant-programme.pdf']);
        $this->actingAs($admin);

        $this->get(route('admin.content.create', 'resources'))
            ->assertOk()->assertSee('Add Event resource')->assertSee('name="document"', false);
        $this->get(route('admin.content.edit', ['resources', $resource]))
            ->assertOk()->assertSee('Edit Event resource')->assertSee('name="document"', false)
            ->assertSee('participant-programme.pdf')
            ->assertSee(route('admin.resources.download', $resource), false);
    }

    public function test_an_administrator_can_create_multilingual_faq_content(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'is_active' => true]);
        $translations = [];

        foreach (array_keys(config('locales.supported')) as $locale) {
            $translations[$locale] = [
                'category' => $locale === 'en' ? 'Participation' : 'Participation '.$locale,
                'question' => $locale === 'en' ? 'How can I attend?' : 'Question '.$locale,
                'answer' => $locale === 'en' ? 'Use the event registration link.' : 'Answer '.$locale,
            ];
        }

        $this->actingAs($admin)->post(route('admin.content.store', 'faqs'), [
            'translations' => $translations,
            'sort_order' => 10,
            'is_published' => '1',
        ])->assertRedirect(route('admin.content.index', 'faqs'));

        $faq = Faq::firstOrFail();
        $this->assertSame('How can I attend?', $faq->translate('question', 'en'));
        $this->assertTrue($faq->is_published);
    }

    public function test_an_administrator_can_update_site_settings_and_homepage_order(): void
    {
        $this->seed();
        $admin = User::factory()->create(['is_admin' => true, 'is_active' => true]);
        $localizedSettings = [];

        foreach (['site_name', 'tagline', 'address', 'footer_blurb', 'copyright'] as $key) {
            foreach (array_keys(config('locales.supported')) as $locale) {
                $localizedSettings[$key][$locale] = ucfirst(str_replace('_', ' ', $key))." $locale";
            }
        }

        $this->actingAs($admin)->put(route('admin.settings.update'), [
            'settings' => array_merge($localizedSettings, [
                'contact_email' => 'hello@example.test',
                'contact_phone' => '+254 700 000 000',
                'facebook_url' => 'https://example.test/facebook',
                'linkedin_url' => 'https://example.test/linkedin',
                'youtube_url' => 'https://example.test/youtube',
            ]),
        ])->assertSessionHasNoErrors();

        $this->assertSame('Site name en', Setting::where('key', 'site_name')->firstOrFail()->value['en']);

        $sections = HomeSection::orderBy('sort_order')->get();
        $payload = $sections->values()->map(fn (HomeSection $section, int $index): array => [
            'id' => $section->id,
            'sort_order' => ($index + 1) * 5,
            'is_active' => $index === 0 ? '0' : '1',
        ])->all();

        $this->actingAs($admin)->put(route('admin.home-sections.update'), ['sections' => $payload])
            ->assertSessionHasNoErrors();

        $this->assertFalse($sections->first()->fresh()->is_active);
    }

    public function test_content_can_be_saved_with_an_empty_optional_display_order(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'is_active' => true]);
        $payload = [
            'translations' => ['en' => ['question' => 'How can I attend?', 'answer' => 'Register online.']],
            'sort_order' => '',
            'is_published' => '1',
        ];

        $this->actingAs($admin)->post(route('admin.content.store', 'faqs'), $payload)
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('admin.content.index', 'faqs'));

        $this->assertDatabaseHas('faqs', ['sort_order' => 0]);
    }

    public function test_invalid_content_identifiers_return_not_found(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'is_active' => true]);

        $this->actingAs($admin);

        foreach (['invalid', '9223372036854775808'] as $identifier) {
            $this->get('/admin/content/faqs/'.$identifier.'/edit')->assertNotFound();
            $this->put('/admin/content/faqs/'.$identifier, [])->assertNotFound();
            $this->delete('/admin/content/faqs/'.$identifier)->assertNotFound();
        }
    }
}
