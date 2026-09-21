@extends('layouts.site')
@section('title', __('portal.footer_platform'))
@section('content')
    @include('site.partials.page-hero', ['title' => $page?->translate('title') ?: __('portal.footer_platform'), 'eyebrow' => __('portal.descriptor'), 'summary' => __('portal.footer_about'), 'heroImage' => asset('images/seed-investment-summit/seed-investment-summit-2026.jpeg')])
    @include('site.sections.about')
    <section class="section about-story"><div class="container about-events-layout"><div><p class="eyebrow"><span></span>{{ $page?->translate('eyebrow') ?: __('portal.region') }}</p><h2>{{ __('portal.themes_title') }}</h2><div class="prose prose-lead">{!! nl2br(e($page?->translate('body') ?: __('portal.footer_about'))) !!}</div><a class="button button-dark" href="{{ route('events.index', $locale) }}">{{ __('portal.all_events') }} @include('site.partials.icon', ['name' => 'arrow'])</a></div><img src="{{ asset('images/seed-investment-summit/seed-investment-summit-2026.jpeg') }}" alt="{{ __('portal.current_event_artwork') }}" loading="lazy"></div></section>
    @include('site.sections.cta')
@endsection
