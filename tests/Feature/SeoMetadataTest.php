<?php

namespace Tests\Feature;

use App\Models\Event;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class SeoMetadataTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['seo.canonical_url' => 'https://events.example.test', 'seo.indexing_enabled' => true]);
        $this->app->instance('env', 'production');
    }

    public function test_public_homepage_has_a_shared_large_image_and_complete_search_metadata(): void
    {
        $response = $this->get('https://events.example.test/en')->assertOk();
        $document = $this->document($response->getContent());

        $this->assertSame(1, $document->query('//title')->length);
        $this->assertStringContainsString('African Union Events', $document->evaluate('string(//title)'));
        $this->assertSame('African Union Events', $document->evaluate('string(//meta[@property="og:site_name"]/@content)'));
        $this->assertStringContainsString('African Union Events', $document->evaluate('normalize-space(//body)'));
        $this->assertStringNotContainsString('FSRP', $document->evaluate('normalize-space(//body)'));
        $this->assertSame('https://events.example.test/en', $document->evaluate('string(//link[@rel="canonical"]/@href)'));
        $this->assertSame('index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1', $document->evaluate('string(//meta[@name="robots"]/@content)'));
        $this->assertSame('https://events.example.test/images/seed-investment-summit/seed-investment-summit-2026.jpeg', $document->evaluate('string(//meta[@property="og:image"]/@content)'));
        $this->assertSame('https://events.example.test/images/seed-investment-summit/seed-investment-summit-2026.jpeg', $document->evaluate('string(//meta[@name="twitter:image"]/@content)'));
        $this->assertSame('summary_large_image', $document->evaluate('string(//meta[@name="twitter:card"]/@content)'));
        $this->assertSame(7, $document->query('//link[@hreflang]')->length);
        $this->assertSame('https://events.example.test/en', $document->evaluate('string(//link[@hreflang="x-default"]/@href)'));
        $dimensions = getimagesize(public_path('images/seed-investment-summit/seed-investment-summit-2026.jpeg'));
        $this->assertSame([1254, 1254], [$dimensions[0], $dimensions[1]]);
        $graph = $this->graph($document);
        $this->assertSame(['Organization', 'WebSite', 'WebPage'], array_column($graph['@graph'], '@type'));
    }

    public function test_section_descriptions_are_distinct_and_language_specific(): void
    {
        $descriptions = [];

        foreach (['', '/events', '/news', '/program-outline', '/speakers', '/resources', '/about', '/faq'] as $path) {
            $document = $this->document($this->get('https://events.example.test/en'.$path)->assertOk()->getContent());
            $descriptions[] = $document->evaluate('string(//meta[@name="description"]/@content)');
        }

        $this->assertCount(8, array_unique($descriptions));

        $french = $this->document($this->get('https://events.example.test/fr/resources')->assertOk()->getContent());
        $this->assertNotSame($descriptions[5], $french->evaluate('string(//meta[@name="description"]/@content)'));
        $this->assertSame('https://events.example.test/fr/resources', $french->evaluate('string(//link[@hreflang="fr"]/@href)'));
    }

    public function test_detail_metadata_keeps_its_language_and_discards_tracking_parameters(): void
    {
        $event = Event::factory()->create(['slug' => 'regional-forum', 'title' => ['en' => 'Regional Forum', 'fr' => 'Forum régional']]);

        $document = $this->document($this->get('https://events.example.test/fr/events/'.$event->slug.'?utm_source=email&fbclid=tracking')->assertOk()->getContent());

        $this->assertSame('https://events.example.test/fr/events/regional-forum', $document->evaluate('string(//link[@rel="canonical"]/@href)'));
        $this->assertStringContainsString('Forum régional', $document->evaluate('string(//title)'));
        $this->assertSame('https://events.example.test/ar/events/regional-forum', $document->evaluate('string(//link[@hreflang="ar"]/@href)'));
        $this->assertContains('Event', array_column($this->graph($document)['@graph'], '@type'));
    }

    public function test_pagination_has_its_own_canonical_and_language_links(): void
    {
        Event::factory()->count(10)->create();

        $document = $this->document($this->get('https://events.example.test/en/events?page=2&utm_source=email')->assertOk()->getContent());

        $this->assertSame('https://events.example.test/en/events?page=2', $document->evaluate('string(//link[@rel="canonical"]/@href)'));
        $this->assertSame('https://events.example.test/fr/events?page=2', $document->evaluate('string(//link[@hreflang="fr"]/@href)'));
        $this->assertStringContainsString('Page 2', $document->evaluate('string(//title)'));
        $this->assertStringStartsWith('index, follow', $document->evaluate('string(//meta[@name="robots"]/@content)'));
    }

    public function test_search_results_keep_a_correct_canonical_but_are_not_indexed(): void
    {
        $response = $this->get('https://events.example.test/en/resources?q=water&utm_source=email')
            ->assertOk()->assertHeader('X-Robots-Tag', 'noindex, follow');
        $document = $this->document($response->getContent());

        $this->assertSame('https://events.example.test/en/resources?q=water', $document->evaluate('string(//link[@rel="canonical"]/@href)'));
        $this->assertSame('noindex, follow', $document->evaluate('string(//meta[@name="robots"]/@content)'));
        $this->assertSame(0, $document->query('//link[@hreflang]')->length);
    }

    public function test_local_preview_uses_the_public_canonical_but_is_not_indexed(): void
    {
        $this->app->instance('env', 'local');

        $response = $this->get('http://127.0.0.1:8001/en')->assertOk()->assertHeader('X-Robots-Tag', 'noindex, follow');
        $document = $this->document($response->getContent());

        $this->assertSame('noindex, follow', $document->evaluate('string(//meta[@name="robots"]/@content)'));
        $this->assertSame('https://events.example.test/en', $document->evaluate('string(//link[@rel="canonical"]/@href)'));
    }

    #[TestWith(['/admin/login'])]
    #[TestWith(['/en/resources/999999/download'])]
    #[TestWith(['/up'])]
    public function test_noncontent_endpoints_are_not_indexed(string $path): void
    {
        $this->get('https://events.example.test'.$path)->assertHeader('X-Robots-Tag', 'noindex, nofollow');
    }

    public function test_model_text_is_safe_in_metadata_and_json_without_losing_its_value(): void
    {
        $title = 'Food </script><script>alert("x")</script> & resilience';
        $description = 'Partner information "quoted" & participant details.';
        $event = Event::factory()->create(['title' => ['en' => $title], 'excerpt' => ['en' => $description]]);

        $response = $this->get('https://events.example.test/en/events/'.$event->slug)->assertOk()
            ->assertDontSee('</script><script>alert("x")</script>', false);
        $document = $this->document($response->getContent());
        $graph = $this->graph($document);
        $entity = array_values(array_filter($graph['@graph'], fn (array $node): bool => $node['@type'] === 'Event'))[0];

        $this->assertStringStartsWith($title, $document->evaluate('string(//title)'));
        $this->assertSame($description, $document->evaluate('string(//meta[@name="description"]/@content)'));
        $this->assertSame($title, $entity['name']);
        $this->assertSame($description, $entity['description']);
    }

    private function document(string $html): DOMXPath
    {
        $previous = libxml_use_internal_errors(true);
        $document = new DOMDocument;
        $document->loadHTML('<?xml encoding="utf-8" ?>'.$html, LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        return new DOMXPath($document);
    }

    /** @return array<string, mixed> */
    private function graph(DOMXPath $document): array
    {
        return json_decode($document->evaluate('string(//script[@type="application/ld+json"])'), true, 512, JSON_THROW_ON_ERROR);
    }
}
