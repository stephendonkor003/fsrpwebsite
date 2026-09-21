<?php

namespace App\Http\Controllers;

use App\EventGallery;
use App\Models\Event;
use App\Models\EventResource;
use App\Models\Faq;
use App\Models\HomeSection;
use App\Models\NewsPost;
use App\Models\Page;
use App\Models\Program;
use App\Models\Setting;
use App\Models\Slide;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SiteController extends Controller
{
    public function home(): View
    {
        $seedSummitSlug = (string) config('seed_summit.event_slug');
        $featuredEvent = $this->currentEvent([
            'resources' => fn (HasMany $query) => $query->with('event')->published()->orderBy('sort_order'),
            'sessions' => fn (HasMany $query) => $query->with('event')->published()->orderBy('start_at')->orderBy('sort_order'),
        ]);

        $slides = $featuredEvent === null
            ? collect()
            : Slide::query()
                ->where('is_active', true)
                ->where('button_url', '/events/'.$seedSummitSlug)
                ->orderBy('sort_order')
                ->get();

        return view('site.home', array_merge($this->shared(), [
            'slides' => $slides,
            'speakers' => $this->speakerProfiles(),
            'featuredEvent' => $featuredEvent,
            'resources' => $featuredEvent?->resources->take(3) ?? collect(),
            'sessions' => $featuredEvent?->sessions->take(6) ?? collect(),
            'events' => new EloquentCollection($featuredEvent === null ? [] : [$featuredEvent]),
            'programs' => collect(),
            'newsPosts' => collect(),
            'faqs' => collect(),
            'homeSections' => HomeSection::where('is_active', true)->orderBy('sort_order')->get(),
        ]));
    }

    public function about(): View
    {
        return view('site.about', array_merge($this->shared(), [
            'page' => Page::where('key', 'about')->where('is_published', true)->first(),
            'programs' => Program::where('is_published', true)->orderBy('sort_order')->get(),
        ]));
    }

    public function events(Request $request): View
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:500'],
            'mode' => ['nullable', 'string', 'in:in-person,online,hybrid'],
            'period' => ['nullable', 'string', 'in:all,upcoming,past'],
        ]);
        $search = trim($validated['q'] ?? '');
        $mode = $validated['mode'] ?? '';
        $period = $validated['period'] ?? 'all';
        $events = Event::where('is_published', true)
            ->when($search !== '', fn (Builder $query) => $this->searchTranslations($query, ['title'], $search))
            ->when(in_array($mode, ['in-person', 'online', 'hybrid'], true), fn (Builder $query) => $query->where('mode', $mode))
            ->when($period === 'past', fn (Builder $query) => $query
                ->where(function (Builder $past): void {
                    $past->where('end_at', '<', now())
                        ->orWhere(function (Builder $withoutEndDate): void {
                            $withoutEndDate->whereNull('end_at')->where('start_at', '<', now()->startOfDay());
                        });
                })
                ->orderByDesc('start_at'))
            ->when($period === 'upcoming', fn (Builder $query) => $query->currentOrUpcoming()->orderBy('start_at'))
            ->when($period === 'all', fn (Builder $query) => $query->orderByDesc('start_at'))
            ->paginate(9)
            ->withQueryString();

        return view('site.events.index', array_merge($this->shared(), compact('events', 'search', 'mode', 'period')));
    }

    public function event(EventGallery $eventGallery, string $locale, string $slug): View
    {
        $event = Event::where('slug', $slug)->where('is_published', true)->firstOrFail();

        return view('site.events.show', array_merge($this->shared(), [
            'event' => $event,
            'galleryDays' => $eventGallery->forEvent($event->slug),
            'sessions' => $event->sessions()->where('is_published', true)->orderBy('start_at')->orderBy('sort_order')->get(),
            'resources' => $event->resources()->with('event')->published()->orderBy('sort_order')->orderByDesc('created_at')->get(),
        ]));
    }

    public function news(Request $request): View
    {
        $validated = $request->validate(['q' => ['nullable', 'string', 'max:500']]);
        $search = trim($validated['q'] ?? '');
        $posts = NewsPost::where('is_published', true)
            ->where(function (Builder $query): void {
                $query->whereNull('published_at')->orWhere('published_at', '<=', now());
            })
            ->when($search !== '', fn (Builder $query) => $this->searchTranslations($query, ['title'], $search))
            ->orderByDesc('published_at')
            ->paginate(9)
            ->withQueryString();

        return view('site.news.index', array_merge($this->shared(), compact('posts', 'search')));
    }

    public function newsPost(string $locale, string $slug): View
    {
        $post = NewsPost::where('slug', $slug)->where('is_published', true)
            ->where(function (Builder $query): void {
                $query->whereNull('published_at')->orWhere('published_at', '<=', now());
            })->firstOrFail();

        return view('site.news.show', array_merge($this->shared(), [
            'post' => $post,
            'relatedPosts' => NewsPost::where('is_published', true)->whereKeyNot($post->getKey())
                ->where(function (Builder $query): void {
                    $query->whereNull('published_at')->orWhere('published_at', '<=', now());
                })->orderByDesc('published_at')->limit(3)->get(),
        ]));
    }

    public function programs(): View
    {
        $currentEvent = $this->currentEvent([
            'sessions' => fn (HasMany $query) => $query->published()->orderBy('start_at')->orderBy('sort_order'),
            'resources' => fn (HasMany $query) => $query->with('event')->published()->orderBy('sort_order'),
        ]);

        return view('site.programs', array_merge($this->shared(), [
            'programmeEvents' => new EloquentCollection($currentEvent === null ? [] : [$currentEvent]),
            'programs' => collect(),
            'sessions' => $currentEvent?->sessions ?? collect(),
            'resources' => $currentEvent?->resources->where('category', 'programme')->values() ?? collect(),
        ]));
    }

    public function resources(Request $request): View
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:500'],
            'category' => ['nullable', 'string', Rule::in(array_keys(EventResource::CATEGORIES))],
            'language' => ['nullable', 'string', Rule::in(array_keys(config('locales.supported')))],
            'event' => ['nullable', 'integer', 'min:1', 'max:9223372036854775807'],
        ]);
        $search = trim($validated['q'] ?? '');
        $category = $validated['category'] ?? '';
        $language = $validated['language'] ?? '';
        $eventId = $validated['event'] ?? '';
        $currentEvent = $this->currentEvent();
        $resources = EventResource::with('event')->published()
            ->where('event_id', $currentEvent?->getKey() ?? 0)
            ->when($search !== '', fn (Builder $query) => $this->searchTranslations($query, ['title', 'description'], $search))
            ->when($category !== '', fn (Builder $query) => $query->where('category', $category))
            ->when($language !== '', fn (Builder $query) => $query->where('language', $language))
            ->when($eventId !== '', fn (Builder $query) => $query->where('event_id', $eventId))
            ->orderBy('sort_order')->orderByDesc('created_at')->paginate(12)->withQueryString();

        return view('site.resources', array_merge($this->shared(), compact('resources', 'search', 'category', 'language', 'eventId'), [
            'resourceEvents' => Event::query()
                ->whereKey($currentEvent?->getKey() ?? 0)
                ->whereHas('resources', fn (Builder $query) => $query->published())
                ->get(),
            'resourceCategories' => EventResource::CATEGORIES,
        ]));
    }

    public function speakers(): View
    {
        return view('site.speakers', array_merge($this->shared(), [
            'speakers' => $this->speakerProfiles(),
        ]));
    }

    public function faq(Request $request): View
    {
        $validated = $request->validate(['q' => ['nullable', 'string', 'max:500']]);
        $search = trim($validated['q'] ?? '');
        $faqs = Faq::where('is_published', true)
            ->when($search !== '', fn (Builder $query) => $this->searchTranslations($query, ['question', 'answer'], $search))
            ->orderBy('sort_order')
            ->get();

        return view('site.faq', array_merge($this->shared(), compact('faqs', 'search')));
    }

    /**
     * @param  array<int, string>  $fields
     */
    private function searchTranslations(Builder $query, array $fields, string $search): void
    {
        $query->where(function (Builder $nested) use ($fields, $search): void {
            foreach ($fields as $field) {
                foreach (array_keys(config('locales.supported')) as $locale) {
                    $nested->orWhereLike("$field->$locale", "%$search%");
                }
            }
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function shared(): array
    {
        return [
            'siteSettings' => Setting::all()->mapWithKeys(fn (Setting $setting): array => [$setting->key => $setting->value])->all(),
            'locales' => config('locales.supported'),
            'locale' => app()->getLocale(),
        ];
    }

    /**
     * @param  array<string, mixed>  $relations
     */
    private function currentEvent(array $relations = []): ?Event
    {
        $query = Event::query()
            ->where('slug', (string) config('seed_summit.event_slug'))
            ->where('is_published', true)
            ->where('is_featured', true)
            ->currentOrUpcoming();

        if ($relations !== []) {
            $query->with($relations);
        }

        return $query->first();
    }

    /**
     * @return array<int, array{source_order: int, name: string, title: string, organisation: string, image: string}>
     */
    private function speakerProfiles(): array
    {
        $profiles = config('seed_summit_speakers', []);

        if (! is_array($profiles)) {
            return [];
        }

        return collect($profiles)
            ->filter(fn (mixed $profile): bool => is_array($profile)
                && isset($profile['source_order'], $profile['name'], $profile['title'], $profile['organisation'], $profile['image']))
            ->sortBy('source_order')
            ->values()
            ->all();
    }
}
