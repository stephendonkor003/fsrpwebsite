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

    $normaliseOptions = static function (iterable $options): array {
        $normalised = [];

        foreach ($options as $key => $option) {
            if (is_array($option) || is_object($option)) {
                $value = data_get($option, 'value') ?? data_get($option, 'code') ?? $key;
                $label = data_get($option, 'label') ?? data_get($option, 'name') ?? $value;
            } else {
                $value = is_int($key) ? $option : $key;
                $label = $option;
            }

            $normalised[(string) $value] = (string) $label;
        }

        return $normalised;
    };

    $titleOptions = $normaliseOptions($titles ?? ['Mr', 'Mrs', 'Ms', 'Dr', 'Prof', 'Hon', 'Amb', 'Rev']);
    $genderSelectOptions = $normaliseOptions($genderOptions ?? []);
    $countryOptions = $normaliseOptions($countries ?? []);
    $memberStateOptions = $normaliseOptions($memberStates ?? []);
    $capacitySelectOptions = $normaliseOptions($capacityOptions ?? []);
    $dietarySelectOptions = $normaliseOptions($dietaryOptions ?? []);
    $submittedInput = is_array($formInput ?? null) ? $formInput : [];
    $formValue = static fn (string $key, mixed $default = ''): mixed => $submittedInput[$key] ?? $default;

    $eventTitle = (string) $eventValue('title', 'Inaugural Seed Investment Summit');
    $eventTheme = (string) $eventValue('excerpt', 'Resilient Seed Systems for a Food Secure Africa');
    $eventVenue = (string) $eventValue('venue', 'Ezulwini, Eswatini');
    $eventDate = (string) $eventValue('date_display', '5–7 October 2026');
    $eventImage = $eventValue('flyer_url', $eventValue('image'));

    if (is_string($eventImage) && $eventImage !== '' && ! preg_match('#^https?://#i', $eventImage)) {
        $eventImage = asset(ltrim($eventImage, '/'));
    }

    $fieldLabels = [
        'title' => 'Title',
        'first_name' => 'First Name',
        'surname' => 'Surname',
        'gender' => 'Gender',
        'date_of_birth' => 'Date of Birth',
        'nationality' => 'Nationality',
        'national_id_number' => 'National ID Number',
        'passport_number' => 'Passport Number',
        'passport_expiry_date' => 'Passport Expiry Date',
        'issuing_country' => 'Issuing Country',
        'visa_required' => 'Visa Required',
        'passport_photo' => 'Passport Photo',
        'passport_scan' => 'Passport Scan',
        'organisation' => 'Organisation',
        'member_state' => 'Member State',
        'delegation_capacity' => 'Delegation Capacity',
        'years_in_service' => 'Years in Service',
        'areas_of_expertise' => 'Areas of Expertise',
        'mobile_number' => 'Mobile Number',
        'alternative_phone' => 'Alternative Phone',
        'official_email' => 'Official Email',
        'personal_email' => 'Personal Email',
        'emergency_contact' => 'Emergency Contact',
        'arrival_date' => 'Arrival Date',
        'departure_date' => 'Departure Date',
        'dietary_requirements' => 'Dietary Requirements',
        'other_dietary_needs' => 'Other Dietary Needs',
        'dinner_attendance' => 'Dinner Attendance',
        'data_protection_declaration' => 'Data protection declaration',
        'attendance_confirmation' => 'Attendance confirmation',
    ];

    $registrationSteps = [
        ['key' => 'personal', 'number' => '01', 'title' => 'Personal information', 'fields' => ['title', 'first_name', 'surname', 'gender', 'date_of_birth', 'nationality']],
        ['key' => 'identification', 'number' => '02', 'title' => 'Identification', 'fields' => ['national_id_number', 'passport_number', 'passport_expiry_date', 'issuing_country', 'visa_required', 'passport_photo', 'passport_scan']],
        ['key' => 'professional', 'number' => '03', 'title' => 'Professional information', 'fields' => ['organisation', 'member_state', 'delegation_capacity', 'years_in_service', 'areas_of_expertise']],
        ['key' => 'contact', 'number' => '04', 'title' => 'Contact', 'fields' => ['mobile_number', 'alternative_phone', 'official_email', 'personal_email', 'emergency_contact']],
        ['key' => 'logistics', 'number' => '05', 'title' => 'Logistics', 'fields' => ['arrival_date', 'departure_date', 'dietary_requirements', 'other_dietary_needs', 'dinner_attendance']],
        ['key' => 'declaration', 'number' => '06', 'title' => 'Declaration', 'fields' => ['data_protection_declaration', 'attendance_confirmation']],
    ];
    $initialStep = 0;

    foreach ($registrationSteps as $stepIndex => $step) {
        foreach ($step['fields'] as $field) {
            if ($errors->has($field)) {
                $initialStep = $stepIndex;
                break 2;
            }
        }
    }

    $today = now()->toDateString();
    $latestBirthDate = now()->subDay()->toDateString();
