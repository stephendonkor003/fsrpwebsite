@extends('layouts.site')
@section('title', __('ui.sessions.speakers'))
@section('content')
    @include('site.partials.page-hero', [
        'title' => __('ui.sessions.speakers'),
        'eyebrow' => __('portal.speakers_eyebrow'),
        'summary' => __('portal.speakers_summary'),
        'heroImage' => asset('images/speakers/moses-vilakati.png'),
    ])

    <section class="section speakers-section" aria-labelledby="speakers-heading">
        <div class="container">
            <div class="section-heading centered-heading">
                <p class="eyebrow"><span></span>{{ __('portal.speakers_eyebrow') }}</p>
                <h2 id="speakers-heading">{{ __('portal.speakers_heading') }}</h2>
                <p>{{ __('portal.speakers_intro') }}</p>
            </div>

            <div class="speakers-grid">
                @foreach($speakers as $speaker)
                    <button class="speaker-card" type="button" data-speaker-open="speaker-{{ $loop->index }}" aria-haspopup="dialog">
                        <span class="speaker-card-image">
                            <img src="{{ asset($speaker['image']) }}" alt="{{ $speaker['name'] }}" loading="lazy" width="1078" height="768">
                            <span class="speaker-card-action">{{ __('portal.view_speaker') }} <span aria-hidden="true">↗</span></span>
                        </span>
                        <span class="speaker-card-copy">
                            <strong>{{ $speaker['name'] }}</strong>
                            <span>{{ $speaker['title'] }}</span>
                            <small>{{ $speaker['organisation'] }}</small>
                        </span>
                    </button>

                    <dialog class="speaker-modal" id="speaker-{{ $loop->index }}" aria-labelledby="speaker-name-{{ $loop->index }}">
                        <div class="speaker-modal-panel">
                            <button class="speaker-modal-close" type="button" data-speaker-close aria-label="{{ __('portal.close_speaker') }}">×</button>
                            <img src="{{ asset($speaker['image']) }}" alt="" width="1078" height="768">
                            <div class="speaker-modal-copy">
                                <p class="eyebrow"><span></span>{{ $speaker['organisation'] }}</p>
                                <h2 id="speaker-name-{{ $loop->index }}">{{ $speaker['name'] }}</h2>
                                <p>{{ $speaker['title'] }}</p>
                            </div>
                        </div>
                    </dialog>
                @endforeach
            </div>
        </div>
    </section>
@endsection
