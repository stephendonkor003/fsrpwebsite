@extends('layouts.site')
@section('title', __('ui.sessions.speakers'))
@section('content')
    @include('site.partials.page-hero', [
        'title' => __('ui.sessions.speakers'),
        'eyebrow' => __('portal.speakers_eyebrow'),
        'summary' => __('portal.speakers_summary'),
        'heroImage' => asset('images/seed-investment-summit/seed-investment-summit-2026.jpeg'),
    ])

    <section class="section speakers-section summit-speakers-section" aria-labelledby="speakers-heading">
        <div class="container">
            <div class="section-heading centered-heading">
                <p class="eyebrow"><span></span>{{ __('portal.speakers_eyebrow') }}</p>
                <h2 id="speakers-heading">{{ __('portal.speakers_heading') }}</h2>
                <p>{{ __('portal.speakers_intro') }}</p>
            </div>

            <div class="summit-speakers-grid">
                @foreach($speakers as $speaker)
                    <article class="summit-speaker" id="speaker-profile-{{ $speaker['source_order'] }}" data-speaker-profile>
                        <button
                            class="summit-speaker-card"
                            type="button"
                            data-speaker-open="speaker-dialog-{{ $speaker['source_order'] }}"
                            aria-controls="speaker-dialog-{{ $speaker['source_order'] }}"
                            aria-haspopup="dialog"
                            aria-label="{{ __('portal.view_speaker') }}: {{ $speaker['name'] }}"
                        >
                            <span class="summit-speaker-artwork">
                                <img src="{{ asset($speaker['image']) }}" alt="" loading="lazy" decoding="async" width="1200" height="628">
                            </span>
                            <span class="summit-speaker-caption">
                                <strong>{{ $speaker['name'] }}</strong>
                                <span>{{ $speaker['title'] }}</span>
                                <small>{{ $speaker['organisation'] }}</small>
                                <span class="summit-speaker-action" aria-hidden="true">{{ __('portal.view_speaker') }} <span>↗</span></span>
                            </span>
                        </button>
                    </article>

                    <dialog
                        class="speaker-modal summit-speaker-dialog"
                        id="speaker-dialog-{{ $speaker['source_order'] }}"
                        aria-labelledby="speaker-name-{{ $speaker['source_order'] }}"
                        aria-describedby="speaker-role-{{ $speaker['source_order'] }}"
                    >
                        <div class="speaker-modal-panel summit-speaker-dialog-panel">
                            <div class="summit-speaker-dialog-toolbar">
                                <button class="speaker-modal-close" type="button" data-speaker-close aria-label="{{ __('portal.close_speaker') }}">×</button>
                            </div>
                            <figure class="summit-speaker-dialog-artwork">
                                <img src="{{ asset($speaker['image']) }}" alt="" loading="lazy" decoding="async" width="1200" height="628">
                            </figure>
                            <div class="speaker-modal-copy summit-speaker-dialog-copy">
                                <p class="eyebrow"><span></span>{{ $speaker['organisation'] }}</p>
                                <h2 id="speaker-name-{{ $speaker['source_order'] }}">{{ $speaker['name'] }}</h2>
                                <p id="speaker-role-{{ $speaker['source_order'] }}">{{ $speaker['title'] }}</p>
                            </div>
                        </div>
                    </dialog>
                @endforeach
            </div>
        </div>
    </section>
@endsection