@endphp

@section('title', 'Register — '.$eventTitle)
@section('meta_description', 'Delegate registration for the '.$eventTitle.' in '.$eventVenue.'.')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/seed-summit-registration.css') }}">
@endpush

@push('scripts')
    <script src="{{ asset('assets/seed-summit-registration.js') }}" defer></script>
@endpush

@section('content')
    <div class="seed-summit-page seed-summit-registration-page" lang="en" dir="ltr">
        <section class="seed-event-hero" aria-labelledby="seed-event-title">
            @if($eventImage)
                <img
                    class="seed-event-hero-media"
                    src="{{ $eventImage }}"
                    alt=""
                    width="1254"
                    height="1254"
                    fetchpriority="high"
                    decoding="async"
                    aria-hidden="true"
                >
            @else
                <div class="seed-event-flyer-placeholder" aria-hidden="true">
                    <span>Save the date</span>
                    <strong>5–7</strong>
                    <small>October 2026</small>
                    <b>{{ $eventTitle }}</b>
                    <em>Ezulwini, Eswatini</em>
                </div>
            @endif
            <div class="seed-event-hero-pattern" aria-hidden="true"></div>
            <div class="container seed-event-hero-grid">
                <div class="seed-event-copy">
                    <p class="seed-event-kicker"><span></span>Delegate registration</p>
                    <h1 id="seed-event-title">{{ $eventTitle }}</h1>
                    <p class="seed-event-theme">{{ $eventTheme }}</p>
                    <dl class="seed-event-facts">
                        <div>
                            <dt>@include('site.partials.icon', ['name' => 'calendar']) <span>Date</span></dt>
                            <dd>{{ $eventDate }}</dd>
                        </div>
                        <div>
                            <dt>@include('site.partials.icon', ['name' => 'location']) <span>Venue</span></dt>
                            <dd>{{ $eventVenue }}</dd>
                        </div>
                    </dl>
                    <p class="seed-event-host">Hosted by the Kingdom of Eswatini in partnership with the African Union Commission (AUC-ARBE).</p>
                    <span class="sr-only">Event branding includes the African Union, CAADP and the Office of the Commissioner for Agriculture, Rural Development, Blue Economy and Sustainable Environment.</span>
                    <a class="seed-jump-link" href="#delegate-registration">Start registration @include('site.partials.icon', ['name' => 'arrow'])</a>
                </div>
            </div>
        </section>

        <section class="seed-registration-section" id="delegate-registration" aria-labelledby="registration-heading">
            <div class="container seed-registration-layout">
                <aside class="seed-registration-intro">
                    <p class="eyebrow"><span></span>Registration</p>
                    <h2 id="registration-heading">Delegate details</h2>
                    <p>Complete each section using the details shown on your official travel documents. Your registration will be reviewed for event accreditation and logistics.</p>
                    <div class="seed-privacy-note">
                        <span class="seed-privacy-note-icon" aria-hidden="true">✓</span>
                        <div>
                            <strong>Prepare before you begin</strong>
                            <p>Have a passport photo and a clear passport scan ready. Do not upload unrelated documents.</p>
                        </div>
                    </div>
                    <p class="seed-required-note"><span aria-hidden="true">*</span> Required fields</p>
                </aside>

                <div class="seed-registration-card">
                    @if($errors->any())
                        <div class="seed-error-summary" role="alert" tabindex="-1" data-error-summary>
                            <strong>Please review the highlighted fields.</strong>
                            <p>We could not submit the registration because some information needs attention.</p>
                            <ul>
                                @foreach($errors->messages() as $field => $messages)
                                    @foreach($messages as $message)
                                        <li><a href="#field-{{ str_replace(['.', '_'], '-', $field) }}">{{ $fieldLabels[$field] ?? str($field)->replace('_', ' ')->title() }}: {{ $message }}</a></li>
                                    @endforeach
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <nav class="seed-form-progress" aria-label="Registration sections">
                        <ol>
                            @foreach($registrationSteps as $stepIndex => $step)
                                <li>
                                    <a href="#step-{{ $step['key'] }}" data-form-step-link="{{ $stepIndex }}" @if($stepIndex === $initialStep) aria-current="step" @endif>
                                        <span>{{ $step['number'] }}</span>
                                        <small>{{ $step['title'] }}</small>
                                    </a>
                                </li>
                            @endforeach
                        </ol>
                        <div class="seed-progress-track" aria-hidden="true"><span data-form-progress-bar></span></div>
                    </nav>

                    <form
                        class="seed-registration-form"
                        action="{{ $formAction ?? request()->url() }}"
                        method="post"
                        enctype="multipart/form-data"
                        data-registration-form
                        data-initial-step="{{ $initialStep }}"
                    >
                        @csrf
                        <p class="sr-only" aria-live="polite" aria-atomic="true" data-form-announcement></p>

                        <fieldset class="seed-form-step" id="step-personal" data-form-step="0">
                            <legend><span>01</span><strong>Personal information</strong></legend>
                            <p class="seed-step-intro">Tell us who will attend. Enter names exactly as they appear on the delegate’s passport.</p>
                            <div class="seed-fields-grid">
                                <div class="seed-field">
                                    <label for="field-title">Title</label>
                                    <select id="field-title" name="title" autocomplete="honorific-prefix" @error('title') aria-invalid="true" aria-describedby="error-title" @enderror>
                                        <option value="">Select title</option>
                                        @foreach($titleOptions as $value => $label)
                                            <option value="{{ $value }}" @selected((string) $formValue('title') === (string) $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    @error('title')<p class="seed-field-error" id="error-title">{{ $message }}</p>@enderror
                                </div>

                                <div class="seed-field">
                                    <label for="field-first-name">First Name <span class="seed-required" aria-hidden="true">*</span><span class="sr-only"> (required)</span></label>
                                    <input id="field-first-name" name="first_name" type="text" value="{{ $formValue('first_name') }}" maxlength="120" autocomplete="given-name" required @error('first_name') aria-invalid="true" aria-describedby="error-first-name" @enderror>
                                    @error('first_name')<p class="seed-field-error" id="error-first-name">{{ $message }}</p>@enderror
                                </div>

                                <div class="seed-field">
                                    <label for="field-surname">Surname <span class="seed-required" aria-hidden="true">*</span><span class="sr-only"> (required)</span></label>
                                    <input id="field-surname" name="surname" type="text" value="{{ $formValue('surname') }}" maxlength="120" autocomplete="family-name" required @error('surname') aria-invalid="true" aria-describedby="error-surname" @enderror>
                                    @error('surname')<p class="seed-field-error" id="error-surname">{{ $message }}</p>@enderror
                                </div>

                                <div class="seed-field">
                                    <label for="field-gender">Gender</label>
                                    <select id="field-gender" name="gender" @error('gender') aria-invalid="true" aria-describedby="error-gender" @enderror>
                                        <option value="">Select gender</option>
                                        @foreach($genderSelectOptions as $value => $label)
                                            <option value="{{ $value }}" @selected((string) $formValue('gender') === (string) $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    @error('gender')<p class="seed-field-error" id="error-gender">{{ $message }}</p>@enderror
                                </div>

                                <div class="seed-field">
                                    <label for="field-date-of-birth">Date of Birth</label>
                                    <input id="field-date-of-birth" name="date_of_birth" type="date" value="{{ $formValue('date_of_birth') }}" max="{{ $latestBirthDate }}" autocomplete="bday" @error('date_of_birth') aria-invalid="true" aria-describedby="error-date-of-birth" @enderror>
                                    @error('date_of_birth')<p class="seed-field-error" id="error-date-of-birth">{{ $message }}</p>@enderror
                                </div>

                                <div class="seed-field">
                                    <label for="field-nationality">Nationality</label>
                                    <select id="field-nationality" name="nationality" autocomplete="country-name" @error('nationality') aria-invalid="true" aria-describedby="error-nationality" @enderror>
                                        <option value="">Select nationality</option>
                                        @foreach($countryOptions as $value => $label)
                                            <option value="{{ $value }}" @selected((string) $formValue('nationality') === (string) $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    @error('nationality')<p class="seed-field-error" id="error-nationality">{{ $message }}</p>@enderror
                                </div>
                            </div>
                            <div class="seed-step-actions"><button class="seed-button seed-button-primary" type="button" data-step-next>Continue to identification <span aria-hidden="true">→</span></button></div>
                        </fieldset>

                        <fieldset class="seed-form-step" id="step-identification" data-form-step="1">
                            <legend><span>02</span><strong>Identification</strong></legend>
                            <p class="seed-step-intro">Provide valid identification and travel-document details for accreditation.</p>
                            <div class="seed-fields-grid">
                                <div class="seed-field">
                                    <label for="field-national-id-number">National ID Number</label>
                                    <input id="field-national-id-number" name="national_id_number" type="text" value="{{ $formValue('national_id_number') }}" maxlength="120" autocomplete="off" @error('national_id_number') aria-invalid="true" aria-describedby="error-national-id-number" @enderror>
                                    @error('national_id_number')<p class="seed-field-error" id="error-national-id-number">{{ $message }}</p>@enderror
                                </div>

                                <div class="seed-field">
                                    <label for="field-passport-number">Passport Number <span class="seed-required" aria-hidden="true">*</span><span class="sr-only"> (required)</span></label>
                                    <input id="field-passport-number" name="passport_number" type="text" value="{{ $formValue('passport_number') }}" maxlength="120" autocomplete="off" required @error('passport_number') aria-invalid="true" aria-describedby="error-passport-number" @enderror>
                                    @error('passport_number')<p class="seed-field-error" id="error-passport-number">{{ $message }}</p>@enderror
                                </div>

                                <div class="seed-field">
                                    <label for="field-passport-expiry-date">Passport Expiry Date</label>
                                    <input id="field-passport-expiry-date" name="passport_expiry_date" type="date" value="{{ $formValue('passport_expiry_date') }}" min="{{ $today }}" @error('passport_expiry_date') aria-invalid="true" aria-describedby="error-passport-expiry-date" @enderror>
                                    @error('passport_expiry_date')<p class="seed-field-error" id="error-passport-expiry-date">{{ $message }}</p>@enderror
                                </div>

                                <div class="seed-field">
                                    <label for="field-issuing-country">Issuing Country</label>
                                    <select id="field-issuing-country" name="issuing_country" @error('issuing_country') aria-invalid="true" aria-describedby="error-issuing-country" @enderror>
                                        <option value="">Select issuing country</option>
                                        @foreach($countryOptions as $value => $label)
                                            <option value="{{ $value }}" @selected((string) $formValue('issuing_country') === (string) $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    @error('issuing_country')<p class="seed-field-error" id="error-issuing-country">{{ $message }}</p>@enderror
                                </div>

                                <div class="seed-field seed-field-wide">
                                    <fieldset class="seed-choice-group" id="field-visa-required" @error('visa_required') aria-describedby="error-visa-required" @enderror>
                                        <legend>Visa Required</legend>
                                        <div class="seed-choice-row">
                                            <label><input name="visa_required" type="radio" value="1" @checked((string) $formValue('visa_required') === '1')><span>Yes</span></label>
                                            <label><input name="visa_required" type="radio" value="0" @checked((string) $formValue('visa_required') === '0')><span>No</span></label>
                                        </div>
                                    </fieldset>
                                    @error('visa_required')<p class="seed-field-error" id="error-visa-required">{{ $message }}</p>@enderror
                                </div>

                                <div class="seed-field seed-upload-field">
                                    <label for="field-passport-photo">Passport Photo</label>
                                    <input id="field-passport-photo" name="passport_photo" type="file" accept="image/jpeg,image/png,image/webp" aria-describedby="help-passport-photo @error('passport_photo') error-passport-photo @enderror" @error('passport_photo') aria-invalid="true" @enderror>
                                    <p class="seed-field-help" id="help-passport-photo">Upload a clear JPEG, PNG or WebP image, up to 5 MB.</p>
                                    @error('passport_photo')<p class="seed-field-error" id="error-passport-photo">{{ $message }}</p>@enderror
                                </div>

                                <div class="seed-field seed-upload-field">
                                    <label for="field-passport-scan">Passport Scan</label>
                                    <input id="field-passport-scan" name="passport_scan" type="file" accept="image/jpeg,image/png,image/webp,application/pdf" aria-describedby="help-passport-scan @error('passport_scan') error-passport-scan @enderror" @error('passport_scan') aria-invalid="true" @enderror>
                                    <p class="seed-field-help" id="help-passport-scan">Upload a clear JPEG, PNG, WebP or PDF of the passport identification page, up to 10 MB.</p>
                                    @error('passport_scan')<p class="seed-field-error" id="error-passport-scan">{{ $message }}</p>@enderror
                                </div>
                            </div>
                            <div class="seed-step-actions"><button class="seed-button seed-button-secondary" type="button" data-step-previous><span aria-hidden="true">←</span> Back</button><button class="seed-button seed-button-primary" type="button" data-step-next>Continue to professional information <span aria-hidden="true">→</span></button></div>
                        </fieldset>

                        <fieldset class="seed-form-step" id="step-professional" data-form-step="2">
                            <legend><span>03</span><strong>Professional information</strong></legend>
                            <p class="seed-step-intro">Tell us about the delegate’s organisation and role.</p>
                            <div class="seed-fields-grid">
                                <div class="seed-field seed-field-wide">
                                    <label for="field-organisation">Organisation <span class="seed-required" aria-hidden="true">*</span><span class="sr-only"> (required)</span></label>
                                    <input id="field-organisation" name="organisation" type="text" value="{{ $formValue('organisation') }}" maxlength="255" autocomplete="organization" required @error('organisation') aria-invalid="true" aria-describedby="error-organisation" @enderror>
                                    @error('organisation')<p class="seed-field-error" id="error-organisation">{{ $message }}</p>@enderror
                                </div>

                                <div class="seed-field">
                                    <label for="field-member-state">Member State</label>
                                    <select id="field-member-state" name="member_state" @error('member_state') aria-invalid="true" aria-describedby="error-member-state" @enderror>
                                        <option value="">Select member state</option>
                                        @foreach($memberStateOptions as $value => $label)
                                            <option value="{{ $value }}" @selected((string) $formValue('member_state') === (string) $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    @error('member_state')<p class="seed-field-error" id="error-member-state">{{ $message }}</p>@enderror
                                </div>

                                <div class="seed-field">
                                    <label for="field-delegation-capacity">Delegation Capacity</label>
                                    <select id="field-delegation-capacity" name="delegation_capacity" @error('delegation_capacity') aria-invalid="true" aria-describedby="error-delegation-capacity" @enderror>
                                        <option value="">Select capacity</option>
                                        @foreach($capacitySelectOptions as $value => $label)
                                            <option value="{{ $value }}" @selected((string) $formValue('delegation_capacity') === (string) $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    @error('delegation_capacity')<p class="seed-field-error" id="error-delegation-capacity">{{ $message }}</p>@enderror
                                </div>

                                <div class="seed-field">
                                    <label for="field-years-in-service">Years in Service</label>
                                    <input id="field-years-in-service" name="years_in_service" type="number" value="{{ $formValue('years_in_service') }}" min="0" max="80" step="1" inputmode="numeric" @error('years_in_service') aria-invalid="true" aria-describedby="error-years-in-service" @enderror>
                                    @error('years_in_service')<p class="seed-field-error" id="error-years-in-service">{{ $message }}</p>@enderror
                                </div>

                                <div class="seed-field seed-field-wide">
                                    <label for="field-areas-of-expertise">Areas of Expertise</label>
                                    <textarea id="field-areas-of-expertise" name="areas_of_expertise" rows="4" maxlength="2000" @error('areas_of_expertise') aria-invalid="true" aria-describedby="error-areas-of-expertise" @enderror>{{ $formValue('areas_of_expertise') }}</textarea>
                                    @error('areas_of_expertise')<p class="seed-field-error" id="error-areas-of-expertise">{{ $message }}</p>@enderror
                                </div>
                            </div>
                            <div class="seed-step-actions"><button class="seed-button seed-button-secondary" type="button" data-step-previous><span aria-hidden="true">←</span> Back</button><button class="seed-button seed-button-primary" type="button" data-step-next>Continue to contact <span aria-hidden="true">→</span></button></div>
                        </fieldset>

                        <fieldset class="seed-form-step" id="step-contact" data-form-step="3">
                            <legend><span>04</span><strong>Contact</strong></legend>
                            <p class="seed-step-intro">Use reachable contact details. The acknowledgement will be sent to the official email address.</p>
                            <div class="seed-fields-grid">
                                <div class="seed-field">
                                    <label for="field-mobile-number">Mobile Number <span class="seed-required" aria-hidden="true">*</span><span class="sr-only"> (required)</span></label>
                                    <input id="field-mobile-number" name="mobile_number" type="tel" value="{{ $formValue('mobile_number') }}" maxlength="40" autocomplete="tel" inputmode="tel" required aria-describedby="help-mobile-number @error('mobile_number') error-mobile-number @enderror" @error('mobile_number') aria-invalid="true" @enderror>
                                    <p class="seed-field-help" id="help-mobile-number">Include the country calling code.</p>
                                    @error('mobile_number')<p class="seed-field-error" id="error-mobile-number">{{ $message }}</p>@enderror
                                </div>

                                <div class="seed-field">
                                    <label for="field-alternative-phone">Alternative Phone</label>
                                    <input id="field-alternative-phone" name="alternative_phone" type="tel" value="{{ $formValue('alternative_phone') }}" maxlength="40" inputmode="tel" @error('alternative_phone') aria-invalid="true" aria-describedby="error-alternative-phone" @enderror>
                                    @error('alternative_phone')<p class="seed-field-error" id="error-alternative-phone">{{ $message }}</p>@enderror
                                </div>

                                <div class="seed-field">
                                    <label for="field-official-email">Official Email <span class="seed-required" aria-hidden="true">*</span><span class="sr-only"> (required)</span></label>
                                    <input id="field-official-email" name="official_email" type="email" value="{{ $formValue('official_email') }}" maxlength="254" autocomplete="email" inputmode="email" required @error('official_email') aria-invalid="true" aria-describedby="error-official-email" @enderror>
                                    @error('official_email')<p class="seed-field-error" id="error-official-email">{{ $message }}</p>@enderror
                                </div>

                                <div class="seed-field">
                                    <label for="field-personal-email">Personal Email</label>
                                    <input id="field-personal-email" name="personal_email" type="email" value="{{ $formValue('personal_email') }}" maxlength="254" inputmode="email" @error('personal_email') aria-invalid="true" aria-describedby="error-personal-email" @enderror>
                                    @error('personal_email')<p class="seed-field-error" id="error-personal-email">{{ $message }}</p>@enderror
                                </div>

                                <div class="seed-field seed-field-wide">
                                    <label for="field-emergency-contact">Emergency Contact</label>
                                    <input id="field-emergency-contact" name="emergency_contact" type="text" value="{{ $formValue('emergency_contact') }}" maxlength="500" aria-describedby="help-emergency-contact @error('emergency_contact') error-emergency-contact @enderror" @error('emergency_contact') aria-invalid="true" @enderror>
                                    <p class="seed-field-help" id="help-emergency-contact">Provide the contact’s name, relationship and reachable telephone number.</p>
                                    @error('emergency_contact')<p class="seed-field-error" id="error-emergency-contact">{{ $message }}</p>@enderror
                                </div>
                            </div>
                            <div class="seed-step-actions"><button class="seed-button seed-button-secondary" type="button" data-step-previous><span aria-hidden="true">←</span> Back</button><button class="seed-button seed-button-primary" type="button" data-step-next>Continue to logistics <span aria-hidden="true">→</span></button></div>
                        </fieldset>

                        <fieldset class="seed-form-step" id="step-logistics" data-form-step="4">
                            <legend><span>05</span><strong>Logistics</strong></legend>
                            <p class="seed-step-intro">Share travel and hospitality information to support event planning.</p>
                            <div class="seed-fields-grid">
                                <div class="seed-field">
                                    <label for="field-arrival-date">Arrival Date</label>
                                    <input id="field-arrival-date" name="arrival_date" type="date" value="{{ $formValue('arrival_date') }}" data-arrival-date @error('arrival_date') aria-invalid="true" aria-describedby="error-arrival-date" @enderror>
                                    @error('arrival_date')<p class="seed-field-error" id="error-arrival-date">{{ $message }}</p>@enderror
                                </div>

                                <div class="seed-field">
                                    <label for="field-departure-date">Departure Date</label>
                                    <input id="field-departure-date" name="departure_date" type="date" value="{{ $formValue('departure_date') }}" data-departure-date @error('departure_date') aria-invalid="true" aria-describedby="error-departure-date" @enderror>
                                    @error('departure_date')<p class="seed-field-error" id="error-departure-date">{{ $message }}</p>@enderror
                                </div>

                                <div class="seed-field">
                                    <label for="field-dietary-requirements">Dietary Requirements</label>
                                    <select id="field-dietary-requirements" name="dietary_requirements" data-dietary-requirements @error('dietary_requirements') aria-invalid="true" aria-describedby="error-dietary-requirements" @enderror>
                                        <option value="">Select dietary requirement</option>
                                        @foreach($dietarySelectOptions as $value => $label)
                                            <option value="{{ $value }}" @selected((string) $formValue('dietary_requirements') === (string) $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    @error('dietary_requirements')<p class="seed-field-error" id="error-dietary-requirements">{{ $message }}</p>@enderror
                                </div>

                                <div class="seed-field">
                                    <label for="field-other-dietary-needs">Other Dietary Needs <span class="seed-required" data-dietary-required hidden aria-hidden="true">*</span></label>
                                    <input id="field-other-dietary-needs" name="other_dietary_needs" type="text" value="{{ $formValue('other_dietary_needs') }}" maxlength="1000" data-other-dietary-needs aria-describedby="help-other-dietary-needs @error('other_dietary_needs') error-other-dietary-needs @enderror" @error('other_dietary_needs') aria-invalid="true" @enderror>
                                    <p class="seed-field-help" id="help-other-dietary-needs">Required when “Other” is selected.</p>
                                    @error('other_dietary_needs')<p class="seed-field-error" id="error-other-dietary-needs">{{ $message }}</p>@enderror
                                </div>

                                <div class="seed-field seed-field-wide">
                                    <fieldset class="seed-choice-group" id="field-dinner-attendance" @error('dinner_attendance') aria-describedby="error-dinner-attendance" @enderror>
                                        <legend>Dinner Attendance</legend>
                                        <div class="seed-choice-row">
                                            <label><input name="dinner_attendance" type="radio" value="1" @checked((string) $formValue('dinner_attendance') === '1')><span>Yes</span></label>
                                            <label><input name="dinner_attendance" type="radio" value="0" @checked((string) $formValue('dinner_attendance') === '0')><span>No</span></label>
                                        </div>
                                    </fieldset>
                                    @error('dinner_attendance')<p class="seed-field-error" id="error-dinner-attendance">{{ $message }}</p>@enderror
                                </div>
                            </div>
                            <div class="seed-step-actions"><button class="seed-button seed-button-secondary" type="button" data-step-previous><span aria-hidden="true">←</span> Back</button><button class="seed-button seed-button-primary" type="button" data-step-next>Continue to declaration <span aria-hidden="true">→</span></button></div>
                        </fieldset>

                        <fieldset class="seed-form-step" id="step-declaration" data-form-step="5">
                            <legend><span>06</span><strong>Declaration</strong></legend>
                            <p class="seed-step-intro">Review the details entered in every section before submitting this registration.</p>
                            <div class="seed-submit-note" id="data-protection-notice">
                                <strong>Data protection notice</strong>
                                <p>{{ $dataProtectionNotice }}</p>
                            </div>
                            <div class="seed-declaration-list">
                                <label class="seed-consent" for="field-data-protection-declaration">
                                    <input id="field-data-protection-declaration" name="data_protection_declaration" type="checkbox" value="1" required @checked((string) $formValue('data_protection_declaration') === '1') aria-describedby="data-protection-notice @error('data_protection_declaration') error-data-protection-declaration @enderror" @error('data_protection_declaration') aria-invalid="true" @enderror>
                                    <span class="seed-consent-control" aria-hidden="true"></span>
                                    <span>I have read and agree to the data protection declaration <b class="seed-required" aria-hidden="true">*</b><span class="sr-only"> (required)</span></span>
                                </label>
                                @error('data_protection_declaration')<p class="seed-field-error" id="error-data-protection-declaration">{{ $message }}</p>@enderror

                                <label class="seed-consent" for="field-attendance-confirmation">
                                    <input id="field-attendance-confirmation" name="attendance_confirmation" type="checkbox" value="1" required @checked((string) $formValue('attendance_confirmation') === '1') @error('attendance_confirmation') aria-invalid="true" aria-describedby="error-attendance-confirmation" @enderror>
                                    <span class="seed-consent-control" aria-hidden="true"></span>
                                    <span>By registering, the delegate confirms attendance <b class="seed-required" aria-hidden="true">*</b><span class="sr-only"> (required)</span></span>
                                </label>
                                @error('attendance_confirmation')<p class="seed-field-error" id="error-attendance-confirmation">{{ $message }}</p>@enderror
                            </div>

                            <div class="seed-submit-note">
                                <strong>What happens next?</strong>
                                <p>After submission, you will see a registration summary with a PDF download. An acknowledgement will also be sent to the official email address.</p>
                            </div>

                            <div class="seed-step-actions"><button class="seed-button seed-button-secondary" type="button" data-step-previous><span aria-hidden="true">←</span> Back</button><button class="seed-button seed-button-submit" type="submit">Submit registration @include('site.partials.icon', ['name' => 'check'])</button></div>
                        </fieldset>
                    </form>
                </div>
            </div>
        </section>
    </div>
@endsection
