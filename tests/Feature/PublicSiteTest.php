<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Faq;
use App\Models\NewsPost;
use Database\Seeders\FsrpEventPortalSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
            ->assertSee('Rainbow Towers Hotel and Conference Centre')
            ->assertSee('Who will take part')
            ->assertSee('Passport and visa')
            ->assertSee('Official event contacts')
            ->assertSee('/images/caadp/caadp-partnership-1.jpeg', false)
            ->assertSee('/images/caadp/caadp-partnership-4.jpeg', false);
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

    public function test_homepage_keeps_ongoing_events_visible_and_excludes_past_and_unpublished_events(): void
    {
        $this->travelTo('2026-09-16 12:00:00');
        Event::query()->update(['is_published' => false]);
        $ongoing = Event::factory()->create([
            'start_at' => '2026-09-15 08:00:00', 'end_at' => '2026-09-18 17:00:00', 'is_featured' => true,
        ]);
        $today = Event::factory()->create(['start_at' => '2026-09-16 08:00:00', 'end_at' => null]);
        $upcoming = Event::factory()->create(['start_at' => '2026-09-20 08:00:00', 'end_at' => '2026-09-21 17:00:00']);
        Event::factory()->create(['start_at' => '2026-09-14 08:00:00', 'end_at' => '2026-09-15 17:00:00', 'is_featured' => true]);
        Event::factory()->create(['start_at' => '2026-09-16 08:00:00', 'end_at' => '2026-09-16 10:00:00']);
        Event::factory()->create(['start_at' => '2026-09-15 08:00:00', 'end_at' => '2026-09-18 17:00:00', 'is_published' => false]);

        $this->get('/en')
            ->assertViewHas('events', fn ($events): bool => $events->modelKeys() === [$ongoing->id, $today->id, $upcoming->id])
            ->assertViewHas('featuredEvent', fn ($event): bool => $event->is($ongoing));
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
