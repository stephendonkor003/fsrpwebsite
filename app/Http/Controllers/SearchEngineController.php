<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\NewsPost;
use App\Seo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Response;

class SearchEngineController extends Controller
{
    public function __construct(private readonly Seo $seo) {}

    public function robots(): Response
    {
        $lines = $this->seo->publicDeployment()
            ? ['User-agent: *', 'Allow: /']
            : ['User-agent: *', 'Disallow: /'];

        if ($this->seo->indexable()) {
            $lines[] = '';
            $lines[] = 'Sitemap: '.$this->seo->absolute('/sitemap.xml');
        }

        return response(implode("\n", $lines)."\n", 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    public function sitemap(): Response
    {
        $pages = [];

        foreach (['home', 'about', 'events.index', 'programs', 'resources.index', 'news.index', 'faq'] as $route) {
            $pages[] = ['route' => $route, 'parameters' => [], 'lastmod' => null];
        }

        foreach (Event::where('is_published', true)->select(['id', 'slug', 'updated_at'])->orderBy('id')->lazyById(500) as $event) {
            $pages[] = ['route' => 'events.show', 'parameters' => ['slug' => $event->slug], 'lastmod' => $event->updated_at?->toAtomString()];
        }

        foreach (NewsPost::where('is_published', true)->where(function (Builder $query): void {
            $query->whereNull('published_at')->orWhere('published_at', '<=', now());
        })->select(['id', 'slug', 'updated_at'])->orderBy('id')->lazyById(500) as $post) {
            $pages[] = ['route' => 'news.show', 'parameters' => ['slug' => $post->slug], 'lastmod' => $post->updated_at?->toAtomString()];
        }

        $entries = [];
        $locales = array_keys(config('locales.supported'));

        foreach ($pages as $page) {
            $alternates = [];

            foreach ($locales as $locale) {
                $alternates[$locale] = $this->seo->url($page['route'], array_merge($page['parameters'], ['locale' => $locale]));
            }

            $alternates['x-default'] = $alternates[config('locales.default', 'en')];

            foreach ($locales as $locale) {
                $entries[] = ['url' => $alternates[$locale], 'alternates' => $alternates, 'lastmod' => $page['lastmod']];
            }
        }

        return response()->view('site.sitemap', ['entries' => $entries], 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
