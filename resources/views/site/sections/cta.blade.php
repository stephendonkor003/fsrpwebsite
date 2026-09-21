<section class="portal-cta">
    <div class="container portal-cta-inner">
        <div>
            <p class="eyebrow eyebrow-light">{{ __('portal.descriptor') }}</p>
            <h2>{{ __('portal.cta_title') }}</h2>
            <p>{{ __('portal.cta_summary') }}</p>
        </div>
        <div class="cta-actions">
            <a class="button button-gold" href="{{ route('seed-summit.registration.create', $locale) }}">{{ __('ui.actions.register_now') }} @include('site.partials.icon', ['name' => 'arrow'])</a>
            <a class="button button-outline-light" href="{{ route('events.index', $locale) }}">{{ __('portal.all_events') }} @include('site.partials.icon', ['name' => 'arrow'])</a>
        </div>
    </div>
</section>
