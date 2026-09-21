@extends('layouts.site')
@section('title', $event->translate('title'))
@section('meta_description', $event->translate('excerpt'))
@section('content')
    @php
        $isCaadpEvent = $event->slug === '22nd-caadp-partnership-platform';
        $isSeedSummitEvent = $event->slug === config('seed_summit.event_slug');
        $isPastEvent = $event->end_at?->isPast()
            ?? ($event->start_at?->lt(now()->startOfDay()) ?? false);
        $registrationUrl = $isPastEvent ? null : $event->registrationUrlForLocale($locale);
        $registrationIsExternal = $registrationUrl ? $event->registrationUrlOpensExternally() : false;
    @endphp
    <section class="detail-hero">
        <img src="{{ $event->image ?: asset('images/seed-investment-summit/seed-investment-summit-2026.jpeg') }}" alt="">
        <div class="page-hero-overlay"></div>
        <div class="container detail-hero-inner">
            <nav class="breadcrumbs" aria-label="{{ __('ui.common.breadcrumbs') }}"><a href="{{ route('home', $locale) }}">{{ __('ui.nav.home') }}</a><span>/</span><a href="{{ route('events.index', $locale) }}">{{ __('ui.nav.events') }}</a></nav>
            <div class="detail-badges"><span class="tag tag-gold">{{ __('ui.events.modes.'.$event->mode) }}</span>@if($isCaadpEvent)<span class="tag tag-outline">{{ __('portal.partner_event') }} · CAADP</span>@endif @if($isPastEvent)<span class="tag tag-outline">{{ __('portal.past_event') }}</span>@elseif($isSeedSummitEvent)<span class="tag tag-outline">{{ __('portal.current_event') }}</span>@endif</div>
            <h1>{{ $event->translate('title') }}</h1><p>{{ $event->translate('excerpt') }}</p>
            <div class="hero-actions">
                @if($programmeFile = $resources->firstWhere('category', 'programme'))
                    <a class="button button-gold" href="{{ route('resources.download', [$locale, $programmeFile->id]) }}" download="{{ $programmeFile->original_filename }}">@include('site.partials.icon', ['name' => 'download']){{ __('portal.download_programme') }}</a>
                @endif
                @if($galleryDays !== [])
                    <a class="button button-outline-light" href="#event-gallery">{{ __('portal.view_gallery') }} @include('site.partials.icon', ['name' => 'arrow'])</a>
                @endif
                @if($isSeedSummitEvent)
                    <a class="hero-secondary" href="#summit-overview">{{ __('ui.events.about_event') }} @include('site.partials.icon', ['name' => 'arrow'])</a>
                @elseif($sessions->isNotEmpty())
                    <a class="hero-secondary" href="#programme">{{ __('portal.daily_programme') }} @include('site.partials.icon', ['name' => 'arrow'])</a>
                @endif
            </div>
        </div>
    </section>
    <section class="detail-facts"><div class="container detail-facts-grid">
        <div>@include('site.partials.icon', ['name' => 'calendar'])<span><small>{{ __('portal.date_range') }}</small><strong>{{ $event->start_at?->translatedFormat('d M') }} – {{ $event->end_at?->translatedFormat('d M Y') }}</strong></span></div>
        <div>@include('site.partials.icon', ['name' => 'location'])<span><small>{{ __('ui.labels.venue') }}</small><strong>{{ $event->translate('venue') }}</strong></span></div>
        <div>@include('site.partials.icon', ['name' => 'people'])<span><small>{{ __('portal.format') }}</small><strong>{{ __('ui.events.modes.'.$event->mode) }}</strong></span></div>
    </div></section>
    <section class="section detail-body-section"><div class="container event-detail-layout">
        <article class="detail-article"><p class="eyebrow"><span></span>{{ __('ui.events.about_event') }}</p><h2>{{ __('ui.events.what_to_expect') }}</h2><div class="prose event-description">{!! nl2br(e($event->translate('body'))) !!}</div></article>
        <aside class="event-participation">
            <span class="step-icon">@include('site.partials.icon', ['name' => $isPastEvent ? 'check' : 'people'])</span>
            @if($isPastEvent)
                <h3>{{ __('portal.event_concluded') }}</h3>
                <p>{{ __('portal.event_concluded_text') }}</p>
            @elseif($registrationUrl)
                <h3>{{ __('ui.actions.register_now') }}</h3>
                <a class="button button-dark" href="{{ $registrationUrl }}" @if($registrationIsExternal) target="_blank" rel="noopener" @endif>{{ __('ui.actions.register_now') }} @include('site.partials.icon', ['name' => $registrationIsExternal ? 'external' : 'arrow'])</a>
            @else
                <h3>{{ __('portal.registration_pending') }}</h3>
                <p>{{ __('portal.registration_pending_text') }}</p>
            @endif
            @if($resources->isNotEmpty())<a class="text-link" href="#event-documents">{{ __('portal.event_resources') }} @include('site.partials.icon', ['name' => 'download'])</a>@endif
            <div class="event-share"><span>{{ __('ui.actions.share') }}</span><button type="button" data-copy-link data-success-label="{{ __('portal.copy_success') }}" data-prompt-label="{{ __('portal.copy_prompt') }}">{{ __('ui.common.copy_link') }}</button></div>
        </aside>
    </div></section>
    @if($isCaadpEvent)
        @include('site.events.partials.caadp-participant-information')
        @include('site.events.partials.caadp-speakers')
    @endif
    @if($isSeedSummitEvent)
        @include('site.events.partials.seed-summit-information')
    @endif
    @if($galleryDays !== [])
        @include('site.events.partials.event-gallery')
    @endif
    @if($sessions->isNotEmpty())
        <section class="section agenda-section" id="programme"><div class="container"><div class="section-heading split-heading"><div><p class="eyebrow"><span></span>{{ __('ui.events.schedule') }}</p><h2>{{ __('portal.daily_programme') }}</h2></div><p>{{ __('portal.daily_summary') }}</p></div>
            <div class="programme-day-list">@foreach($sessions as $session)
                <article class="programme-day">
                    @if($isCaadpEvent)<img class="programme-day-image" src="{{ asset('images/caadp/caadp-partnership-'.(($loop->index % 4) + 1).'.jpeg') }}" alt="" loading="lazy">@endif
                    <div class="programme-day-date"><span>{{ $session->is_all_day ? __('portal.day', ['number' => $loop->iteration]) : $session->start_at?->format('H:i') }}</span><strong>{{ $session->start_at?->translatedFormat('d M') }}</strong><small>{{ $session->is_all_day ? $session->start_at?->translatedFormat('l') : $session->end_at?->format('H:i') }}</small></div><div><span class="resource-category">{{ $session->translate('track') ?: __('portal.daily_focus') }}</span><h3>{{ $session->translate('title') }}</h3><p>{{ $session->translate('summary') }}</p>@if($session->translate('speaker_name'))<p class="programme-speaker">{{ $session->translate('speaker_name') }} · {{ $session->translate('speaker_role') }}</p>@endif</div><span class="programme-day-icon">@include('site.partials.icon', ['name' => 'check'])</span>
                </article>
            @endforeach</div><p class="programme-note">@include('site.partials.icon', ['name' => 'clock']){{ __('portal.programme_note') }}</p>
        </div></section>
    @endif
    @if($resources->isNotEmpty())<section class="section event-documents" id="event-documents"><div class="container"><div class="section-heading"><p class="eyebrow"><span></span>{{ __('portal.event_resources') }}</p><h2>{{ __('portal.event_resources_text') }}</h2></div><div class="resource-grid">@foreach($resources as $resource)@include('site.partials.resource-card', ['resource' => $resource])@endforeach</div></div></section>@endif
    <div class="container back-events"><a class="text-link" href="{{ route('events.index', $locale) }}">← {{ __('portal.back_events') }}</a></div>
@endsection
