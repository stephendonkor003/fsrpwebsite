<section class="section participation-section">
    <div class="container">
        @include('site.partials.africa-map')
        <div class="coverage-participation">
            @foreach([['event', 'events.index', 'calendar'], ['programme', 'programs', 'people'], ['download', 'resources.index', 'download']] as [$step, $route, $icon])
                <a class="participation-step" href="{{ route($route, $locale) }}"><span class="step-icon">@include('site.partials.icon', ['name' => $icon])</span><span><strong>{{ __('portal.step_'.$step) }}</strong><small>{{ __('portal.step_'.$step.'_text') }}</small></span>@include('site.partials.icon', ['name' => 'external'])</a>
            @endforeach
        </div>
    </div>
</section>
