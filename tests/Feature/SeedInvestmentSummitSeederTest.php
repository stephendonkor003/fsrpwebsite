<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Faq;
use App\Models\NewsPost;
use App\Models\Page;
use App\Models\Program;
use App\Models\Session;
use App\Models\Setting;
use App\Models\Slide;
use Database\Seeders\CaadpEventArchiveSeeder;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\SeedInvestmentSummitSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\TestWith;
use RuntimeException;
use Tests\TestCase;

class SeedInvestmentSummitSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_is_rerunnable_preserves_existing_events_and_features_only_the_summit(): void
    {
        $previouslyFeaturedEvent = Event::factory()->create([
            'slug' => 'existing-featured-event',
            'title' => ['en' => 'Existing featured event'],
            'is_featured' => true,
        ]);
        $otherEvent = Event::factory()->create([
            'slug' => 'other-existing-event',
            'title' => ['en' => 'Other existing event'],
            'is_featured' => false,
        ]);
        $legacySlide = Slide::query()->create([
            'title' => ['en' => 'Previous homepage story'],
            'button_url' => '/events/previous-event',
            'image' => '/images/previous-event.jpg',
            'is_active' => true,
            'sort_order' => 9,
        ]);

        $this->seed(SeedInvestmentSummitSeeder::class);
        $summitId = Event::query()->where('slug', SeedInvestmentSummitSeeder::EVENT_SLUG)->sole()->id;
        $summitSlideId = Slide::query()->where('button_url', SeedInvestmentSummitSeeder::EVENT_PATH)->sole()->id;
        $this->seed(SeedInvestmentSummitSeeder::class);

        $summit = Event::query()->where('slug', SeedInvestmentSummitSeeder::EVENT_SLUG)->sole();
        $summitSlide = Slide::query()->where('button_url', SeedInvestmentSummitSeeder::EVENT_PATH)->sole();

        $this->assertSame($summitId, $summit->id);
        $this->assertSame(3, Event::query()->count());
        $this->assertSame(1, Event::query()->where('is_featured', true)->count());
        $this->assertFalse($previouslyFeaturedEvent->fresh()->is_featured);
        $this->assertSame('Existing featured event', $previouslyFeaturedEvent->fresh()->translate('title', 'en'));
        $this->assertSame('Other existing event', $otherEvent->fresh()->translate('title', 'en'));
        $this->assertTrue($summit->is_featured);
        $this->assertTrue($summit->is_published);
        $this->assertSame('Inaugural Seed Investment Summit', $summit->translate('title', 'en'));
        $this->assertSame('Resilient Seed Systems for a Food Secure Africa', $summit->translate('excerpt', 'en'));
        $this->assertStringContainsString('Resilient Seed Systems for a Food Secure Africa', $summit->translate('body', 'en'));
        $this->assertStringContainsString('Ezulwini Declaration', $summit->translate('body', 'en'));
        $this->assertStringContainsString('Seed Sector Performance Index 2025', $summit->translate('body', 'en'));
        $this->assertSame('Palazzo Convention Centre, Ezulwini, Eswatini', $summit->translate('venue', 'en'));
        $this->assertSame('2026-10-05 00:00:00', $summit->start_at?->format('Y-m-d H:i:s'));
        $this->assertSame('2026-10-07 23:59:59', $summit->end_at?->format('Y-m-d H:i:s'));
        $this->assertSame('in-person', $summit->mode);
        $this->assertSame(SeedInvestmentSummitSeeder::REGISTRATION_PATH, $summit->registration_url);
        $this->assertSame(SeedInvestmentSummitSeeder::IMAGE_PATH, $summit->image);
        $this->assertSame(['en', 'fr', 'ar', 'pt', 'es', 'sw'], array_keys($summit->title));
        $this->assertSame($summitSlideId, $summitSlide->id);
        $this->assertSame(2, Slide::query()->count());
        $this->assertSame(1, Slide::query()->where('is_active', true)->count());
        $this->assertFalse($legacySlide->fresh()->is_active);
        $this->assertSame('Previous homepage story', $legacySlide->fresh()->translate('title', 'en'));
        $this->assertTrue($summitSlide->is_active);
        $this->assertSame('Inaugural Seed Investment Summit', $summitSlide->translate('title', 'en'));
        $this->assertSame(SeedInvestmentSummitSeeder::IMAGE_PATH, $summitSlide->image);
        $this->assertSame([
            'Meeting of Senior Officials',
            'Meeting of the Ministers of Agriculture',
            'Summit for AU Heads of State and Government',
        ], $summit->sessions()->orderBy('start_at')->get()->map(
            fn (Session $session): string => $session->translate('title', 'en'),
        )->all());
        $this->assertSame(3, $summit->sessions()->where('is_all_day', true)->whereNull('end_at')->count());
        $this->assertFileExists(public_path(ltrim(SeedInvestmentSummitSeeder::IMAGE_PATH, '/')));
        $this->assertSame(
            SeedInvestmentSummitSeeder::FLYER_SHA256,
            hash_file('sha256', public_path(ltrim(SeedInvestmentSummitSeeder::IMAGE_PATH, '/'))),
        );
    }

    public function test_seed_and_archive_seeders_are_order_independent(): void
    {
        $this->prepareCaadpDocuments();

        $this->seed(CaadpEventArchiveSeeder::class);
        $this->seed(SeedInvestmentSummitSeeder::class);

        $seedEventId = Event::query()->where('slug', SeedInvestmentSummitSeeder::EVENT_SLUG)->sole()->id;
        $caadpEventId = Event::query()->where('slug', CaadpEventArchiveSeeder::EVENT_SLUG)->sole()->id;
        $caadpResourceIds = Event::query()->findOrFail($caadpEventId)->resources()->orderBy('sort_order')->pluck('id')->all();

        $this->assertCurrentEventInvariants();

        $this->seed(SeedInvestmentSummitSeeder::class);
        $this->seed(CaadpEventArchiveSeeder::class);

        $this->assertCurrentEventInvariants();
        $this->assertSame($seedEventId, Event::query()->where('slug', SeedInvestmentSummitSeeder::EVENT_SLUG)->sole()->id);
        $this->assertSame($caadpEventId, Event::query()->where('slug', CaadpEventArchiveSeeder::EVENT_SLUG)->sole()->id);
        $this->assertSame($caadpResourceIds, Event::query()->findOrFail($caadpEventId)->resources()->orderBy('sort_order')->pluck('id')->all());
    }

    public function test_seeder_retires_known_legacy_content_without_deleting_or_unpublishing_unrelated_content(): void
    {
        $legacyProgram = Program::query()->create([
            'slug' => 'fsrp-regional-food-markets',
            'title' => ['en' => 'Regional food markets & trade'],
            'excerpt' => ['en' => 'Legacy excerpt'],
            'body' => ['en' => 'Legacy body'],
            'is_published' => true,
        ]);
        $customProgram = Program::query()->create([
            'slug' => 'editorial-programme',
            'title' => ['en' => 'Editorial programme'],
            'excerpt' => ['en' => 'Editorial excerpt'],
            'body' => ['en' => 'Editorial body'],
            'is_published' => true,
        ]);
        $legacyFaq = Faq::query()->create([
            'category' => ['en' => 'Legacy'],
            'question' => ['en' => 'What is FSRP Events?'],
            'answer' => ['en' => 'Legacy answer'],
            'is_published' => true,
        ]);
        $legacyCaadpFaqs = collect([
            'Who convenes the 22nd CAADP Partnership Platform?',
            'When and where is the CAADP partner event?',
            'Where can I download the CAADP programme?',
            'What interpretation is listed for CAADP?',
            'How do I confirm participation and travel arrangements?',
        ])->map(fn (string $question): Faq => Faq::query()->create([
            'category' => ['en' => 'Legacy CAADP'],
            'question' => ['en' => $question],
            'answer' => ['en' => 'Legacy CAADP answer'],
            'is_published' => true,
        ]));
        $customFaq = Faq::query()->create([
            'category' => ['en' => 'Editorial'],
            'question' => ['en' => 'Editorial question?'],
            'answer' => ['en' => 'Editorial answer'],
            'is_published' => true,
        ]);
        $legacyNews = NewsPost::query()->create([
            'slug' => '22nd-caadp-partnership-platform-participant-information',
            'title' => ['en' => 'CAADP participant information'],
            'excerpt' => ['en' => 'Legacy excerpt'],
            'body' => ['en' => 'Legacy body'],
            'category' => ['en' => 'Update'],
            'is_featured' => true,
            'is_published' => true,
        ]);
        $customNews = NewsPost::query()->create([
            'slug' => 'editorial-update',
            'title' => ['en' => 'Editorial update'],
            'excerpt' => ['en' => 'Editorial excerpt'],
            'body' => ['en' => 'Editorial body'],
            'category' => ['en' => 'Update'],
            'is_published' => true,
        ]);
        Setting::query()->create(['key' => 'site_name', 'value' => ['en' => 'FSRP Events'], 'group' => 'general']);
        Setting::query()->create(['key' => 'logo', 'value' => ['value' => '/images/fsrp/african-union-logo.png'], 'group' => 'general']);
        Setting::query()->create(['key' => 'contact_email', 'value' => ['value' => 'events@example.test'], 'group' => 'contact']);
        Page::query()->create([
            'key' => 'about',
            'eyebrow' => ['en' => 'About FSRP Events'],
            'title' => ['en' => 'Legacy platform'],
            'body' => ['en' => 'Legacy FSRP content'],
            'image' => '/images/fsrp/legacy.jpg',
            'is_published' => true,
        ]);

        $this->seed(SeedInvestmentSummitSeeder::class);

        $this->assertFalse($legacyProgram->fresh()->is_published);
        $this->assertTrue($customProgram->fresh()->is_published);
        $this->assertFalse($legacyFaq->fresh()->is_published);
        $this->assertTrue($legacyCaadpFaqs->every(fn (Faq $faq): bool => ! $faq->fresh()->is_published));
        $this->assertTrue($customFaq->fresh()->is_published);
        $this->assertFalse($legacyNews->fresh()->is_published);
        $this->assertFalse($legacyNews->fresh()->is_featured);
        $this->assertTrue($customNews->fresh()->is_published);
        $this->assertSame('African Union Events', Setting::query()->where('key', 'site_name')->sole()->value['en']);
        $this->assertSame('/images/brand/african-union-logo.png', Setting::query()->where('key', 'logo')->sole()->value['value']);
        $this->assertSame('events@example.test', Setting::query()->where('key', 'contact_email')->sole()->value['value']);
        $this->assertSame('About African Union Events', Page::query()->where('key', 'about')->sole()->translate('eyebrow', 'en'));
        $this->assertDatabaseHas('programs', ['id' => $legacyProgram->id]);
        $this->assertDatabaseHas('faqs', ['id' => $legacyFaq->id]);
        $legacyCaadpFaqs->each(fn (Faq $faq) => $this->assertDatabaseHas('faqs', ['id' => $faq->id]));
        $this->assertDatabaseHas('news_posts', ['id' => $legacyNews->id]);
    }

    public function test_database_seeder_publishes_only_the_two_real_events_with_seed_as_current(): void
    {
        $this->prepareCaadpDocuments();

        $this->seed(DatabaseSeeder::class);

        $this->assertSame([
            SeedInvestmentSummitSeeder::EVENT_SLUG,
            CaadpEventArchiveSeeder::EVENT_SLUG,
        ], Event::query()->where('is_published', true)->orderByDesc('start_at')->pluck('slug')->all());
        $this->assertCurrentEventInvariants();
        $this->assertSame('African Union Events', Setting::query()->where('key', 'site_name')->sole()->value['en']);
        $this->assertSame('/images/brand/african-union-logo.png', Setting::query()->where('key', 'logo')->sole()->value['value']);
        $this->assertSame('', Setting::query()->where('key', 'contact_email')->sole()->value['value']);
        $this->assertSame('About African Union Events', Page::query()->where('key', 'about')->sole()->translate('eyebrow', 'en'));
        $this->assertSame(0, Program::query()->where('is_published', true)->count());
        $this->assertSame(0, Faq::query()->where('is_published', true)->count());
        $this->assertSame(0, NewsPost::query()->where('is_published', true)->count());
        $this->assertDatabaseHas('home_sections', [
            'key' => 'speakers',
            'is_active' => true,
            'sort_order' => 4,
        ]);
        $this->assertDatabaseHas('home_sections', [
            'key' => 'programs',
            'is_active' => false,
        ]);
    }

    public function test_database_seeder_preflights_event_assets_before_mutating_existing_data(): void
    {
        Storage::fake('local');
        $setting = Setting::query()->create([
            'key' => 'site_name',
            'value' => ['en' => 'Existing platform'],
            'group' => 'general',
        ]);

        try {
            $this->seed(DatabaseSeeder::class);
            $this->fail('Missing CAADP documents should stop the database seeder before it writes.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString(CaadpEventArchiveSeeder::BRIEF_PATH, $exception->getMessage());
        }

        $this->assertSame('Existing platform', $setting->fresh()->value['en']);
        $this->assertDatabaseCount('settings', 1);
        $this->assertDatabaseCount('events', 0);
        $this->assertDatabaseCount('slides', 0);
    }

    #[TestWith(['/events/inaugural-seed-investment-summit/register', 'fr', '/fr/events/inaugural-seed-investment-summit/register', false])]
    #[TestWith(['/en/events/inaugural-seed-investment-summit/register?source=home#form', 'sw', '/sw/events/inaugural-seed-investment-summit/register?source=home#form', false])]
    #[TestWith(['https://events.example.test/register', 'ar', 'https://events.example.test/register', true])]
    #[TestWith(['http://events.example.test/register', 'pt', 'http://events.example.test/register', true])]
    public function test_registration_urls_follow_the_visitor_language_and_preserve_safe_external_urls(
        string $registrationUrl,
        string $locale,
        string $expectedUrl,
        bool $opensExternally,
    ): void {
        $event = new Event(['registration_url' => $registrationUrl]);

        $this->assertSame($expectedUrl, $event->registrationUrlForLocale($locale));
        $this->assertSame($opensExternally, $event->registrationUrlOpensExternally());
    }

    #[TestWith(['javascript:alert(1)'])]
    #[TestWith(['//example.test/register'])]
    #[TestWith(['/\\example.test/register'])]
    #[TestWith(['/%2fexample.test/register'])]
    #[TestWith(['/%5cexample.test/register'])]
    #[TestWith(['data:text/html,test'])]
    public function test_unsafe_registration_urls_are_suppressed(string $registrationUrl): void
    {
        $event = new Event(['registration_url' => $registrationUrl]);

        $this->assertNull($event->registrationUrlForLocale('en'));
        $this->assertFalse($event->registrationUrlOpensExternally());
    }

    public function test_public_pages_render_localized_internal_ctas_and_external_ctas_safely(): void
    {
        $this->travelTo('2026-09-21 12:00:00');
        $this->prepareCaadpDocuments();
        $this->seed(DatabaseSeeder::class);
        $this->seed(SeedInvestmentSummitSeeder::class);

        $summit = Event::query()->where('slug', SeedInvestmentSummitSeeder::EVENT_SLUG)->sole();
        $englishRegistrationUrl = '/en'.SeedInvestmentSummitSeeder::REGISTRATION_PATH;
        $frenchRegistrationUrl = '/fr'.SeedInvestmentSummitSeeder::REGISTRATION_PATH;

        $englishHomepage = $this->get('/en')
            ->assertOk()
            ->assertViewHas('featuredEvent', fn (?Event $event): bool => $event?->is($summit) ?? false)
            ->assertSee('Inaugural Seed Investment Summit')
            ->assertSee(SeedInvestmentSummitSeeder::IMAGE_PATH, false)
            ->assertSee($englishRegistrationUrl, false);
        $this->assertDoesNotMatchRegularExpression(
            '#href="'.preg_quote($englishRegistrationUrl, '#').'"\s+target="_blank"#',
            $englishHomepage->getContent(),
        );

        $this->get('/en/events/'.SeedInvestmentSummitSeeder::EVENT_SLUG)
            ->assertOk()
            ->assertSee('Official concept note')
            ->assertSee('Summit objectives')
            ->assertSee('Expected outcomes')
            ->assertSee('Five thematic areas')
            ->assertSee('Meeting of Senior Officials')
            ->assertSee('Summit for AU Heads of State and Government');

        $frenchEventPage = $this->get('/fr/events/'.SeedInvestmentSummitSeeder::EVENT_SLUG)
            ->assertOk()
            ->assertSee('Sommet inaugural sur l’investissement semencier')
            ->assertSee('Palazzo Convention Centre, Ezulwini, Eswatini')
            ->assertSee(SeedInvestmentSummitSeeder::IMAGE_PATH, false)
            ->assertSee($frenchRegistrationUrl, false);
        $this->assertDoesNotMatchRegularExpression(
            '#href="'.preg_quote($frenchRegistrationUrl, '#').'"\s+target="_blank"#',
            $frenchEventPage->getContent(),
        );

        $externalRegistrationUrl = 'https://events.example.test/register';
        $summit->update(['registration_url' => $externalRegistrationUrl]);

        foreach (['/en', '/en/events/'.SeedInvestmentSummitSeeder::EVENT_SLUG] as $page) {
            $response = $this->get($page)->assertOk();

            $this->assertMatchesRegularExpression(
                '#href="'.preg_quote($externalRegistrationUrl, '#').'"\s+target="_blank"\s+rel="noopener"#',
                $response->getContent(),
            );
        }
    }

    private function assertCurrentEventInvariants(): void
    {
        $summit = Event::query()->where('slug', SeedInvestmentSummitSeeder::EVENT_SLUG)->sole();
        $caadp = Event::query()->where('slug', CaadpEventArchiveSeeder::EVENT_SLUG)->sole();

        $this->assertTrue($summit->is_published);
        $this->assertTrue($summit->is_featured);
        $this->assertTrue($caadp->is_published);
        $this->assertFalse($caadp->is_featured);
        $this->assertSame(1, Event::query()->where('is_featured', true)->count());
        $this->assertSame(1, Slide::query()->where('is_active', true)->count());
        $this->assertSame(
            SeedInvestmentSummitSeeder::EVENT_PATH,
            Slide::query()->where('is_active', true)->sole()->button_url,
        );
    }

    private function prepareCaadpDocuments(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put(CaadpEventArchiveSeeder::BRIEF_PATH, '%PDF-1.7 information note');
        Storage::disk('local')->put(CaadpEventArchiveSeeder::PROGRAMME_PATH, '%PDF-1.7 programme overview');
    }
}
