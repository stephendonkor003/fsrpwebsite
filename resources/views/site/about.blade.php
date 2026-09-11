@extends('layouts.site')
@section('title', __('portal.footer_fsrp'))
@section('content')
    @include('site.partials.page-hero', ['title' => $page?->translate('title') ?: __('portal.footer_fsrp'), 'eyebrow' => __('portal.descriptor'), 'summary' => __('portal.footer_about'), 'heroImage' => asset('images/fsrp/water-food-resilience-1.jpg')])
    @include('site.sections.about')
    <section class="section about-story"><div class="container about-fsrp-layout"><div><p class="eyebrow"><span></span>{{ $page?->translate('eyebrow') ?: __('portal.region') }}</p><h2>{{ __('portal.themes_title') }}</h2><div class="prose prose-lead">{!! nl2br(e($page?->translate('body') ?: __('portal.footer_about'))) !!}</div><a class="button button-dark" href="https://fsrp.africa/" target="_blank" rel="noopener">{{ __('portal.main_site') }} @include('site.partials.icon', ['name' => 'external'])</a></div><img src="{{ asset('images/fsrp/water-food-resilience-2.jpg') }}" alt="{{ __('portal.descriptor') }}" loading="lazy"></div></section>
    @include('site.sections.programs')
    @include('site.sections.cta')
@endsection
