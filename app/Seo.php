<?php

namespace App;

use App\Models\Event;
use App\Models\NewsPost;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class Seo
{
    public function baseUrl(): string
    {
        return rtrim((string) config('seo.canonical_url', config('app.url')), '/');
    }

    /** @param array<string, mixed> $parameters */
    public function url(string $route, array $parameters = []): string
    {
        return $this->baseUrl().'/'.ltrim(route($route, $parameters, false), '/');
    }

    public function absolute(string $path): string
    {
        if (filter_var($path, FILTER_VALIDATE_URL) && in_array(strtolower((string) parse_url($path, PHP_URL_SCHEME)), ['https', 'http'], true)) {
            return $path;
        }

        return $this->baseUrl().'/'.ltrim($path, '/');
    }

    public function image(): string
    {
        return $this->absolute((string) config('seo.image'));
    }

    public function indexable(): bool
    {
        return $this->publicDeployment() && (bool) config('seo.indexing_enabled');
    }

    public function publicDeployment(): bool
    {
        return app()->isProduction()
            && strcasecmp(request()->getHost(), (string) parse_url($this->baseUrl(), PHP_URL_HOST)) === 0;
    }

    public function robots(Request $request): string
    {
        if ($request->is('admin', 'admin/*', '*/resources/*/download', 'up')) {
            return 'noindex, nofollow';
        }

        if (! $this->indexable() || $this->filtered($request)) {
            return 'noindex, follow';
        }

        return 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1';
    }

    public function filtered(Request $request): bool
    {
        return count(array_diff_key($this->queryParameters($request), ['page' => true])) > 0;
    }

    public function canonical(Request $request): string
    {
        $route = $request->route();

        if (! $route?->getName()) {
            return $this->absolute($request->path());
        }

        return $this->url($route->getName(), array_merge($route->parameters(), $this->queryParameters($request)));
    }

    /** @return array<string, string> */
    public function alternates(Request $request): array
    {
        $route = $request->route();

        if (! $route?->getName() || ! $route->hasParameter('locale') || $this->filtered($request)) {
            return [];
        }

        $alternates = [];

        foreach (array_keys(config('locales.supported')) as $locale) {
            $alternates[$locale] = $this->url($route->getName(), array_merge($route->parameters(), $this->queryParameters($request), ['locale' => $locale]));
        }

        $alternates['x-default'] = $alternates[config('locales.default')];

        return $alternates;
    }

    /** @return array<string, mixed> */
    public function page(Request $request, string $siteName, ?Event $event = null, ?NewsPost $post = null): array
    {
        $key = match ($request->route()?->getName()) {
            'events.index', 'events.show' => 'events',
            'news.index', 'news.show' => 'news',
            'programs' => 'programmes',
            'speakers' => 'speakers',
            'resources.index' => 'resources',
            'about' => 'about',
            'faq' => 'faq',
            default => 'home',
        };
        $title = $event?->translate('title') ?: $post?->translate('title') ?: __('seo.pages.'.$key.'.title');
        $description = $event?->translate('excerpt') ?: $post?->translate('excerpt') ?: __('seo.pages.'.$key.'.description');
        $pageNumber = $this->queryParameters($request)['page'] ?? 1;

        if ($pageNumber > 1) {
            $title .= ' · '.__('seo.page_number', ['number' => $pageNumber]);
        }

        return [
            'title' => $title.' · '.$siteName,
            'description' => Str::limit(preg_replace('/\s+/u', ' ', trim($description)) ?? $description, 180),
            'canonical' => $this->canonical($request),
            'alternates' => $this->alternates($request),
            'robots' => $this->robots($request),
            'image' => $this->image(),
            'image_alt' => __('seo.image_alt'),
            'type' => $post ? 'article' : 'website',
            'locale' => config('seo.social_locales.'.app()->getLocale(), 'en_GB'),
        ];
    }

    /** @return array<string, int|string> */
    private function queryParameters(Request $request): array
    {
        $keys = match ($request->route()?->getName()) {
            'events.index' => ['mode', 'period', 'q', 'page'],
            'news.index' => ['q', 'page'],
            'resources.index' => ['category', 'event', 'language', 'q', 'page'],
            'faq' => ['q'],
            default => [],
        };
        $parameters = [];

        foreach ($keys as $key) {
            $value = $request->query($key);

            if (! is_scalar($value) || trim((string) $value) === '') {
                continue;
            }

            if ($key === 'page') {
                $page = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 2]]);

                if ($page !== false) {
                    $parameters[$key] = $page;
                }

                continue;
            }

            if ($key === 'period' && $value === 'upcoming') {
                continue;
            }

            $parameters[$key] = trim((string) $value);
        }

        return $parameters;
    }
}
