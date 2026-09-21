@php
    $speakerPreview = array_slice($speakers ?? config('seed_summit_speakers', []), 0, 4);
@endphp

@if($speakerPreview !== [])
    <section class="section summit-speakers-teaser" aria-labelledby="homepage-speakers-heading">
        <div class="container">
            <div class="section-heading split-heading">
                <div>
                    <p class="eyebrow"><span></span>{{ __('portal.speakers_eyebrow') }}</p>
                    <h2 id="homepage-speakers-heading">{{ __('portal.speakers_heading') }}</h2>
                </div>
                <a class="text-link" href="{{ route('speakers', $locale) }}" aria-label="{{ __('ui.actions.view_all') }}: {{ __('ui.sessions.speakers') }}">
                    {{ __('ui.actions.view_all') }} @include('site.partials.icon', ['name' => 'arrow'])
                </a>
            </div>
            <p class="section-intro">{{ __('portal.speakers_summary') }}</p>

            <div class="summit-speaker-teaser-grid">
                @foreach($speakerPreview as $speaker)
                    <a class="summit-speaker-teaser-card" href="{{ route('speakers', $locale) }}#speaker-profile-{{ $speaker['source_order'] }}">
                        <span class="summit-speaker-artwork">
                            <img src="{{ asset($speaker['image']) }}" alt="" loading="lazy" decoding="async" width="1200" height="628">
                        </span>
                        <span class="summit-speaker-caption">
                            <strong>{{ $speaker['name'] }}</strong>
                            <span>{{ $speaker['title'] }}</span>
                            <small>{{ $speaker['organisation'] }}</small>
                        </span>
                    </a>
                @endforeach
            </div>
        </div>
    </section>
@endif
