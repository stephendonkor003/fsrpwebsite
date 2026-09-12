@extends('layouts.site')

@section('title', __('ui.nav.home'))
@section('content')
    @if($homeSections->contains('key', 'hero'))
        <section class="hero fsrp-hero" aria-label="{{ __('ui.home.featured_stories') }}" aria-roledescription="carousel" data-carousel>
            <div class="hero-slides">
                @forelse($slides as $slide)
                    <article class="hero-slide {{ $loop->first ? 'active' : '' }}" data-slide aria-hidden="{{ $loop->first ? 'false' : 'true' }}" @if(! $loop->first) inert @endif>
                        <img src="{{ $slide->image ?: asset('images/fsrp/field-implementation.jpeg') }}" alt="" fetchpriority="{{ $loop->first ? 'high' : 'low' }}">
                        @if($slide->video_url)
                            <video class="hero-video" muted playsinline loop preload="none" poster="{{ $slide->image }}" data-hero-video data-src="{{ $slide->video_url }}" aria-hidden="true"></video>
                        @endif
                        <div class="hero-overlay"></div>
                        <div class="container hero-content">
                            <div class="hero-copy">
                                <p class="eyebrow eyebrow-light"><span></span>{{ $slide->translate('eyebrow') ?: __('portal.descriptor') }}</p>
                                @if($loop->first)<h1>{{ $slide->translate('title') }}</h1>@else<h2 class="hero-title">{{ $slide->translate('title') }}</h2>@endif
                                <p class="hero-summary">{{ $slide->translate('subtitle') }}</p>
                                <div class="hero-actions">
                                    <a class="button button-gold" href="{{ $slide->buttonUrlForLocale($locale) ?? route('events.index', $locale) }}">{{ $slide->translate('button_text') ?: __('portal.explore') }} @include('site.partials.icon', ['name' => 'arrow'])</a>
                                    @if($featuredEvent?->registration_url)
                                        <a class="button button-outline-light" href="{{ $featuredEvent->registration_url }}" target="_blank" rel="noopener">{{ __('ui.actions.register_now') }} @include('site.partials.icon', ['name' => 'external'])</a>
                                    @endif
                                    <a class="hero-secondary" href="{{ route('resources.index', $locale) }}">@include('site.partials.icon', ['name' => 'download']){{ __('portal.downloads') }}</a>
                                </div>
                            </div>
                        </div>
                    </article>
                @empty
                    <article class="hero-slide active" data-slide>
                        <img src="{{ asset('images/fsrp/field-implementation.jpeg') }}" alt="">
                        <div class="hero-overlay"></div>
                        <div class="container hero-content"><div class="hero-copy"><p class="eyebrow eyebrow-light">{{ __('portal.descriptor') }}</p><h1>{{ __('portal.events_title') }}</h1><p class="hero-summary">{{ __('portal.events_summary') }}</p><a class="button button-gold" href="{{ route('events.index', $locale) }}">{{ __('portal.explore') }} @include('site.partials.icon', ['name' => 'arrow'])</a></div></div>
                    </article>
                @endforelse
            </div>
            <div class="container hero-bottom">
                <div class="hero-controls">
                    <button type="button" data-carousel-prev aria-label="{{ __('ui.common.previous_slide') }}"><svg aria-hidden="true" viewBox="0 0 24 24"><path d="m15 18-6-6 6-6"/></svg></button>
                    <div class="hero-dots" aria-label="{{ __('ui.common.choose_slide') }}">
                        @foreach($slides as $slide)<button type="button" data-carousel-dot="{{ $loop->index }}" @class(['active' => $loop->first]) aria-label="{{ __('ui.common.slide_number', ['number' => $loop->iteration]) }}" aria-pressed="{{ $loop->first ? 'true' : 'false' }}"><span></span></button>@endforeach
                    </div>
                    <button type="button" data-carousel-next aria-label="{{ __('ui.common.next_slide') }}"><svg aria-hidden="true" viewBox="0 0 24 24"><path d="m9 18 6-6-6-6"/></svg></button>
                    <button class="carousel-pause" type="button" data-carousel-pause aria-label="{{ __('portal.pause') }}" data-play-label="{{ __('portal.resume') }}" data-pause-label="{{ __('portal.pause') }}"><svg class="pause-icon" aria-hidden="true" viewBox="0 0 24 24"><path d="M9 5v14M15 5v14"/></svg><svg class="play-icon" aria-hidden="true" viewBox="0 0 24 24"><path d="m8 5 11 7-11 7Z"/></svg></button>
                </div>
                <span class="hero-caption">{{ __('portal.photo_credit') }}</span>
            </div>
        </section>
        @if($featuredEvent)
            <section class="next-event-bar">
                <div class="container next-event-inner">
                    <div class="next-event-label"><span class="status-dot"></span>{{ __('portal.next_event') }}</div>
                    <a class="next-event-name" href="{{ route('events.show', [$locale, $featuredEvent->slug]) }}">{{ $featuredEvent->translate('title') }}</a>
                    <span class="next-event-date">@include('site.partials.icon', ['name' => 'calendar']){{ $featuredEvent->start_at?->translatedFormat('d') }}–{{ $featuredEvent->end_at?->translatedFormat('d M Y') }}</span>
                    <div class="hero-countdown" data-countdown data-countdown-target="{{ $featuredEvent->start_at?->toIso8601String() }}" data-countdown-finished="{{ __('portal.countdown_finished') }}">
                        <span class="countdown-label">{{ __('portal.countdown_to_event') }}</span>
                        <div class="countdown-grid">
                            <div class="countdown-item"><strong data-countdown-part="days">00</strong><small>{{ __('portal.countdown_days') }}</small></div>
                            <div class="countdown-item"><strong data-countdown-part="hours">00</strong><small>{{ __('portal.countdown_hours') }}</small></div>
                            <div class="countdown-item"><strong data-countdown-part="minutes">00</strong><small>{{ __('portal.countdown_minutes') }}</small></div>
                            <div class="countdown-item"><strong data-countdown-part="seconds">00</strong><small>{{ __('portal.countdown_seconds') }}</small></div>
                        </div>
                        <p class="countdown-finished" data-countdown-finished hidden>{{ __('portal.event_has_started') }}</p>
                    </div>
                    <a class="next-event-arrow" href="{{ route('events.show', [$locale, $featuredEvent->slug]) }}" aria-label="{{ __('portal.event_details') }}">@include('site.partials.icon', ['name' => 'arrow'])</a>
                    @if($informationNote = $featuredEvent->resources->firstWhere('category', 'brief'))
                        <a class="next-event-doc" href="{{ route('resources.download', [$locale, $informationNote->id]) }}" download="{{ $informationNote->original_filename }}">{{ __('portal.information_note') }} @include('site.partials.icon', ['name' => 'download'])</a>
                    @endif
                </div>
            </section>
        @endif
    @endif
    <div id="homepage-sections">
        @foreach($homeSections as $homeSection)
            @switch($homeSection->key)
                @case('events') @include('site.sections.events') @break
                @case('programs') @include('site.sections.programs') @break
                @case('sessions') @include('site.sections.sessions') @break
                @case('resources') @include('site.sections.resources') @break
                @case('media') @include('site.sections.media') @break
                @case('news') @if($newsPosts->isNotEmpty()) @include('site.sections.news') @endif @break
                @case('about') @include('site.sections.about') @break
                @case('faq') @include('site.sections.faq') @break
                @case('cta') @include('site.sections.cta') @break
            @endswitch
        @endforeach
    </div>
@endsection
