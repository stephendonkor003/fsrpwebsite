@extends('admin.layouts.app')

@section('title', 'Registration details')

@section('content')
    @php
        $statusClass = static fn (?string $status): string => match (strtolower((string) $status)) {
            'verified', 'sent', 'delivered', 'complete', 'completed' => 'badge--published',
            'failed', 'error' => 'badge--danger',
            'queued', 'sending', 'processing' => 'badge--info',
            default => 'badge--draft',
        };

        $displayValue = static function (mixed $value): string {
            if ($value instanceof \Carbon\CarbonInterface) {
                return $value->format('d M Y, H:i T');
            }

            if (is_bool($value)) {
                return $value ? 'Yes' : 'No';
            }

            if (is_array($value)) {
                $value = implode(', ', array_filter(array_map(
                    static fn (mixed $item): string => is_scalar($item) ? (string) $item : '',
                    $value,
                )));
            }

            return $value === null || trim((string) $value) === '' ? 'Not provided' : (string) $value;
        };

        $applicantName = $registration->fullName() ?: 'Unnamed applicant';
        $initials = Illuminate\Support\Str::upper(
            Illuminate\Support\Str::substr((string) $registration->first_name, 0, 1)
            .Illuminate\Support\Str::substr((string) $registration->surname, 0, 1),
        );
        $isVerified = $registration->official_email_verified_at !== null;
        $confirmationStatus = (string) ($registration->confirmation_email_status ?: 'pending');
        $receiptStatus = (string) ($registration->receipt_email_status ?: 'pending');
        $documents = collect($documentLinks ?? [])->filter(
            static fn (mixed $document): bool => is_array($document) && filled(data_get($document, 'url')),
        );
        $profilePhoto = $documents->first(
            static fn (array $document): bool => (bool) data_get($document, 'previewable')
                && str_contains(Illuminate\Support\Str::lower((string) data_get($document, 'label')), 'photo'),
        );
    @endphp

    <a class="back-link" href="{{ route('admin.registrations.index') }}">
        @include('admin.partials.icon', ['name' => 'arrow-left'])
        <span>Back to registrations</span>
    </a>

    <div class="page-heading page-heading--record">
        <div>
            <p class="eyebrow">Delegate record</p>
            <h1>{{ $applicantName }}</h1>
            <p>Review the complete submitted registration and its communication status.</p>
        </div>
        <div class="page-heading-actions">
            <a class="button button--secondary" href="{{ route('admin.registrations.index') }}">
                @include('admin.partials.icon', ['name' => 'registrations'])
                <span>All registrations</span>
            </a>
            <a class="button button--primary" href="{{ route('admin.registrations.pdf', $registration) }}" download>
                @include('admin.partials.icon', ['name' => 'download'])
                <span>Download PDF</span>
            </a>
        </div>
    </div>

    <aside class="registration-privacy-notice" aria-label="Privacy notice">
        <span>@include('admin.partials.icon', ['name' => 'shield'])</span>
        <div>
            <strong>Restricted personal record</strong>
            <p>This page contains identity, contact, and travel information. Download or share it only when operationally authorised.</p>
        </div>
    </aside>

    <section class="panel registration-record-hero" aria-labelledby="record-overview-heading">
        <div class="registration-profile-visual">
            @if($profilePhoto)
                <img src="{{ data_get($profilePhoto, 'url') }}" alt="Delegate profile photo" loading="lazy" referrerpolicy="no-referrer">
            @else
                <span aria-hidden="true">{{ $initials ?: '—' }}</span>
            @endif
        </div>
        <div class="registration-record-identity">
            <p class="eyebrow">Applicant</p>
            <h2 id="record-overview-heading">{{ $applicantName }}</h2>
            <p>{{ $registration->organisation ?: 'Organisation not provided' }}</p>
            <div class="registration-record-tags">
                <span>@include('admin.partials.icon', ['name' => 'country']) {{ $registration->member_state ?: 'Member State not provided' }}</span>
                <span>@include('admin.partials.icon', ['name' => 'people']) {{ $registration->delegation_capacity ?: 'Capacity not provided' }}</span>
            </div>
        </div>
        <dl class="registration-reference-card">
            <div><dt>Reference</dt><dd>{{ $registration->public_id }}</dd></div>
            <div><dt>Event</dt><dd>{{ $registration->event_title }}</dd></div>
            <div><dt>Submitted</dt><dd>{{ $registration->created_at?->format('d M Y, H:i T') ?? 'Not available' }}</dd></div>
        </dl>
    </section>

    <section class="record-status-grid" aria-label="Registration status">
        <article class="record-status-card">
            <span class="record-status-icon">@include('admin.partials.icon', ['name' => 'verified'])</span>
            <div>
                <p>Official email</p>
                <span class="badge {{ $isVerified ? 'badge--published' : 'badge--draft' }}">
                    <span class="badge-dot" aria-hidden="true"></span>{{ $isVerified ? 'Verified' : 'Pending verification' }}
                </span>
                <small>{{ $registration->official_email_verified_at?->format('d M Y, H:i T') ?? 'Not yet verified' }}</small>
            </div>
        </article>
        <article class="record-status-card">
            <span class="record-status-icon">@include('admin.partials.icon', ['name' => 'mail'])</span>
            <div>
                <p>Verification email</p>
                <span class="badge {{ $statusClass($confirmationStatus) }}">
                    <span class="badge-dot" aria-hidden="true"></span>{{ Illuminate\Support\Str::headline($confirmationStatus) }}
                </span>
                <small>{{ $registration->confirmation_email_sent_at?->format('d M Y, H:i T') ?? 'No sent timestamp' }}</small>
            </div>
        </article>
        <article class="record-status-card">
            <span class="record-status-icon">@include('admin.partials.icon', ['name' => 'document'])</span>
            <div>
                <p>Receipt PDF email</p>
                <span class="badge {{ $statusClass($receiptStatus) }}">
                    <span class="badge-dot" aria-hidden="true"></span>{{ Illuminate\Support\Str::headline($receiptStatus) }}
                </span>
                <small>{{ $registration->receipt_email_sent_at?->format('d M Y, H:i T') ?? 'No sent timestamp' }}</small>
            </div>
        </article>
    </section>

    <div class="registration-record-layout">
        <div class="registration-summary-grid" aria-label="Complete registration information">
            @foreach($summarySections as $section)
                <section class="panel registration-summary-card" aria-labelledby="record-section-{{ $loop->index }}">
                    <header>
                        <span aria-hidden="true">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                        <h2 id="record-section-{{ $loop->index }}">{{ data_get($section, 'title', 'Registration details') }}</h2>
                    </header>
                    <dl>
                        @foreach(data_get($section, 'items', []) as $item)
                            <div>
                                <dt>{{ data_get($item, 'label', 'Details') }}</dt>
                                <dd>{{ $displayValue(data_get($item, 'value')) }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </section>
            @endforeach
        </div>

        <aside class="registration-record-sidebar" aria-label="Registration files and actions">
            <section class="panel registration-document-panel">
                <div class="panel-heading">
                    <div>
                        <p class="eyebrow">Private files</p>
                        <h2>Submitted documents</h2>
                    </div>
                </div>
                @if($documents->isEmpty())
                    <div class="compact-document-empty">
                        @include('admin.partials.icon', ['name' => 'document'])
                        <p>No downloadable documents are attached to this record.</p>
                    </div>
                @else
                    <div class="registration-document-list">
                        @foreach($documents as $document)
                            <a href="{{ data_get($document, 'url') }}"
                                @if(data_get($document, 'previewable')) target="_blank" rel="noopener noreferrer" @else download="{{ data_get($document, 'filename') }}" @endif>
                                <span>@include('admin.partials.icon', ['name' => data_get($document, 'previewable') ? 'eye' : 'download'])</span>
                                <span>
                                    <strong>{{ data_get($document, 'label', 'Registration document') }}</strong>
                                    <small>{{ data_get($document, 'filename', data_get($document, 'previewable') ? 'Open secure preview' : 'Download private file') }}</small>
                                </span>
                                @include('admin.partials.icon', ['name' => 'arrow-right'])
                            </a>
                        @endforeach
                    </div>
                @endif
            </section>

            <section class="panel registration-download-panel">
                <span class="registration-download-mark">@include('admin.partials.icon', ['name' => 'document'])</span>
                <p class="eyebrow">Official copy</p>
                <h2>Registration PDF</h2>
                <p>Download the branded complete record with its header, delegate information, page footer, and privacy notice.</p>
                <a class="button button--primary button--full" href="{{ route('admin.registrations.pdf', $registration) }}" download>
                    @include('admin.partials.icon', ['name' => 'download'])
                    <span>Download PDF</span>
                </a>
            </section>
        </aside>
    </div>
@endsection
