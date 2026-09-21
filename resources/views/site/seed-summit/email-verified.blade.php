@extends('layouts.site')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/seed-summit-registration.css') }}">
@endpush

@section('content')
    <div class="seed-summit-page seed-confirmation-page" lang="en" dir="ltr">
        <section class="seed-confirmation-hero" aria-labelledby="verification-title">
            <div class="seed-event-hero-pattern" aria-hidden="true"></div>
            <div class="container seed-confirmation-shell">
                <div class="seed-confirmation-mark" aria-hidden="true">@include('site.partials.icon', ['name' => 'check'])</div>
                <p class="seed-event-kicker"><span></span>Email verification</p>
                @if($verificationCompleted && $alreadyVerified)
                    <h1 id="verification-title">Official email already confirmed.</h1>
                    <p>This official email address is already attached to a verified Seed Investment Summit registration.</p>
                @elseif($verificationCompleted)
                    <h1 id="verification-title">Official email confirmed.</h1>
                    <p>Thank you. The official email address for the Seed Investment Summit registration has been verified.</p>
                @elseif($alreadyVerified)
                    <h1 id="verification-title">Official email already confirmed.</h1>
                    <p>This official email address is already attached to a verified Seed Investment Summit registration.</p>
                @else
                    <h1 id="verification-title">Confirm your official email.</h1>
                    <p>Select the button below to confirm that this official email address belongs to the delegate who registered.</p>
                    <form method="POST" action="{{ request()->fullUrl() }}" class="seed-confirmation-actions">
                        @csrf
                        <button class="seed-button seed-button-gold" type="submit">Confirm official email</button>
                    </form>
                @endif
                <div class="seed-confirmation-actions" @if(! $verificationCompleted && ! $alreadyVerified) hidden @endif>
                    <a class="seed-button seed-button-gold" href="{{ route('events.show', ['locale' => $locale, 'slug' => config('seed_summit.event_slug')]) }}">Return to the event</a>
                </div>
            </div>
        </section>
    </div>
@endsection
