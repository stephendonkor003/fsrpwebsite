@extends('layouts.site')

@section('title', __('ui.events.title'))
@section('meta_description', __('ui.events.subtitle'))

@section('content')
    @include('site.partials.page-hero', [
        'title' => __('ui.events.title'),
        'eyebrow' => __('portal.event_series_eyebrow'),
        'summary' => __('portal.event_series_summary'),
        'heroImage' => asset('images/seed-investment-summit/seed-investment-summit-2026.jpeg'),
    ])

    <section class="listing-toolbar-wrap">
        <div class="container">
            <form class="listing-toolbar" method="get" action="{{ route('events.index', $locale) }}">
                <label class="search-field">
                    <span class="sr-only">{{ __('ui.search.title') }}</span>
                    <svg aria-hidden="true" viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m16 16 5 5"/></svg>
                    <input type="search" name="q" value="{{ $search }}" placeholder="{{ __('ui.events.search_placeholder') }}">
                </label>
                <label>
                    <span>{{ __('ui.events.period') }}</span>
                    <select name="period">
                        <option value="all" @selected($period === 'all')>{{ __('ui.common.all') }}</option>
                        <option value="upcoming" @selected($period === 'upcoming')>{{ __('ui.events.upcoming') }}</option>
                        <option value="past" @selected($period === 'past')>{{ __('ui.events.past') }}</option>
                    </select>
                </label>
                <label>
                    <span>{{ __('ui.events.delivery_mode') }}</span>
                    <select name="mode">
                        <option value="">{{ __('ui.common.all_formats') }}</option>
                        @foreach(['in-person','online','hybrid'] as $option)<option value="{{ $option }}" @selected($mode === $option)>{{ __('ui.events.modes.'.$option) }}</option>@endforeach
                    </select>
                </label>
                <button class="button button-dark" type="submit">{{ __('ui.filter.apply') }}</button>
            </form>
        </div>
    </section>

    <section class="section listing-section">
        <div class="container">
            <div class="listing-intro">
                <div>
                    <p class="eyebrow"><span></span>{{ match ($period) { 'past' => __('ui.events.past'), 'upcoming' => __('ui.events.upcoming'), default => __('ui.common.all') } }}</p>
                    <h2>{{ $period === 'all' ? __('ui.events.title') : __('ui.events.discover_title') }}</h2>
                </div>
                <p>{{ __('ui.pagination.showing', ['from' => $events->firstItem() ?? 0, 'to' => $events->lastItem() ?? 0, 'total' => $events->total()]) }}</p>
            </div>
            <div class="listing-grid event-listing-grid">
                @forelse($events as $event)
                    @php
                        $isPastEvent = $event->end_at?->isPast()
                            ?? ($event->start_at?->lt(now()->startOfDay()) ?? false);
                    @endphp
                    <article class="listing-card event-list-card">
                        <a class="listing-image" href="{{ route('events.show', [$locale, $event->slug]) }}">
                            <img src="{{ $event->image ?: asset('images/seed-investment-summit/seed-investment-summit-2026.jpeg') }}" alt="{{ $event->translate('title') }}" loading="lazy" decoding="async">
                            <span class="event-mode">{{ __('ui.events.modes.'.$event->mode) }}</span>
                            @if($event->is_featured && ! $isPastEvent)
                                <span class="featured-label">{{ __('portal.current_event') }}</span>
                            @elseif($isPastEvent)
                                <span class="featured-label">{{ __('portal.past_event') }}</span>
                            @endif
                        </a>
                        <div class="listing-content">
                            <div class="event-date-row"><div class="event-date-block"><strong>{{ $event->start_at?->format('d') }}</strong><span>{{ $event->start_at?->translatedFormat('M Y') }}</span></div><span>{{ $event->end_at?->translatedFormat('d M Y') }}</span></div>
                            <p class="card-kicker">{{ $event->translate('venue') }}</p>
                            <h3><a href="{{ route('events.show', [$locale, $event->slug]) }}">{{ $event->translate('title') }}</a></h3>
                            <p>{{ $event->translate('excerpt') }}</p>
                            <a class="text-link text-link-small" href="{{ route('events.show', [$locale, $event->slug]) }}">{{ __('ui.actions.view_details') }} <span aria-hidden="true">↗</span></a>
                        </div>
                    </article>
                @empty
                    <div class="empty-state listing-empty"><span>○</span><h2>{{ __('ui.empty.no_events_title') }}</h2><p>{{ __('ui.empty.no_events_text') }}</p><a class="button button-dark" href="{{ route('events.index', $locale) }}">{{ __('ui.filter.clear') }}</a></div>
                @endforelse
            </div>
            <div class="pagination-wrap">{{ $events->links() }}</div>
        </div>
    </section>
@endsection
