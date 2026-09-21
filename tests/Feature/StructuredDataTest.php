<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\NewsPost;
use App\StructuredData;
use Tests\TestCase;

class StructuredDataTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['seo.canonical_url' => 'https://events.example.test']);
    }

    public function test_public_page_graph_connects_canonical_site_and_localized_breadcrumbs(): void
    {
        app()->setLocale('en');

        $graph = app(StructuredData::class)->graph('fr', 'African Union Events', 'À propos · African Union Events', 'Une série d’événements continentaux.', 'https://events.example.test/fr/about');
        $nodes = array_column($graph['@graph'], null, '@type');

        $this->assertSame('https://schema.org', $graph['@context']);
        $this->assertSame('https://events.example.test', $nodes['WebSite']['url']);
        $this->assertSame('African Union Events', $nodes['Organization']['name']);
        $this->assertSame(['@id' => $nodes['Organization']['@id']], $nodes['WebSite']['publisher']);
        $this->assertSame(['@id' => $nodes['WebSite']['@id']], $nodes['WebPage']['isPartOf']);
        $this->assertSame('fr', $nodes['WebPage']['inLanguage']);
        $this->assertSame(['https://events.example.test/fr', 'https://events.example.test/fr/about'], array_column($nodes['BreadcrumbList']['itemListElement'], 'item'));
        $this->assertSame(['Accueil', 'À propos'], array_column($nodes['BreadcrumbList']['itemListElement'], 'name'));
        $this->assertSame([1, 2], array_column($nodes['BreadcrumbList']['itemListElement'], 'position'));
        $this->assertArrayNotHasKey('logo', $nodes['Organization']);
        $this->assertArrayNotHasKey('Event', $nodes);
        $this->assertArrayNotHasKey('NewsArticle', $nodes);
        $this->assertArrayNotHasKey('FAQPage', $nodes);
        $this->assertArrayNotHasKey('VideoObject', $nodes);
    }

    public function test_homepage_does_not_add_an_incomplete_single_item_breadcrumb(): void
    {
        $graph = app(StructuredData::class)->graph('en', 'African Union Events', 'African Union events and programmes', 'Events and learning.', 'https://events.example.test/en');
        $nodes = array_column($graph['@graph'], null, '@type');

        $this->assertCount(3, $nodes);
        $this->assertArrayNotHasKey('BreadcrumbList', $nodes);
        $this->assertArrayNotHasKey('breadcrumb', $nodes['WebPage']);
        $this->assertArrayNotHasKey('mainEntity', $nodes['WebPage']);
    }

    public function test_partner_event_uses_confirmed_dates_and_venue_without_invented_details(): void
    {
        $event = Event::factory()->make([
            'slug' => '22nd-caadp-partnership-platform',
            'title' => ['en' => '22nd CAADP Partnership Platform', 'ar' => 'منصة شراكة CAADP الثانية والعشرون'],
            'excerpt' => ['en' => 'Partner event convened by African Union Commission (AUC).'],
            'venue' => ['en' => 'Harare, Zimbabwe', 'ar' => 'هراري، زيمبابوي'],
            'start_at' => '2026-09-15 00:00:00',
            'end_at' => '2026-09-18 23:59:59',
            'image' => '/images/caadp/caadp-partnership-1.jpeg',
            'registration_url' => null,
        ]);
        $canonical = 'https://events.example.test/ar/events/22nd-caadp-partnership-platform';

        $graph = app(StructuredData::class)->graph('ar', 'African Union Events', $event->translate('title', 'ar'), 'فعالية شريكة.', $canonical, $event);
        $nodes = array_column($graph['@graph'], null, '@type');
        $schema = $nodes['Event'];

        $this->assertSame('منصة شراكة CAADP الثانية والعشرون', $schema['name']);
        $this->assertSame('2026-09-15', $schema['startDate']);
        $this->assertSame('2026-09-18', $schema['endDate']);
        $this->assertSame(['@type' => 'Place', 'name' => 'هراري، زيمبابوي', 'address' => 'هراري، زيمبابوي'], $schema['location']);
        $this->assertSame('https://schema.org/OfflineEventAttendanceMode', $schema['eventAttendanceMode']);
        $this->assertSame(['https://events.example.test/images/caadp/caadp-partnership-1.jpeg'], $schema['image']);
        $this->assertSame(['@id' => $canonical.'#event'], $nodes['WebPage']['mainEntity']);
        $this->assertSame(['https://events.example.test/ar', 'https://events.example.test/ar/events', $canonical], array_column($nodes['BreadcrumbList']['itemListElement'], 'item'));
        $this->assertArrayNotHasKey('organizer', $schema);
        $this->assertArrayNotHasKey('offers', $schema);
        $this->assertArrayNotHasKey('performer', $schema);
        $this->assertArrayNotHasKey('aggregateRating', $schema);
        $this->assertArrayNotHasKey('eventStatus', $schema);
    }

    public function test_online_event_uses_the_actual_venue_url_as_a_virtual_location(): void
    {
        $event = Event::factory()->make([
            'mode' => 'online',
            'venue' => ['en' => 'https://example.test/live-session'],
            'registration_url' => 'https://example.test/register',
        ]);

        $graph = app(StructuredData::class)->graph('en', 'African Union Events', 'Online event', 'Event information.', 'https://events.example.test/en/events/online-event', $event);
        $nodes = array_column($graph['@graph'], null, '@type');

        $this->assertSame(['@type' => 'VirtualLocation', 'url' => 'https://example.test/live-session'], $nodes['Event']['location']);
        $this->assertSame('https://schema.org/OnlineEventAttendanceMode', $nodes['Event']['eventAttendanceMode']);
    }

    public function test_unknown_event_dates_and_stream_location_are_not_inferred_from_registration(): void
    {
        $event = Event::factory()->make([
            'mode' => 'online',
            'venue' => ['en' => 'Online'],
            'registration_url' => 'https://example.test/register',
            'start_at' => null,
            'end_at' => null,
            'image' => null,
        ]);

        $graph = app(StructuredData::class)->graph('en', 'African Union Events', 'Online event', 'Event information.', 'https://events.example.test/en/events/online-event', $event);
        $schema = array_column($graph['@graph'], null, '@type')['Event'];

        $this->assertArrayNotHasKey('startDate', $schema);
        $this->assertArrayNotHasKey('endDate', $schema);
        $this->assertArrayNotHasKey('location', $schema);
        $this->assertArrayNotHasKey('offers', $schema);
        $this->assertArrayNotHasKey('image', $schema);
    }

    public function test_article_schema_uses_the_visible_editorial_attribution_and_publication_date(): void
    {
        $post = new NewsPost([
            'slug' => 'participant-information',
            'title' => ['en' => 'Participant information', 'fr' => 'Informations aux participants'],
            'excerpt' => ['fr' => 'Dates et informations pratiques.'],
            'category' => ['fr' => 'Actualité d’un événement partenaire'],
            'published_at' => '2026-09-08 14:30:00',
            'image' => 'https://events.example.test/images/seed-investment-summit/seed-investment-summit-2026.jpeg',
        ]);
        $canonical = 'https://events.example.test/fr/news/participant-information';

        $graph = app(StructuredData::class)->graph('fr', 'African Union Events', 'Informations aux participants', 'Informations pratiques.', $canonical, post: $post);
        $nodes = array_column($graph['@graph'], null, '@type');
        $schema = $nodes['NewsArticle'];

        $this->assertSame('Informations aux participants', $schema['headline']);
        $this->assertSame('Actualité d’un événement partenaire', $schema['articleSection']);
        $this->assertSame('2026-09-08', $schema['datePublished']);
        $this->assertSame(__('seo.editorial_team', [], 'fr'), $schema['author']['name']);
        $this->assertSame('Organization', $schema['author']['@type']);
        $this->assertSame('https://events.example.test/fr/about', $schema['author']['url']);
        $this->assertSame(['@id' => $nodes['Organization']['@id']], $schema['publisher']);
        $this->assertSame(['@id' => $canonical.'#article'], $nodes['WebPage']['mainEntity']);
        $this->assertSame(['https://events.example.test/fr', 'https://events.example.test/fr/news', $canonical], array_column($nodes['BreadcrumbList']['itemListElement'], 'item'));
        $this->assertArrayNotHasKey('Event', $nodes);
        $this->assertArrayNotHasKey('dateModified', $schema);
    }

    public function test_article_without_a_publication_date_does_not_invent_one(): void
    {
        $post = new NewsPost(['title' => ['en' => 'Programme information'], 'published_at' => null]);

        $graph = app(StructuredData::class)->graph('en', 'African Union Events', 'Programme information', '', 'https://events.example.test/en/news/programme-information', post: $post);
        $schema = array_column($graph['@graph'], null, '@type')['NewsArticle'];

        $this->assertArrayNotHasKey('datePublished', $schema);
        $this->assertArrayNotHasKey('image', $schema);
        $this->assertArrayNotHasKey('description', $schema);
        $this->assertArrayNotHasKey('articleSection', $schema);
    }
}
