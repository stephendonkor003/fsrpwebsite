<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\NewsPost;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\TestWith;
use SimpleXMLElement;
use Tests\TestCase;

class SearchIndexingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['seo.canonical_url' => 'https://events.example.test', 'seo.indexing_enabled' => false, 'app.timezone' => 'UTC']);
    }

    public function test_the_root_permanently_redirects_to_the_default_language(): void
    {
        $this->get('/')->assertStatus(301)->assertRedirect('/en');
    }

    public function test_production_robots_permits_public_pages_and_points_to_the_canonical_sitemap(): void
    {
        $this->enableProductionIndexing();

        $this->get('https://events.example.test/robots.txt')
            ->assertOk()->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
            ->assertSee("User-agent: *\nAllow: /\n", false)
            ->assertSee('Sitemap: https://events.example.test/sitemap.xml', false)
            ->assertDontSee('Disallow:', false);

        $this->get('https://events.example.test/admin/login')
            ->assertOk()->assertHeader('X-Robots-Tag', 'noindex, nofollow');
    }

    #[TestWith(['testing', true, 'https://events.example.test'])]
    #[TestWith(['production', true, 'http://127.0.0.1:8001'])]
    #[TestWith(['production', true, 'https://untrusted.example'])]
    public function test_nonpublic_environments_disable_robot_crawling(string $environment, bool $indexingEnabled, string $requestBase): void
    {
        $this->app->instance('env', $environment);
        config(['app.env' => $environment, 'seo.indexing_enabled' => $indexingEnabled]);

        $this->get($requestBase.'/robots.txt')
            ->assertOk()->assertSee("User-agent: *\nDisallow: /\n", false)
            ->assertDontSee('Sitemap:', false);
    }

    public function test_disabling_production_indexing_allows_crawlers_to_read_noindex_headers(): void
    {
        $this->enableProductionIndexing();
        config(['seo.indexing_enabled' => false]);

        $this->get('https://events.example.test/robots.txt')
            ->assertOk()->assertHeader('X-Robots-Tag', 'noindex, follow')
            ->assertSee("User-agent: *\nAllow: /\n", false)
            ->assertDontSee('Disallow:', false)->assertDontSee('Sitemap:', false);

        $this->get('https://events.example.test/en')
            ->assertOk()->assertHeader('X-Robots-Tag', 'noindex, follow');
    }

    public function test_the_sitemap_lists_all_public_static_pages_and_language_alternatives(): void
    {
        $this->enableProductionIndexing();

        $response = $this->get('https://events.example.test/sitemap.xml')
            ->assertOk()->assertHeader('Content-Type', 'application/xml; charset=UTF-8');
        $xml = $this->xml($response->getContent());
        $entries = $xml->xpath('//s:url');

        $this->assertCount(48, $entries);
        $this->assertCount(0, $xml->xpath('//s:lastmod'));

        foreach (['en', 'fr', 'ar', 'pt', 'es', 'sw'] as $locale) {
            foreach (['', '/about', '/events', '/program-outline', '/speakers', '/resources', '/news', '/faq'] as $path) {
                $url = 'https://events.example.test/'.$locale.$path;
                $matching = $xml->xpath('//s:url[s:loc="'.$url.'"]');
                $this->assertCount(1, $matching);
                $alternates = $matching[0]->children('http://www.w3.org/1999/xhtml')->link;
                $this->assertCount(7, $alternates);
                $this->assertSame('https://events.example.test/en'.$path, (string) $alternates[6]->attributes()->href);
                $this->assertSame('x-default', (string) $alternates[6]->attributes()->hreflang);
            }
        }
    }

    public function test_the_sitemap_only_lists_published_events_and_available_news_with_honest_update_dates(): void
    {
        $this->freezeTime();
        $this->seed();
        $this->enableProductionIndexing();
        Event::query()->update(['is_published' => false]);
        NewsPost::query()->update(['is_published' => false]);
        $event = Event::factory()->create(['slug' => 'public-convening']);
        $event->forceFill(['updated_at' => '2026-09-01 09:30:00'])->save();
        $posts = NewsPost::orderBy('id')->get();
        $posts[0]->update(['slug' => 'available-news', 'is_published' => true, 'published_at' => now()->subDay()]);
        $posts[1]->update(['slug' => 'unscheduled-news', 'is_published' => true, 'published_at' => null]);
        $posts[2]->update(['slug' => 'future-news', 'is_published' => true, 'published_at' => now()->addDay()]);
        $posts[3]->update(['slug' => 'draft-news', 'is_published' => false]);

        $response = $this->get('https://events.example.test/sitemap.xml')->assertOk()
            ->assertDontSee('future-news')->assertDontSee('draft-news')
            ->assertDontSee('pan-african-leadership-summit')
            ->assertDontSee('/admin')->assertDontSee('/download')->assertDontSee('?q=')->assertDontSee('?category=');
        $xml = $this->xml($response->getContent());

        $this->assertCount(66, $xml->xpath('//s:url'));
        $this->assertCount(18, $xml->xpath('//s:lastmod'));
        $eventEntry = $xml->xpath('//s:url[s:loc="https://events.example.test/en/events/public-convening"]')[0];
        $this->assertSame('2026-09-01T09:30:00+00:00', (string) $eventEntry->lastmod);

        $this->travel(1)->days();

        $published = $this->xml($this->get('https://events.example.test/sitemap.xml')->getContent());
        $this->assertCount(6, $published->xpath('//s:url[contains(s:loc,"/news/future-news")]'));
    }

    public function test_a_local_sitemap_uses_canonical_urls_and_is_not_indexable(): void
    {
        config(['seo.indexing_enabled' => true]);

        $this->get('http://127.0.0.1:8001/sitemap.xml')
            ->assertOk()->assertHeader('X-Robots-Tag', 'noindex, follow')
            ->assertSee('https://events.example.test/en', false)
            ->assertDontSee('127.0.0.1')->assertDontSee('localhost');
    }

    public function test_sitemap_urls_cannot_inject_xml_markup(): void
    {
        Event::factory()->create(['slug' => 'event&<untrusted>']);

        $response = $this->get('/sitemap.xml')->assertOk()->assertDontSee('<untrusted>', false);

        $xml = $this->xml($response->getContent());
        $this->assertCount(54, $xml->xpath('//s:url'));
        $this->assertCount(0, $xml->xpath('//untrusted'));
    }

    private function enableProductionIndexing(): void
    {
        $this->app->instance('env', 'production');
        config(['app.env' => 'production', 'seo.indexing_enabled' => true]);
    }

    private function xml(string $content): SimpleXMLElement
    {
        $xml = simplexml_load_string($content, SimpleXMLElement::class, LIBXML_NONET);
        $this->assertInstanceOf(SimpleXMLElement::class, $xml);
        $xml->registerXPathNamespace('s', 'http://www.sitemaps.org/schemas/sitemap/0.9');

        return $xml;
    }
}
