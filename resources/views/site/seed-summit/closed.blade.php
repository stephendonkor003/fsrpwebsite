@extends('layouts.site')

@section('title', __('portal.registration_closed'))
@section('meta_description', __('portal.registration_closed_text'))

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/seed-summit-registration.css') }}">
@endpush

@section('content')
    <div class="seed-summit-page seed-confirmation-page">
        <section class="seed-confirmation-hero" aria-labelledby="registration-closed-title">
            <div class="seed-event-hero-pattern" aria-hidden="true"></div>
            <div class="container seed-confirmation-shell">
                <div class="seed-confirmation-mark" aria-hidden="true">@include('site.partials.icon', ['name' => 'check'])</div>
                <p class="seed-event-kicker"><span></span>{{ $event->translate('title') }}</p>
                <h1 id="registration-closed-title">{{ __('portal.registration_closed') }}</h1>
                <p>{{ __('portal.registration_closed_text') }}</p>
                <div class="seed-confirmation-actions">
                    <a class="seed-button seed-button-gold" href="{{ route('events.show', [$locale, $event->slug]) }}">{{ __('portal.event_details') }} @include('site.partials.icon', ['name' => 'arrow'])</a>
                    <a class="seed-button seed-button-secondary" href="{{ route('resources.index', $locale) }}">{{ __('portal.downloads') }}</a>
                </div>
            </div>
        </section>
    </div>
@endsection
