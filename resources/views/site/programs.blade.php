@extends('layouts.site')
@section('title', __('portal.programme'))
@section('content')
    @include('site.partials.page-hero', ['title' => __('portal.programme_title'), 'eyebrow' => __('portal.programme'), 'summary' => __('portal.programme_summary'), 'heroImage' => asset('images/caadp/caadp-partnership-1.jpeg')])
    <aside class="programme-update-band" aria-labelledby="programme-update-title">
        <div class="container">
            <div class="programme-update-notice">
                <span class="programme-update-icon" aria-hidden="true">!</span>
                <div>
                    <p class="programme-update-label">{{ __('portal.programme_update_label') }}</p>
                    <h2 id="programme-update-title">{{ __('portal.programme_update_title') }}</h2>
                    <p>{{ __('portal.programme_update_text') }}</p>
                </div>
            </div>
        </div>
    </aside>
    <section class="section programme-events"><div class="container">
        @forelse($programmeEvents as $programmeEvent)
            <div class="programme-event-heading"><div><p class="eyebrow"><span></span>{{ $programmeEvent->start_at?->translatedFormat('d M') }} – {{ $programmeEvent->end_at?->translatedFormat('d M Y') }} · {{ $programmeEvent->translate('venue') }}</p><h2>{{ $programmeEvent->translate('title') }}</h2><p>{{ $programmeEvent->translate('excerpt') }}</p></div>@if($programmeFile = $programmeEvent->resources->firstWhere('category', 'programme'))<a class="button button-dark" href="{{ route('resources.download', [$locale, $programmeFile->id]) }}" download="{{ $programmeFile->original_filename }}">@include('site.partials.icon', ['name' => 'download']){{ __('portal.download_programme') }}</a>@endif</div>
            <div class="programme-day-list">
                @foreach($programmeEvent->sessions as $session)
                    <article class="programme-day">
                        @if($programmeEvent->slug === '22nd-caadp-partnership-platform')<img class="programme-day-image" src="{{ asset('images/caadp/caadp-partnership-'.(($loop->index % 4) + 1).'.jpeg') }}" alt="" loading="lazy">@endif
                        <div class="programme-day-date"><span>{{ $session->is_all_day ? __('portal.day', ['number' => $loop->iteration]) : $session->start_at?->format('H:i') }}</span><strong>{{ $session->start_at?->translatedFormat('d M') }}</strong><small>{{ $session->start_at?->translatedFormat('l') }}</small></div>
                        <div><span class="resource-category">{{ $session->translate('track') ?: __('portal.daily_focus') }}</span><h3>{{ $session->translate('title') }}</h3><p>{{ $session->translate('summary') }}</p>@if($session->translate('speaker_name'))<div class="programme-speaker">@include('site.partials.icon', ['name' => 'people']){{ $session->translate('speaker_name') }} @if($session->translate('speaker_role'))· {{ $session->translate('speaker_role') }}@endif</div>@endif</div>
                        <span class="programme-day-icon">@include('site.partials.icon', ['name' => 'check'])</span>
                    </article>
                @endforeach
            </div>
            <div class="programme-end"><p class="programme-note">@include('site.partials.icon', ['name' => 'clock']){{ __('portal.programme_note') }}</p><a class="text-link" href="{{ route('events.show', [$locale, $programmeEvent->slug]) }}">{{ __('portal.event_details') }} @include('site.partials.icon', ['name' => 'arrow'])</a></div>
        @empty
            <div class="empty-state"><h2>{{ __('portal.no_programme') }}</h2><p>{{ __('portal.no_programme_text') }}</p><a class="button button-dark" href="{{ route('events.index', $locale) }}">{{ __('portal.explore') }}</a></div>
        @endforelse
    </div></section>
    @if($resources->isNotEmpty())@include('site.sections.resources')@endif
    <section class="section programme-themes"><div class="container"><div class="section-heading"><p class="eyebrow"><span></span>{{ __('portal.themes_eyebrow') }}</p><h2>{{ __('portal.themes_title') }}</h2></div>
        @foreach($programs as $program)<article id="track-{{ $program->slug }}" class="programme-track"><span class="theme-number">0{{ $loop->iteration }}</span><div><h3>{{ $program->translate('title') }}</h3><p>{{ $program->translate('excerpt') }}</p><div class="prose">{!! nl2br(e($program->translate('body'))) !!}</div></div></article>@endforeach
    </div></section>
@endsection
