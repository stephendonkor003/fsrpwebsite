<?php

namespace App;

use App\Models\Event;
use App\Models\NewsPost;

class StructuredData
{
    public function __construct(private readonly Seo $seo) {}

    /**
     * @return array{'@context': string, '@graph': array<int, array<string, mixed>>}
     */
    public function graph(string $locale, string $siteName, string $title, string $description, string $canonical, ?Event $event = null, ?NewsPost $post = null): array
    {
        $baseUrl = rtrim($this->seo->baseUrl(), '/');
        $organizationId = $baseUrl.'/#organization';
        $websiteId = $baseUrl.'/#website';
        $pageId = $canonical.'#webpage';
        $page = [
            '@type' => 'WebPage',
            '@id' => $pageId,
            'url' => $canonical,
            'name' => $title,
            'description' => $description,
            'inLanguage' => $locale,
            'isPartOf' => ['@id' => $websiteId],
        ];
        $nodes = [
            [
                '@type' => 'Organization',
                '@id' => $organizationId,
                'name' => $siteName,
                'url' => $baseUrl,
            ],
            [
                '@type' => 'WebSite',
                '@id' => $websiteId,
                'name' => $siteName,
                'url' => $baseUrl,
                'inLanguage' => array_keys(config('locales.supported')),
                'publisher' => ['@id' => $organizationId],
            ],
        ];

        $breadcrumbs = $this->breadcrumbs($locale, $title, $canonical, $event, $post);

        if (count($breadcrumbs) > 1) {
            $breadcrumbId = $canonical.'#breadcrumb';
            $page['breadcrumb'] = ['@id' => $breadcrumbId];
            $nodes[] = [
                '@type' => 'BreadcrumbList',
                '@id' => $breadcrumbId,
                'itemListElement' => $breadcrumbs,
            ];
        }

        if ($event !== null) {
            $entity = $this->event($event, $locale, $canonical, $pageId);
            $page['mainEntity'] = ['@id' => $entity['@id']];
            $nodes[] = $entity;
        } elseif ($post !== null) {
            $entity = $this->article($post, $locale, $canonical, $pageId, $organizationId);
            $page['mainEntity'] = ['@id' => $entity['@id']];
            $nodes[] = $entity;
        }

        $nodes[] = $page;

        return ['@context' => 'https://schema.org', '@graph' => $nodes];
    }

    /**
     * @return array<int, array{'@type': string, position: int, name: string, item: string}>
     */
    private function breadcrumbs(string $locale, string $title, string $canonical, ?Event $event, ?NewsPost $post): array
    {
        $homeUrl = $this->seo->url('home', ['locale' => $locale]);
        $items = [[
            '@type' => 'ListItem',
            'position' => 1,
            'name' => __('ui.nav.home', [], $locale),
            'item' => $homeUrl,
        ]];

        if (rtrim($canonical, '/') === rtrim($homeUrl, '/')) {
            return $items;
        }

        if ($event !== null || $post !== null) {
            $items[] = [
                '@type' => 'ListItem',
                'position' => 2,
                'name' => __($event !== null ? 'ui.nav.events' : 'ui.nav.news', [], $locale),
                'item' => $this->seo->url($event !== null ? 'events.index' : 'news.index', ['locale' => $locale]),
            ];
        }

        $name = $event?->translate('title', $locale) ?? $post?->translate('title', $locale) ?? $this->pageLabel($locale, $title, $canonical);
        $items[] = [
            '@type' => 'ListItem',
            'position' => count($items) + 1,
            'name' => $name,
            'item' => $canonical,
        ];

        return $items;
    }

    private function pageLabel(string $locale, string $title, string $canonical): string
    {
        foreach ([
            'about' => 'ui.nav.about',
            'events.index' => 'ui.nav.events',
            'news.index' => 'ui.nav.news',
            'programs' => 'ui.nav.program_outline',
            'faq' => 'ui.nav.faq',
        ] as $route => $translation) {
            if (parse_url($canonical, PHP_URL_PATH) === parse_url($this->seo->url($route, ['locale' => $locale]), PHP_URL_PATH)) {
                return __($translation, [], $locale);
            }
        }

        return $title;
    }

    /**
     * @return array<string, mixed>
     */
    private function event(Event $event, string $locale, string $canonical, string $pageId): array
    {
        $data = [
            '@type' => 'Event',
            '@id' => $canonical.'#event',
            'url' => $canonical,
            'name' => $event->translate('title', $locale),
            'description' => $event->translate('excerpt', $locale),
            'mainEntityOfPage' => ['@id' => $pageId],
        ];

        if ($event->start_at !== null) {
            $data['startDate'] = $event->start_at->toDateString();
        }

        if ($event->end_at !== null) {
            $data['endDate'] = $event->end_at->toDateString();
        }

        $attendance = match ($event->mode) {
            'in-person' => 'OfflineEventAttendanceMode',
            'online' => 'OnlineEventAttendanceMode',
            'hybrid' => 'MixedEventAttendanceMode',
            default => null,
        };

        if ($attendance !== null) {
            $data['eventAttendanceMode'] = 'https://schema.org/'.$attendance;
        }

        $venue = trim($event->translate('venue', $locale) ?? '');
        $isVenueUrl = filter_var($venue, FILTER_VALIDATE_URL) !== false && in_array(strtolower((string) parse_url($venue, PHP_URL_SCHEME)), ['http', 'https'], true);

        if ($venue !== '' && $event->mode !== 'online' && ! $isVenueUrl) {
            $data['location'] = ['@type' => 'Place', 'name' => $venue, 'address' => $venue];
        } elseif ($isVenueUrl && in_array($event->mode, ['online', 'hybrid'], true)) {
            $data['location'] = ['@type' => 'VirtualLocation', 'url' => $venue];
        }

        if (is_string($event->image) && $event->image !== '') {
            $data['image'] = [$this->seo->absolute($event->image)];
        }

        return array_filter($data, fn (mixed $value): bool => $value !== null && $value !== '');
    }

    /**
     * @return array<string, mixed>
     */
    private function article(NewsPost $post, string $locale, string $canonical, string $pageId, string $organizationId): array
    {
        $data = [
            '@type' => 'NewsArticle',
            '@id' => $canonical.'#article',
            'url' => $canonical,
            'headline' => $post->translate('title', $locale),
            'description' => $post->translate('excerpt', $locale),
            'articleSection' => $post->translate('category', $locale),
            'inLanguage' => $locale,
            'mainEntityOfPage' => ['@id' => $pageId],
            'publisher' => ['@id' => $organizationId],
            'author' => [
                '@type' => 'Organization',
                'name' => __('seo.editorial_team', [], $locale),
                'url' => $this->seo->url('about', ['locale' => $locale]),
            ],
        ];

        if ($post->published_at !== null) {
            $data['datePublished'] = $post->published_at->toDateString();
        }

        if (is_string($post->image) && $post->image !== '') {
            $data['image'] = [$this->seo->absolute($post->image)];
        }

        return array_filter($data, fn (mixed $value): bool => $value !== null && $value !== '');
    }
}
