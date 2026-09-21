@if($sessions->isNotEmpty())
<section class="section portal-agenda">
    <div class="container">
        <div class="section-heading split-heading"><div><p class="eyebrow"><span></span>{{ __('portal.programme') }}</p><h2>{{ __('portal.daily_programme') }}</h2></div><a class="text-link" href="{{ route('programs', $locale) }}">{{ __('portal.full_programme') }} @include('site.partials.icon', ['name' => 'arrow'])</a></div>
        <div class="day-grid">
            @foreach($sessions as $session)
                <article class="day-card">
                    <div class="day-card-content">
                        <div class="day-card-top"><span>{{ $session->is_all_day ? __('portal.day', ['number' => $loop->iteration]) : $session->start_at?->format('H:i') }}</span><time datetime="{{ $session->start_at?->toDateString() }}">{{ $session->start_at?->translatedFormat('d M Y') }}</time></div>
                        <h3>{{ $session->translate('title') }}</h3>
                        <p>{{ $session->translate('summary') }}</p>
                        @if($session->event)<a href="{{ route('events.show', [$locale, $session->event->slug]) }}#programme">{{ __('ui.actions.view_details') }} @include('site.partials.icon', ['name' => 'arrow'])</a>@endif
                    </div>
                </article>
            @endforeach
        </div>
        <p class="programme-note">@include('site.partials.icon', ['name' => 'clock']){{ __('portal.programme_note') }}</p>
    </div>
</section>
@endif
