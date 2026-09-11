<section class="section portal-themes">
    <div class="container">
        <div class="section-heading centered-heading"><p class="eyebrow"><span></span>{{ __('portal.themes_eyebrow') }}</p><h2>{{ __('portal.themes_title') }}</h2><p>{{ __('portal.themes_summary') }}</p></div>
        <div class="theme-grid">
            @foreach($programs as $program)
                <a class="theme-card" href="{{ route('programs', $locale) }}#track-{{ $program->slug }}"><span class="theme-number">0{{ $loop->iteration }}</span><h3>{{ $program->translate('title') }}</h3><p>{{ $program->translate('excerpt') }}</p><span class="theme-arrow">@include('site.partials.icon', ['name' => 'arrow'])</span></a>
            @endforeach
        </div>
    </div>
</section>
