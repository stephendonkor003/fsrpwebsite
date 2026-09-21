@extends('layouts.site')

@php
    $resolvedLocale = $locale ?? 'en';
    $eventValue = static function (string $key, mixed $fallback = null) use ($event, $resolvedLocale): mixed {
        $value = null;

        if (is_object($event) && method_exists($event, 'translate')) {
            $value = $event->translate($key);
        }

        if ($value === null || $value === '') {
            $value = data_get($event, $key);
        }

        if (is_array($value)) {
            $value = $value[$resolvedLocale] ?? $value['en'] ?? $value['value'] ?? null;
        }

        return $value === null || $value === '' ? $fallback : $value;
    };
    $registrationValue = static fn (string $key, mixed $fallback = null): mixed => data_get($registration, $key, $fallback);
    $displayValue = static function (mixed $value): string {
        if ($value instanceof DateTimeInterface) {
            return $value->format('d F Y');
        }

        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }

        if (is_array($value)) {
            $value = implode(', ', array_filter(array_map(static fn (mixed $item): string => is_scalar($item) ? (string) $item : '', $value)));
        }

        return $value === null || trim((string) $value) === '' ? 'Not provided' : (string) $value;
    };

    $eventTitle = (string) $eventValue('title', 'Inaugural Seed Investment Summit');
    $eventTheme = (string) $eventValue('excerpt', 'Resilient Seed Systems for a Food Secure Africa');
    $eventVenue = (string) $eventValue('venue', 'Ezulwini, Eswatini');
    $eventDate = (string) $eventValue('date_display', '5–7 October 2026');
    $delegateName = trim(implode(' ', array_filter([
        $registrationValue('title'),
        $registrationValue('first_name'),
        $registrationValue('surname'),
    ])));
    $delegateName = $delegateName !== '' ? $delegateName : 'Delegate';
    $registrationReference = $registrationValue('public_id', $registrationValue('reference_code', $registrationValue('registration_number', $registrationValue('uuid', $registrationValue('id')))));

    $emailState = is_array($emailStatus ?? null) || is_object($emailStatus ?? null)
        ? data_get($emailStatus, 'status', data_get($emailStatus, 'state', 'pending'))
        : ($emailStatus ?? 'pending');
    $emailState = $emailState === true ? 'sent' : ($emailState === false ? 'pending' : strtolower((string) $emailState));
    $emailMessage = is_array($emailStatus ?? null) || is_object($emailStatus ?? null)
        ? data_get($emailStatus, 'message')
        : null;

    $normalisedSections = [];

    foreach ($summarySections as $sectionKey => $section) {
        $isStructuredSection = is_array($section) || is_object($section);
        $sectionTitle = $isStructuredSection ? data_get($section, 'title') : null;
        $sectionItems = $isStructuredSection ? data_get($section, 'items') : null;

        if ($sectionItems === null) {
            $sectionTitle = $sectionTitle ?? (is_string($sectionKey) ? $sectionKey : 'Registration details');
            $sectionItems = $section;
        }

        if (! is_iterable($sectionItems)) {
            $sectionItems = ['Details' => $sectionItems];
        }

        $normalisedItems = [];

        foreach ($sectionItems as $itemKey => $item) {
            $hasStructuredItem = (is_array($item) || is_object($item)) && data_get($item, 'label') !== null;
            $label = $hasStructuredItem ? data_get($item, 'label') : (is_string($itemKey) ? $itemKey : 'Details');
            $value = $hasStructuredItem ? data_get($item, 'value') : $item;
            $normalisedItems[] = ['label' => (string) $label, 'value' => $value];
        }

        $normalisedSections[] = [
            'title' => (string) ($sectionTitle ?: 'Registration details'),
            'items' => $normalisedItems,
        ];
    }
@endphp

@section('title', 'Registration received — '.$eventTitle)
@section('meta_description', 'Your delegate registration for the '.$eventTitle.' has been received.')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/seed-summit-registration.css') }}">
@endpush

@push('scripts')
    <script src="{{ asset('assets/seed-summit-registration.js') }}" defer></script>
@endpush

