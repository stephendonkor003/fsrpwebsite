@php
    $caadpSpeakers = config('caadp_event_speakers', []);
@endphp

@if($caadpSpeakers !== [])
    <section class="section caadp-speakers-section" id="event-speakers" aria-labelledby="caadp-speakers-heading">
        <div class="container">
            <div class="section-heading split-heading">
                <div>
                    <p class="eyebrow"><span></span>{{ __('portal.partner_event') }} · CAADP</p>
                    <h2 id="caadp-speakers-heading">{{ __('ui.sessions.speakers') }}</h2>
                </div>
                <p>{{ $event->translate('title') }}</p>
            </div>

            <div class="caadp-speakers-grid">
                @foreach($caadpSpeakers as $speaker)
                    <article class="caadp-speaker-card">
                        <div class="caadp-speaker-artwork">
                            <img src="{{ asset($speaker['image']) }}" alt="" loading="lazy" decoding="async" width="1078" height="768">
                        </div>
                        <div class="caadp-speaker-caption">
                            <h3>{{ $speaker['name'] }}</h3>
                            <p>{{ $speaker['title'] }}</p>
                            <span>{{ $speaker['organisation'] }}</span>
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
    </section>
@endif
