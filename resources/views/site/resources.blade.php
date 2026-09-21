@extends('layouts.site')
@section('title', __('portal.downloads'))
@section('content')
    @include('site.partials.page-hero', ['title' => __('portal.downloads'), 'eyebrow' => __('portal.resources_eyebrow'), 'summary' => __('portal.resources_summary'), 'heroImage' => asset('images/seed-investment-summit/seed-investment-summit-2026.jpeg')])
    <section class="resource-filter-band"><div class="container"><form class="resource-filters" method="get" action="{{ route('resources.index', $locale) }}">
        <label class="resource-search"><span>{{ __('ui.actions.search') }}</span><div>@include('site.partials.icon', ['name' => 'search'])<input type="search" name="q" value="{{ $search }}" placeholder="{{ __('portal.resource_search') }}" maxlength="500"></div></label>
        <label><span>{{ __('ui.labels.category') }}</span><select name="category"><option value="">{{ __('portal.all_types') }}</option>@foreach($resourceCategories as $key => $label)<option value="{{ $key }}" @selected($category === $key)>{{ __('portal.category.'.$key) }}</option>@endforeach</select></label>
        <label><span>{{ __('portal.file_language') }}</span><select name="language"><option value="">{{ __('portal.all_languages') }}</option>@foreach($locales as $code => $option)<option value="{{ $code }}" @selected($language === $code)>{{ $option['native_name'] }}</option>@endforeach</select></label>
        <label><span>{{ __('ui.nav.events') }}</span><select name="event"><option value="">{{ __('portal.all_event_options') }}</option>@foreach($resourceEvents as $option)<option value="{{ $option->id }}" @selected((string) $eventId === (string) $option->id)>{{ $option->translate('title') }}</option>@endforeach</select></label>
        <button class="button button-dark" type="submit">{{ __('ui.actions.search') }}</button>
    </form></div></section>
    <section class="section resource-library"><div class="container">
        <div class="library-heading"><h2>{{ __('portal.resources') }} <span>{{ $resources->total() }}</span></h2>@if($search || $category || $language || $eventId)<a class="text-link" href="{{ route('resources.index', $locale) }}">{{ __('ui.filter.clear') }} ×</a>@endif</div>
        <div class="resource-grid">@forelse($resources as $resource)@include('site.partials.resource-card', ['resource' => $resource])@empty<div class="empty-state listing-empty">@include('site.partials.icon', ['name' => 'document'])<h2>{{ __('portal.no_resources') }}</h2><p>{{ __('portal.no_resources_text') }}</p><a class="button button-dark" href="{{ route('resources.index', $locale) }}">{{ __('ui.filter.clear') }}</a></div>@endforelse</div>
        <div class="pagination-wrap">{{ $resources->links() }}</div>
    </div></section>
@endsection
