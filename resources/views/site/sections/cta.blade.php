<section class="portal-cta">
    <div class="container portal-cta-inner">
        <div>
            <p class="eyebrow eyebrow-light">{{ __('portal.descriptor') }}</p>
            <h2>{{ config('seed_summit.registration_open') === true ? __('portal.cta_title') : __('portal.registration_closed') }}</h2>
            <p>{{ config('seed_summit.registration_open') === true ? __('portal.cta_summary') : __('portal.registration_closed_text') }}</p>
        </div>
        <div class="cta-actions">
            @if(config('seed_summit.registration_open') === true)
                <a class="button button-gold" href="{{ route('seed-summit.registration.create', $locale) }}">{{ __('ui.actions.register_now') }} @include('site.partials.icon', ['name' => 'arrow'])</a>
            @endif
            <a class="button button-outline-light" href="{{ route('events.index', $locale) }}">{{ __('portal.all_events') }} @include('site.partials.icon', ['name' => 'arrow'])</a>
        </div>
    </div>
</section>
