<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Session;
use App\Models\Slide;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\SeedInvestmentSummitSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\TestWith;
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
}
