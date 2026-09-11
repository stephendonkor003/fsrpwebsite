<section class="section portal-events">
    <div class="container">
        <div class="section-heading split-heading">
            <div><p class="eyebrow"><span></span>{{ __('portal.events_eyebrow') }}</p><h2>{{ __('portal.events_title') }}</h2></div>
            <a class="text-link" href="{{ route('events.index', $locale) }}">{{ __('portal.all_events') }} @include('site.partials.icon', ['name' => 'arrow'])</a>
        </div>
        <p class="section-intro">{{ __('portal.events_summary') }}</p>
        @forelse($events as $event)
            <article class="spotlight-event">
                <a class="spotlight-image" href="{{ route('events.show', [$locale, $event->slug]) }}">
                    <img src="{{ $event->image ?: asset('images/fsrp/field-implementation.jpeg') }}" alt="{{ $event->translate('title') }}" loading="lazy">
                    <span class="image-label">{{ __('portal.featured') }}</span>
                    @if($event->start_at)<span class="event-date-tile"><strong>{{ $event->start_at->format('d') }}</strong><span>{{ $event->start_at->translatedFormat('M Y') }}</span></span>@endif
                </a>
                <div class="spotlight-copy">
                    <div class="card-meta"><span class="tag tag-green">{{ __('ui.events.modes.'.$event->mode) }}</span>@if($event->slug === '22nd-caadp-partnership-platform')<span class="partner-label">{{ __('portal.partner_event') }} · CAADP</span>@endif</div>
                    <h3><a href="{{ route('events.show', [$locale, $event->slug]) }}">{{ $event->translate('title') }}</a></h3>
                    <p>{{ $event->translate('excerpt') }}</p>
                    <div class="spotlight-facts"><span>@include('site.partials.icon', ['name' => 'calendar']){{ $event->start_at?->translatedFormat('d M') }} – {{ $event->end_at?->translatedFormat('d M Y') }}</span><span>@include('site.partials.icon', ['name' => 'location']){{ $event->translate('venue') }}</span></div>
                    <a class="button button-dark" href="{{ route('events.show', [$locale, $event->slug]) }}">{{ __('portal.event_details') }} @include('site.partials.icon', ['name' => 'arrow'])</a>
                </div>
            </article>
        @empty
            <div class="empty-state"><h3>{{ __('ui.empty.no_events_title') }}</h3><p>{{ __('ui.empty.no_events_text') }}</p><a class="text-link" href="{{ route('events.index', [$locale, 'period' => 'past']) }}">{{ __('portal.all_events') }} →</a></div>
        @endforelse
    </div>
</section>
