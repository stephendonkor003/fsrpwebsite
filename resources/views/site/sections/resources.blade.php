@if($resources->isNotEmpty())
<section class="section portal-resources"><div class="container"><div class="section-heading split-heading"><div><p class="eyebrow"><span></span>{{ __('portal.resources_eyebrow') }}</p><h2>{{ __('portal.resources_title') }}</h2></div><a class="text-link" href="{{ route('resources.index', $locale) }}">{{ __('portal.all_resources') }} @include('site.partials.icon', ['name' => 'arrow'])</a></div><div class="resource-grid">@foreach($resources as $resource)@include('site.partials.resource-card', ['resource' => $resource])@endforeach</div></div></section>
@endif
