<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventResource;
use App\Models\Faq;
use App\Models\HomeSection;
use App\Models\NewsPost;
use App\Models\Page;
use App\Models\Program;
use App\Models\Session;
use App\Models\Setting;
use App\Models\Slide;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SiteController extends Controller
{
    public function home(): View
    {
        return view('site.home', array_merge($this->shared(), [
            'slides' => Slide::where('is_active', true)->orderByRaw("(video_url IS NOT NULL AND video_url <> '') DESC")->orderBy('sort_order')->get(),
            'speakers' => $this->speakerProfiles(),
            'featuredEvent' => Event::where('is_published', true)->where('is_featured', true)->currentOrUpcoming()
                ->with(['resources' => fn (HasMany $query) => $query->published()->orderBy('sort_order')])->orderBy('start_at')->first(),
            'resources' => EventResource::with('event')->published()->latest()->limit(3)->get(),
            'sessions' => Session::with('event')->published()->where('start_at', '>=', now()->startOfDay())->orderBy('start_at')->limit(6)->get(),
            'events' => Event::where('is_published', true)->currentOrUpcoming()->orderBy('start_at')->limit(4)->get(),
            'programs' => Program::where('is_published', true)->orderBy('sort_order')->get(),
            'newsPosts' => NewsPost::where('is_published', true)->where(function (Builder $query): void {
                $query->whereNull('published_at')->orWhere('published_at', '<=', now());
            })->orderByDesc('published_at')->limit(3)->get(),
            'faqs' => Faq::where('is_published', true)->orderBy('sort_order')->limit(4)->get(),
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
            'period' => ['nullable', 'string', 'in:upcoming,past'],
        ]);
        $search = trim($validated['q'] ?? '');
        $mode = $validated['mode'] ?? '';
        $period = $validated['period'] ?? 'upcoming';
        $events = Event::where('is_published', true)
            ->when($search !== '', fn (Builder $query) => $this->searchTranslations($query, ['title'], $search))
            ->when(in_array($mode, ['in-person', 'online', 'hybrid'], true), fn (Builder $query) => $query->where('mode', $mode))
            ->when($period === 'past', fn (Builder $query) => $query->where('end_at', '<', now())->orderByDesc('start_at'))
            ->when($period !== 'past', fn (Builder $query) => $query->currentOrUpcoming()->orderBy('start_at'))
            ->paginate(9)
            ->withQueryString();

        return view('site.events.index', array_merge($this->shared(), compact('events', 'search', 'mode', 'period')));
    }

    public function event(string $locale, string $slug): View
    {
        $event = Event::where('slug', $slug)->where('is_published', true)->firstOrFail();

        return view('site.events.show', array_merge($this->shared(), [
            'event' => $event,
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
        return view('site.programs', array_merge($this->shared(), [
            'programmeEvents' => Event::where('is_published', true)
                ->whereHas('sessions', fn (Builder $query) => $query->published())
                ->with([
                    'sessions' => fn (HasMany $query) => $query->published()->orderBy('start_at')->orderBy('sort_order'),
                    'resources' => fn (HasMany $query) => $query->published()->orderBy('sort_order'),
                ])->orderByDesc('start_at')->get(),
            'programs' => Program::where('is_published', true)->orderBy('sort_order')->get(),
            'sessions' => Session::with('event')->published()->where('start_at', '>=', now()->startOfDay())->orderBy('start_at')->limit(8)->get(),
            'resources' => EventResource::with('event')->published()->where('category', 'programme')->orderBy('sort_order')->orderByDesc('created_at')->get(),
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
        $resources = EventResource::with('event')->published()
            ->when($search !== '', fn (Builder $query) => $this->searchTranslations($query, ['title', 'description'], $search))
            ->when($category !== '', fn (Builder $query) => $query->where('category', $category))
            ->when($language !== '', fn (Builder $query) => $query->where('language', $language))
            ->when($eventId !== '', fn (Builder $query) => $query->where('event_id', $eventId))
            ->orderBy('sort_order')->orderByDesc('created_at')->paginate(12)->withQueryString();

        return view('site.resources', array_merge($this->shared(), compact('resources', 'search', 'category', 'language', 'eventId'), [
            'resourceEvents' => Event::where('is_published', true)->whereHas('resources', fn (Builder $query) => $query->published())->orderByDesc('start_at')->get(),
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
     * @return array<int, array{name: string, title: string, organisation: string, image: string}>
     */
    private function speakerProfiles(): array
    {
        return [
            ['name' => 'H.E. Moses Vilakati', 'title' => 'Commissioner, Agriculture, Rural Development, Blue Economy and Sustainable Environment (ARBE)', 'organisation' => 'African Union Commission', 'image' => 'images/speakers/moses-vilakati.png'],
            ['name' => 'Nardos Bekele-Thomas', 'title' => 'CEO of AUDA-NEPAD', 'organisation' => 'African Union Development Agency – NEPAD', 'image' => 'images/speakers/nardos-bekele-thomas.png'],
            ['name' => 'Dr. Anxious Jongwe Masuka', 'title' => 'Minister for Agriculture, Mechanization and Water Resources Development', 'organisation' => 'Zimbabwe', 'image' => 'images/speakers/anxious-jongwe-masuka.png'],
            ['name' => 'Elias Mpedi Magosi', 'title' => 'Executive Secretary', 'organisation' => 'SADC Secretariat', 'image' => 'images/speakers/elias-mpedi-magosi.png'],
            ['name' => 'Gabriel Mbairobe', 'title' => 'Minister of Agriculture and Rural Development, Cameroon, and Chairperson of the African Union Specialized Technical Committee on Agriculture, Rural Development, Water and Environment (STC-ARBE)', 'organisation' => 'Cameroon', 'image' => 'images/speakers/gabriel-mbairobe.png'],
        ];
    }
}
