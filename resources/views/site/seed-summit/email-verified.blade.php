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
                @if($verificationCompleted && ! $verificationSucceeded)
                    <h1 id="verification-title">Official email already in use.</h1>
                    <p>This email address is already attached to a verified registration for this event. No registration PDF was sent for this request.</p>
                @elseif($verificationCompleted && $alreadyVerified)
                    <h1 id="verification-title">Official email already confirmed.</h1>
                    @if($receiptEmailStatus === \App\Models\EventRegistration::EMAIL_SENT)
                        <p>Your registration acknowledgement and complete PDF have already been sent to the verified email address.</p>
                    @elseif($receiptEmailStatus === \App\Models\EventRegistration::EMAIL_FAILED)
                        <p>Your email is verified, but delivery of the registration PDF is delayed. Please select the confirmation link again to retry.</p>
                    @else
                        <p>Your email is verified and the registration acknowledgement with your complete registration PDF is being prepared.</p>
                    @endif
                @elseif($verificationCompleted)
                    <h1 id="verification-title">Official email confirmed.</h1>
                    @if($receiptEmailStatus === \App\Models\EventRegistration::EMAIL_SENT)
                        <p>Thank you. Your registration acknowledgement and complete PDF have been sent to the verified email address.</p>
                    @elseif($receiptEmailStatus === \App\Models\EventRegistration::EMAIL_FAILED)
                        <p>Your email is verified, but delivery of the registration PDF is delayed. Please select the confirmation link again to retry.</p>
                    @else
                        <p>Thank you. A registration acknowledgement with your complete registration PDF is now being prepared for the verified address.</p>
                    @endif
                @elseif($alreadyVerified)
                    <h1 id="verification-title">Official email already confirmed.</h1>
                    @if($receiptEmailStatus === \App\Models\EventRegistration::EMAIL_FAILED)
                        <p>Your email is verified, but delivery of the registration PDF was delayed. Select the button below to retry it.</p>
                    @else
                        <p>This official email address is already attached to the verified Seed Investment Summit registration.</p>
                    @endif
                @else
                    <h1 id="verification-title">Confirm your official email.</h1>
                    <p>Select the button below to confirm that this official email address belongs to the delegate who registered.</p>
                    <form method="POST" action="{{ request()->fullUrl() }}" class="seed-confirmation-actions">
                        @csrf
                        <button class="seed-button seed-button-gold" type="submit">Confirm official email</button>
                    </form>
                @endif
                @if($canRetryReceiptEmail ?? false)
                    <form method="POST" action="{{ request()->fullUrl() }}" class="seed-confirmation-actions">
                        @csrf
                        <button class="seed-button seed-button-gold" type="submit">Retry PDF email</button>
                    </form>
                @endif
                <div class="seed-confirmation-actions" @if(! $verificationCompleted && ! $alreadyVerified) hidden @endif>
                    <a class="seed-button seed-button-gold" href="{{ route('events.show', ['locale' => $locale, 'slug' => config('seed_summit.event_slug')]) }}">Return to the event</a>
                </div>
            </div>
        </section>
    </div>
@endsection