@section('content')
    <div class="seed-summit-page seed-confirmation-page" lang="en" dir="ltr">
        <section class="seed-confirmation-hero" aria-labelledby="confirmation-title">
            <div class="seed-event-hero-pattern" aria-hidden="true"></div>
            <div class="container seed-confirmation-shell">
                <div class="seed-confirmation-mark" aria-hidden="true">@include('site.partials.icon', ['name' => 'check'])</div>
                <p class="seed-event-kicker"><span></span>Registration received</p>
                <h1 id="confirmation-title">Thank you, {{ $delegateName }}.</h1>
                <p>Your delegate registration for the {{ $eventTitle }} has been saved. Review the full summary now and keep the PDF for your records.</p>

                @if($registrationReference)
                    <p class="seed-reference"><span>Registration reference</span><strong>{{ $registrationReference }}</strong></p>
                @endif

                <div @class([
                    'seed-email-status',
                    'is-success' => in_array($emailState, ['sent', 'delivered'], true),
                    'is-pending' => in_array($emailState, ['pending', 'queued', 'processing'], true),
                    'is-warning' => in_array($emailState, ['failed', 'error'], true),
                ]) role="status">
                    <span aria-hidden="true">{{ in_array($emailState, ['sent', 'delivered'], true) ? '✓' : 'i' }}</span>
                    <p>
                        @if($emailMessage)
                            {{ $emailMessage }}
                        @elseif(in_array($emailState, ['sent', 'delivered'], true))
                            A confirmation email has been sent to the official email address.
                        @elseif($emailState === 'queued')
                            Your confirmation email is queued for delivery to the official email address.
                        @elseif(in_array($emailState, ['failed', 'error'], true))
                            Your registration is saved, but email delivery is still pending. Please download the PDF below.
                        @else
                            Your acknowledgement is being prepared for the official email address.
                        @endif
                    </p>
                </div>

                <div class="seed-confirmation-actions">
                    <button class="seed-button seed-button-primary" type="button" data-confirmation-open>View full registration</button>
                    @if($pdfUrl)
                        <a class="seed-button seed-button-gold" href="{{ $pdfUrl }}" download>Download PDF @include('site.partials.icon', ['name' => 'download'])</a>
                    @endif
                </div>

                <dl class="seed-confirmation-event-facts">
                    <div><dt>Date</dt><dd>{{ $eventDate }}</dd></div>
                    <div><dt>Venue</dt><dd>{{ $eventVenue }}</dd></div>
                    <div><dt>Theme</dt><dd>{{ $eventTheme }}</dd></div>
                </dl>

                <a class="seed-confirmation-home" href="{{ route('home', $resolvedLocale) }}">Return to the FSRP website <span aria-hidden="true">→</span></a>
            </div>
        </section>

        <noscript>
            <style>[data-confirmation-open], [data-confirmation-close] { display: none !important; }</style>
        </noscript>
        <dialog class="seed-registration-dialog" open data-registration-dialog aria-labelledby="registration-summary-title" aria-describedby="registration-summary-description">
            <div class="seed-registration-dialog-panel">
                <header class="seed-registration-dialog-header">
                    <div>
                        <p>Registration acknowledgement</p>
                        <h2 id="registration-summary-title">{{ $delegateName }}</h2>
                        <span id="registration-summary-description">Review the information recorded for this delegate.</span>
                    </div>
                    <form method="dialog">
                        <button type="submit" data-confirmation-close aria-label="Close registration summary">&times;</button>
                    </form>
                </header>

                <div class="seed-registration-dialog-body">
                    @foreach($normalisedSections as $section)
                        <section class="seed-summary-section" aria-labelledby="summary-section-{{ $loop->index }}">
                            <h3 id="summary-section-{{ $loop->index }}"><span>{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>{{ $section['title'] }}</h3>
                            <dl>
                                @foreach($section['items'] as $item)
                                    <div>
                                        <dt>{{ $item['label'] }}</dt>
                                        <dd>{{ $displayValue($item['value']) }}</dd>
                                    </div>
                                @endforeach
                            </dl>
                        </section>
                    @endforeach
                </div>

                <footer class="seed-registration-dialog-footer">
                    <p>Keep your registration PDF private because it contains personal information.</p>
                    <div>
                        <button class="seed-button seed-button-secondary" type="button" data-print-registration>Print summary</button>
                        @if($pdfUrl)
                            <a class="seed-button seed-button-gold" href="{{ $pdfUrl }}" download>Download PDF @include('site.partials.icon', ['name' => 'download'])</a>
                        @endif
                    </div>
                </footer>
            </div>
        </dialog>
    </div>
@endsection
