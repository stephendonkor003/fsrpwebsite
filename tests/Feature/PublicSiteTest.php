<?php

namespace Tests\Feature;

use App\EventGallery;
use App\Models\Event;
use App\Models\Faq;
use App\Models\NewsPost;
use Database\Seeders\FsrpEventPortalSeeder;
use Database\Seeders\SeedInvestmentSummitSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class PublicSiteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    public function test_the_root_redirects_to_the_default_locale(): void
    {
        $response = $this->get('/');

        $response->assertRedirect('/en');
    }

    public function test_all_supported_language_homepages_render(): void
    {
        foreach (['en', 'fr', 'ar', 'pt', 'es', 'sw'] as $locale) {
            $this->get("/$locale")
                ->assertOk()
                ->assertSee('lang="'.$locale.'"', false);
        }
    }

    public function test_arabic_uses_right_to_left_document_direction(): void
    {
        $this->get('/ar')
            ->assertOk()
            ->assertSee('<html lang="ar" dir="rtl">', false);
    }

    public function test_main_public_pages_and_a_published_event_render(): void
    {
        $this->get('/en/about')->assertOk();
        $this->get('/en/events')->assertOk();
        $this->get('/en/news')->assertOk();
        $this->get('/en/program-outline')->assertOk();
        $this->get('/en/speakers')->assertOk();
        $this->get('/en/faq')->assertOk();

        $event = Event::where('is_published', true)->firstOrFail();

        $this->get('/en/events/'.$event->slug)
            ->assertOk()
            ->assertSee($event->translate('title', 'en'));
    }

    public function test_caadp_event_displays_supplied_artwork_and_participant_information(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put(FsrpEventPortalSeeder::BRIEF_PATH, '%PDF-1.7 information note');
        Storage::disk('local')->put(FsrpEventPortalSeeder::PROGRAMME_PATH, '%PDF-1.7 programme overview');
        $this->seed(FsrpEventPortalSeeder::class);

        $this->get('/en/events/'.FsrpEventPortalSeeder::EVENT_SLUG)
            ->assertOk()
            ->assertSee(FsrpEventPortalSeeder::REGISTRATION_URL, false)
            ->assertSee('Register now')
            ->assertSee('Rainbow Towers Hotel and Conference Centre')
            ->assertSee('Who will take part')
            ->assertSee('Passport and visa')
            ->assertSee('Official event contacts')
            ->assertDontSee('NEPAD', false)
            ->assertDontSee('nepad.org', false)
            ->assertSee('/images/caadp/caadp-partnership-1.jpeg', false)
            ->assertSee('/images/caadp/caadp-partnership-4.jpeg', false);
    }

    public function test_caadp_event_displays_all_media_grouped_by_day_in_natural_order(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put(FsrpEventPortalSeeder::BRIEF_PATH, '%PDF-1.7 information note');
        Storage::disk('local')->put(FsrpEventPortalSeeder::PROGRAMME_PATH, '%PDF-1.7 programme overview');
        $this->seed(FsrpEventPortalSeeder::class);

        $response = $this->get('/en/events/'.FsrpEventPortalSeeder::EVENT_SLUG)
            ->assertOk()
            ->assertSee('View event gallery')
            ->assertSee('121 photos')
            ->assertSee('id="event-gallery-day-1"', false)
            ->assertSee('id="event-gallery-day-2"', false)
            ->assertSee('/media/events/22nd-caadp-partnership-platform/2026-09-15-day-01/photos/caadp-2026-d01-001.jpg', false)
            ->assertSee('/media/events/22nd-caadp-partnership-platform/2026-09-15-day-01/photos/caadp-2026-d01-085.jpg', false)
            ->assertSee('/media/events/22nd-caadp-partnership-platform/2026-09-16-day-02/photos/caadp-2026-d02-001.jpg', false)
            ->assertSee('/media/events/22nd-caadp-partnership-platform/2026-09-16-day-02/photos/caadp-2026-d02-038.jpg', false)
            ->assertViewHas('galleryDays', function (array $galleryDays): bool {
                return array_column($galleryDays, 'day') === [1, 2]
                    && array_column($galleryDays, 'count') === [83, 38]
                    && array_column($galleryDays[0]['items'], 'sequence') === array_merge(range(1, 81), [84, 85])
                    && array_column($galleryDays[1]['items'], 'sequence') === range(1, 38);
            });

        $content = $response->getContent();

        $this->assertSame(121, substr_count($content, 'class="event-gallery-card"'));
        $this->assertSame(121, substr_count($content, ' loading="lazy" decoding="async"'));
        $this->assertLessThan(
            strpos($content, 'caadp-2026-d01-010.jpg'),
            strpos($content, 'caadp-2026-d01-002.jpg'),
        );
    }

    public function test_event_gallery_scanner_rejects_invalid_paths_and_discovers_supported_media(): void
    {
        $eventSlug = 'gallery-scanner-test-'.getmypid();
        $eventDirectory = public_path('media/events/'.$eventSlug);

        File::ensureDirectoryExists($eventDirectory.'/2026-13-40-day-03/photos');
        File::put($eventDirectory.'/2026-13-40-day-03/photos/invalid-001.jpg', 'invalid date');
        File::ensureDirectoryExists($eventDirectory.'/2026-09-17-day-00/photos');
        File::put($eventDirectory.'/2026-09-17-day-00/photos/invalid-001.jpg', 'invalid day');
        File::ensureDirectoryExists($eventDirectory.'/2026-09-17-day-03/photos');
        File::put($eventDirectory.'/2026-09-17-day-03/photos/photo-002.jpg', 'photo');
        File::put($eventDirectory.'/2026-09-17-day-03/photos/ignored-003.txt', 'unsupported');
        File::ensureDirectoryExists($eventDirectory.'/2026-09-17-day-03/videos');
        File::put($eventDirectory.'/2026-09-17-day-03/videos/video-010.mp4', 'video');
        File::ensureDirectoryExists($eventDirectory.'/2026-09-18-day-03/photos');
        File::put($eventDirectory.'/2026-09-18-day-03/photos/duplicate-001.jpg', 'duplicate day');

        try {
            $gallery = app(EventGallery::class);
            $days = $gallery->forEvent($eventSlug);

            $this->assertSame([], $gallery->forEvent('../'.$eventSlug));
            $this->assertCount(1, $days);
            $this->assertSame(3, $days[0]['day']);
            $this->assertSame('2026-09-17', $days[0]['date']);
            $this->assertSame([2, 10], array_column($days[0]['items'], 'sequence'));
            $this->assertSame(['image', 'video'], array_column($days[0]['items'], 'type'));
        } finally {
            File::deleteDirectory($eventDirectory);
        }
    }

    public function test_homepage_hero_and_header_are_focused_on_the_seed_summit(): void
    {
        $this->travelTo('2026-09-21 12:00:00');
        Storage::fake('local');
        Storage::disk('local')->put(FsrpEventPortalSeeder::BRIEF_PATH, '%PDF-1.7 information note');
        Storage::disk('local')->put(FsrpEventPortalSeeder::PROGRAMME_PATH, '%PDF-1.7 programme overview');
        $this->seed(FsrpEventPortalSeeder::class);
        $this->seed(SeedInvestmentSummitSeeder::class);

        $summit = Event::query()->where('slug', SeedInvestmentSummitSeeder::EVENT_SLUG)->sole();

        $response = $this->get('/en')
            ->assertOk()
            ->assertViewHas('slides', fn ($slides): bool => $slides->count() === 1
                && $slides->sole()->translate('title', 'en') === 'Inaugural Seed Investment Summit')
            ->assertViewHas('events', fn ($events): bool => $events->modelKeys() === [$summit->id])
            ->assertViewHas('sessions', fn ($sessions): bool => $sessions->isNotEmpty()
                && $sessions->every(fn ($session): bool => $session->relationLoaded('event')))
            ->assertViewHas('resources', fn ($resources): bool => $resources->every(fn ($resource): bool => $resource->relationLoaded('event')))
            ->assertViewHas('newsPosts', fn ($posts): bool => $posts->isEmpty())
            ->assertViewHas('faqs', fn ($faqs): bool => $faqs->isEmpty())
            ->assertSee('/en'.SeedInvestmentSummitSeeder::REGISTRATION_PATH, false)
            ->assertSee('Register now')
            ->assertSee('Palazzo Convention Centre, Ezulwini, Eswatini')
            ->assertDontSee('22nd CAADP Partnership Platform: participant information')
            ->assertDontSee('Who convenes the 22nd CAADP Partnership Platform?');

        $content = $response->getContent();
        $this->assertMatchesRegularExpression('#<section[^>]+seed-summit-home-hero.*?</section>#s', $content);
        $this->assertSame(1, preg_match('#<section[^>]+seed-summit-home-hero.*?</section>#s', $content, $heroMatches));
        $this->assertSame(1, preg_match('#<header[^>]*>.*?</header>#s', $content, $headerMatches));

        $hero = $heroMatches[0];
        $header = $headerMatches[0];

        $this->assertSame(1, substr_count($hero, ' data-slide'));
        $this->assertStringContainsString('Inaugural Seed Investment Summit', $hero);
        $this->assertStringContainsString(SeedInvestmentSummitSeeder::IMAGE_PATH, $hero);
        $this->assertStringNotContainsString('22nd CAADP Partnership Platform', $hero);
        $this->assertStringNotContainsString('H.E. Moses Vilakati', $hero);
        $this->assertStringNotContainsString('FSRP Events', $header);
        $this->assertStringContainsString('alt="African Union"', $header);
    }

    public function test_programme_page_shows_update_notice_and_readable_english_punctuation(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put(FsrpEventPortalSeeder::BRIEF_PATH, '%PDF-1.7 information note');
        Storage::disk('local')->put(FsrpEventPortalSeeder::PROGRAMME_PATH, '%PDF-1.7 programme overview');
        $this->seed(FsrpEventPortalSeeder::class);

        $this->get('/en/program-outline')
            ->assertOk()
            ->assertSee('class="programme-update-notice"', false)
            ->assertSee('Programme updates in progress')
            ->assertSee('Session details and timings may change')
            ->assertSee('Day 2 · Country readiness & REC delivery')
            ->assertSee('identify 1–3 priority actions')
            ->assertDontSee('Â·', false)
            ->assertDontSee('â€“', false);
    }

    public function test_event_search_matches_arabic_translations(): void
    {
        $this->freezeTime();
        $event = Event::firstOrFail();
        $event->update([
            'title' => ['en' => 'Regional food forum', 'ar' => 'الأمن الغذائي'],
            'start_at' => now()->addDay(),
            'end_at' => now()->addDays(2),
            'is_published' => true,
        ]);

        $this->get('/ar/events?'.http_build_query(['q' => 'الأمن']))
            ->assertViewHas('events', fn ($events): bool => $events->modelKeys() === [$event->id]);
    }

    public function test_event_directory_defaults_to_every_published_event_and_retains_period_filters(): void
    {
        $this->travelTo('2026-09-21 12:00:00');
        Event::query()->update(['is_published' => false]);
        $upcoming = Event::factory()->create([
            'start_at' => '2026-10-05 08:00:00', 'end_at' => '2026-10-07 17:00:00',
        ]);
        $pastWithoutEndDate = Event::factory()->create([
            'start_at' => '2026-09-19 08:00:00', 'end_at' => null,
        ]);
        $past = Event::factory()->create([
            'start_at' => '2026-09-15 08:00:00', 'end_at' => '2026-09-18 17:00:00',
        ]);
        $unpublished = Event::factory()->create([
            'start_at' => '2026-10-08 08:00:00', 'end_at' => '2026-10-09 17:00:00', 'is_published' => false,
        ]);

        $this->get('/en/events')
            ->assertOk()
            ->assertViewHas('period', 'all')
            ->assertViewHas('events', fn ($events): bool => $events->modelKeys() === [
                $upcoming->id,
                $pastWithoutEndDate->id,
                $past->id,
            ])
            ->assertDontSee($unpublished->translate('title', 'en'));

        $this->get('/en/events?period=upcoming')
            ->assertViewHas('events', fn ($events): bool => $events->modelKeys() === [$upcoming->id]);

        $this->get('/en/events?period=past')
            ->assertViewHas('events', fn ($events): bool => $events->modelKeys() === [
                $pastWithoutEndDate->id,
                $past->id,
            ]);
    }

    public function test_homepage_only_surfaces_the_current_seed_summit_event(): void
    {
        $this->travelTo('2026-09-21 12:00:00');
        $summit = Event::query()->where('slug', SeedInvestmentSummitSeeder::EVENT_SLUG)->sole();
        $summit->update([
            'start_at' => '2026-10-05 08:00:00',
            'end_at' => '2026-10-07 17:00:00',
            'is_published' => true,
            'is_featured' => true,
        ]);
        Event::factory()->create([
            'title' => ['en' => 'Another featured gathering'],
            'start_at' => '2026-09-22 08:00:00',
            'end_at' => '2026-09-23 17:00:00',
            'is_featured' => true,
        ]);
        Event::factory()->create([
            'title' => ['en' => 'Past gathering'],
            'start_at' => '2026-09-15 08:00:00',
            'end_at' => '2026-09-18 17:00:00',
        ]);
        Event::factory()->create([
            'title' => ['en' => 'Unpublished gathering'],
            'start_at' => '2026-09-25 08:00:00',
            'end_at' => '2026-09-26 17:00:00',
            'is_published' => false,
        ]);

        $this->get('/en')
            ->assertViewHas('events', fn ($events): bool => $events->modelKeys() === [$summit->id])
            ->assertViewHas('featuredEvent', fn ($event): bool => $event->is($summit))
            ->assertSee('Inaugural Seed Investment Summit')
            ->assertDontSee('Another featured gathering')
            ->assertDontSee('Past gathering')
            ->assertDontSee('Unpublished gathering');

        $summit->update(['is_featured' => false]);

        $this->get('/en')
            ->assertViewHas('featuredEvent', null)
            ->assertViewHas('slides', fn ($slides): bool => $slides->isEmpty())
            ->assertViewHas('events', fn ($events): bool => $events->isEmpty());
    }

    public function test_news_search_matches_accented_translations(): void
    {
        $this->freezeTime();
        $post = NewsPost::firstOrFail();
        $post->update([
            'title' => ['en' => 'Regional food update', 'fr' => 'Récolte durable'],
            'published_at' => now()->subDay(),
            'is_published' => true,
        ]);

        $this->get('/fr/news?'.http_build_query(['q' => 'Récolte']))
            ->assertViewHas('posts', fn ($posts): bool => $posts->modelKeys() === [$post->id]);
    }

    public function test_faq_search_matches_translated_questions_and_answers(): void
    {
        $faq = Faq::firstOrFail();
        $faq->update([
            'question' => ['en' => 'Where is the forum?', 'fr' => 'Où participer?'],
            'answer' => ['en' => 'Attend the regional forum.', 'ar' => 'الغذائي'],
            'is_published' => true,
        ]);

        foreach (['Où', 'الغذائي'] as $search) {
            $this->get('/en/faq?'.http_build_query(['q' => $search]))
                ->assertViewHas('faqs', fn ($faqs): bool => $faqs->modelKeys() === [$faq->id]);
        }
    }

    public function test_array_search_filters_return_validation_errors(): void
    {
        foreach (['events', 'news', 'faq'] as $page) {
            $this->getJson('/en/'.$page.'?q[]=invalid')
                ->assertUnprocessable()
                ->assertJsonValidationErrors('q');
        }

        $this->getJson('/en/events?mode[]=online&period[]=past')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['mode', 'period']);
    }

    public function test_scheduled_news_is_unavailable_until_its_publication_time(): void
    {
        $this->freezeTime();
        $post = NewsPost::firstOrFail();
        $post->update(['is_published' => true, 'published_at' => now()->addDay()]);

        $this->get('/en/news/'.$post->slug)->assertNotFound();

        $this->travel(1)->days();

        $this->get('/en/news/'.$post->slug)->assertOk();
    }

    public function test_related_news_excludes_future_and_unpublished_posts(): void
    {
        $this->freezeTime();
        $posts = NewsPost::take(3)->get();
        $posts[0]->update(['is_published' => true, 'published_at' => null]);
        $posts[1]->update(['is_published' => true, 'published_at' => now()->addDay()]);
        $posts[2]->update(['is_published' => false, 'published_at' => now()->subDay()]);

        $this->get('/en/news/'.$posts[0]->slug)
            ->assertViewHas('relatedPosts', fn ($relatedPosts): bool => ! $relatedPosts->contains($posts[1]) && ! $relatedPosts->contains($posts[2]));
    }

    /**
     * @param  class-string<Event|NewsPost>  $modelClass
     */
    #[TestWith([Event::class, 'events.show'])]
    #[TestWith([NewsPost::class, 'news.show'])]
    public function test_event_and_news_metadata_escape_html_content(string $modelClass, string $routeName): void
    {
        $this->freezeTime();
        $item = $modelClass::where('is_published', true)->firstOrFail();
        $title = '</title><script>alert("title")</script>';
        $description = '"><script>alert("description")</script>';
        $item->fill(['title' => ['en' => $title], 'excerpt' => ['en' => $description]]);

        if ($item instanceof NewsPost) {
            $item->published_at = now()->subDay();
        }

        $item->save();

        $this->get(route($routeName, ['locale' => 'en', 'slug' => $item->slug]))
            ->assertSee('<title>'.e($title), false)
            ->assertSee('name="description" content="'.e($description).'"', false)
            ->assertDontSee($title, false)
            ->assertDontSee($description, false);
    }
}
