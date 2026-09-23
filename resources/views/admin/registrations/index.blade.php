@extends('admin.layouts.app')

@section('title', 'Registrations')

@section('content')
    @php
        $filters = array_merge([
            'search' => '',
            'event' => '',
            'member_state' => '',
            'verification_status' => '',
            'mail_status' => '',
            'period' => '',
            'date_from' => '',
            'date_to' => '',
            'per_page' => 25,
        ], $filters ?? []);

        $normaliseOptions = static function (iterable $options): array {
            $normalised = [];

            foreach ($options as $key => $option) {
                if (is_array($option) || is_object($option)) {
                    $value = data_get($option, 'value', data_get($option, 'id', data_get($option, 'slug', '')));
                    $label = data_get($option, 'label', data_get($option, 'name', data_get($option, 'title', $value)));
                } else {
                    $value = is_int($key) ? $option : $key;
                    $label = $option;
                }

                if (is_array($label)) {
                    $label = $label['en'] ?? collect($label)->first() ?? $value;
                }

                if (is_scalar($value) && is_scalar($label) && trim((string) $value) !== '') {
                    $normalised[] = ['value' => (string) $value, 'label' => (string) $label];
                }
            }

            return $normalised;
        };

        $metricValue = static function (mixed $metrics, array $keys, mixed $default = 0): mixed {
            foreach ($keys as $key) {
                $value = data_get($metrics, $key);

                if ($value !== null) {
                    return $value;
                }
            }

            return $default;
        };

        $statusClass = static fn (?string $status): string => match (strtolower((string) $status)) {
            'verified', 'sent', 'delivered', 'complete', 'completed' => 'badge--published',
            'failed', 'error' => 'badge--danger',
            'queued', 'sending', 'processing' => 'badge--info',
            default => 'badge--draft',
        };

        $total = (int) $metricValue($metrics ?? [], ['total', 'total_registrations'], $registrations->total());
        $verified = (int) $metricValue($metrics ?? [], ['verified', 'verified_registrations'], 0);
        $pending = (int) $metricValue($metrics ?? [], ['pending', 'pending_verification'], max(0, $total - $verified));
        $today = (int) $metricValue($metrics ?? [], ['today', 'registered_today', 'today_registrations'], 0);
        $verificationRate = (float) $metricValue(
            $metrics ?? [],
            ['verification_rate'],
            $total > 0 ? round(($verified / $total) * 100, 1) : 0,
        );

        $eventOptions = $normaliseOptions(data_get($filterOptions ?? [], 'events', []));
        $memberStateOptions = $normaliseOptions(data_get($filterOptions ?? [], 'member_states', []));
        $verificationOptions = $normaliseOptions(data_get($filterOptions ?? [], 'verification_statuses', []));
        $mailOptions = $normaliseOptions(data_get($filterOptions ?? [], 'mail_statuses', []));
        $perPageOptions = $normaliseOptions(data_get($filterOptions ?? [], 'per_page_options', [25, 50, 100]));

        $exportQuery = array_filter([
            'search' => $filters['search'],
            'event' => $filters['event'],
            'member_state' => $filters['member_state'],
            'verification_status' => $filters['verification_status'],
            'mail_status' => $filters['mail_status'],
            'period' => $filters['period'],
            'date_from' => $filters['date_from'],
            'date_to' => $filters['date_to'],
        ], static fn (mixed $value): bool => $value !== null && $value !== '');
    @endphp

    <div class="page-heading page-heading--registrations">
        <div>
            <p class="eyebrow">Delegate administration</p>
            <h1>Registrations</h1>
            <p>Search, filter, review, and export delegate records securely from one workspace.</p>
        </div>
        <div class="page-heading-actions export-actions" aria-label="Export filtered registrations">
            @foreach(['csv' => 'CSV', 'excel' => 'Excel', 'pdf' => 'PDF'] as $format => $label)
                <a class="button {{ $format === 'pdf' ? 'button--primary' : 'button--secondary' }}" href="{{ route('admin.registrations.export', array_merge($exportQuery, ['format' => $format])) }}">
                    @include('admin.partials.icon', ['name' => 'download'])
                    <span>{{ $label }}</span>
                </a>
            @endforeach
        </div>
    </div>

    <section class="registration-metric-grid" aria-label="Registration summary">
        <article class="registration-metric-card">
            <span class="registration-metric-icon">@include('admin.partials.icon', ['name' => 'registrations'])</span>
            <div><p>Matching records</p><strong>{{ number_format($total) }}</strong><small>Within the current filters</small></div>
        </article>
        <article class="registration-metric-card">
            <span class="registration-metric-icon registration-metric-icon--gold">@include('admin.partials.icon', ['name' => 'calendar'])</span>
            <div><p>Registered today</p><strong>{{ number_format($today) }}</strong><small>Based on the reporting day</small></div>
        </article>
        <article class="registration-metric-card">
            <span class="registration-metric-icon registration-metric-icon--success">@include('admin.partials.icon', ['name' => 'verified'])</span>
            <div><p>Verified emails</p><strong>{{ number_format($verified) }}</strong><small>{{ number_format($verificationRate, 1) }}% verification rate</small></div>
        </article>
        <article class="registration-metric-card">
            <span class="registration-metric-icon registration-metric-icon--warning">@include('admin.partials.icon', ['name' => 'clock'])</span>
            <div><p>Awaiting verification</p><strong>{{ number_format($pending) }}</strong><small>Follow-up may be required</small></div>
        </article>
    </section>

    <section class="panel registration-filter-panel" aria-labelledby="registration-filters-heading">
        <div class="panel-heading registration-filter-heading">
            <div>
                <p class="eyebrow">Refine the records</p>
                <h2 id="registration-filters-heading">Search and filters</h2>
            </div>
            <a class="text-link" href="{{ route('admin.registrations.index') }}">Clear all</a>
        </div>

        @if($errors->any())
            <div class="form-error-summary registration-filter-errors" role="alert" tabindex="-1" data-error-summary>
                @include('admin.partials.icon', ['name' => 'alert'])
                <div>
                    <strong>Review the selected filters</strong>
                    <ul>
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        <form class="registration-filter-form" method="GET" action="{{ route('admin.registrations.index') }}" role="search">
            <div class="filter-field filter-field--search">
                <label for="registration-search">Search</label>
                <div class="filter-search-control">
                    @include('admin.partials.icon', ['name' => 'search'])
                    <input id="registration-search" name="search" type="search" value="{{ $filters['search'] }}" placeholder="Name, email, or reference" autocomplete="off">
                </div>
            </div>

            <div class="filter-field">
                <label for="registration-period">Period</label>
                <select id="registration-period" name="period">
                    <option value="">Custom / any time</option>
                    @foreach(['today' => 'Today', '7_days' => 'Last 7 days', '30_days' => 'Last 30 days', 'all' => 'All time'] as $value => $label)
                        <option value="{{ $value }}" @selected((string) $filters['period'] === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="filter-field">
                <label for="registration-date-from">From</label>
                <input id="registration-date-from" name="date_from" type="date" value="{{ $filters['date_from'] }}">
            </div>

            <div class="filter-field">
                <label for="registration-date-to">To</label>
                <input id="registration-date-to" name="date_to" type="date" value="{{ $filters['date_to'] }}">
            </div>

            @if($eventOptions !== [])
                <div class="filter-field">
                    <label for="registration-event">Event</label>
                    <select id="registration-event" name="event">
                        <option value="">All events</option>
                        @foreach($eventOptions as $option)
                            <option value="{{ $option['value'] }}" @selected((string) $filters['event'] === $option['value'])>{{ $option['label'] }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            <div class="filter-field">
                <label for="registration-member-state">Member State</label>
                <select id="registration-member-state" name="member_state">
                    <option value="">All Member States</option>
                    @foreach($memberStateOptions as $option)
                        <option value="{{ $option['value'] }}" @selected((string) $filters['member_state'] === $option['value'])>{{ $option['label'] }}</option>
                    @endforeach
                </select>
            </div>

            <div class="filter-field">
                <label for="registration-verification">Verification</label>
                <select id="registration-verification" name="verification_status">
                    <option value="">Any status</option>
                    @foreach($verificationOptions as $option)
                        <option value="{{ $option['value'] }}" @selected((string) $filters['verification_status'] === $option['value'])>{{ $option['label'] }}</option>
                    @endforeach
                </select>
            </div>

            <div class="filter-field">
                <label for="registration-mail-status">Verification email</label>
                <select id="registration-mail-status" name="mail_status">
                    <option value="">Any status</option>
                    @foreach($mailOptions as $option)
                        <option value="{{ $option['value'] }}" @selected((string) $filters['mail_status'] === $option['value'])>{{ $option['label'] }}</option>
                    @endforeach
                </select>
            </div>

            <div class="filter-field filter-field--compact">
                <label for="registration-per-page">Rows</label>
                <select id="registration-per-page" name="per_page">
                    @foreach($perPageOptions as $option)
                        <option value="{{ $option['value'] }}" @selected((string) $filters['per_page'] === $option['value'])>{{ $option['label'] }}</option>
                    @endforeach
                </select>
            </div>

            <div class="filter-actions">
                <button class="button button--primary" type="submit">
                    @include('admin.partials.icon', ['name' => 'filter'])
                    <span>Apply filters</span>
                </button>
            </div>
        </form>
    </section>

    <div class="registration-insights-grid">
        <section class="panel registration-chart-panel" aria-labelledby="daily-trend-heading">
            <div class="panel-heading">
                <div>
                    <p class="eyebrow">Day by day</p>
                    <h2 id="daily-trend-heading">Registration trend</h2>
                </div>
            </div>
            @if(collect($dailyTrend ?? [])->isEmpty())
                <div class="chart-empty-state">No registrations match this date range.</div>
            @else
                <div class="daily-bar-chart" role="list" aria-label="Daily registration totals">
                    @foreach($dailyTrend as $day)
                        @php
                            $dayCount = (int) data_get($day, 'count', 0);
                            $dayMaximum = (int) ($maximumDailyCount ?? 0);
                            $barHeight = $dayCount > 0 && $dayMaximum > 0
                                ? max(4, round(($dayCount / $dayMaximum) * 100, 2))
                                : 0;
                        @endphp
                        <div class="daily-bar-column" role="listitem" aria-label="{{ data_get($day, 'label', data_get($day, 'date')) }}: {{ number_format($dayCount) }} registrations">
                            <strong>{{ number_format($dayCount) }}</strong>
                            <span class="daily-bar-track" aria-hidden="true"><span class="daily-bar-fill" style="--bar-height: {{ $barHeight }}%"></span></span>
                            <time datetime="{{ data_get($day, 'date') }}">{{ data_get($day, 'label', data_get($day, 'date')) }}</time>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>

        <section class="panel registration-chart-panel" aria-labelledby="country-breakdown-heading">
            <div class="panel-heading">
                <div>
                    <p class="eyebrow">Geographic reach</p>
                    <h2 id="country-breakdown-heading">By Member State</h2>
                </div>
            </div>
            @if(collect($countryBreakdown ?? [])->isEmpty())
                <div class="chart-empty-state">Country information will appear as registrations arrive.</div>
            @else
                <ol class="country-bar-list">
                    @foreach($countryBreakdown as $country)
                        @php
                            $countryPercentage = min(100, max(0, (float) data_get($country, 'percentage', 0)));
                        @endphp
                        <li>
                            <div><span>{{ data_get($country, 'country', 'Not provided') }}</span><strong>{{ number_format((int) data_get($country, 'count', 0)) }}</strong></div>
                            <span class="country-bar-track" aria-hidden="true"><span style="--bar-width: {{ $countryPercentage }}%"></span></span>
                            <small>{{ number_format($countryPercentage, 1) }}%</small>
                        </li>
                    @endforeach
                </ol>
            @endif
        </section>
    </div>

    <section class="panel content-list-panel registration-list-panel" aria-labelledby="registration-list-heading">
        <div class="panel-heading panel-heading--list">
            <div>
                <h2 id="registration-list-heading">Delegate records</h2>
                <p>
                    Showing {{ number_format($registrations->firstItem() ?? 0) }}–{{ number_format($registrations->lastItem() ?? 0) }}
                    of {{ number_format($registrations->total()) }} registrations
                </p>
            </div>
        </div>

        @if($registrations->isEmpty())
            <div class="empty-state">
                <span class="empty-state-icon">@include('admin.partials.icon', ['name' => 'registrations'])</span>
                <p class="eyebrow">No matching records</p>
                <h3>Try a broader search or date range</h3>
                <p>No registration records matched the filters currently applied.</p>
                <a class="button button--secondary" href="{{ route('admin.registrations.index') }}">Clear filters</a>
            </div>
        @else
            <div class="table-scroll">
                <table class="data-table registration-data-table">
                    <thead>
                        <tr>
                            <th scope="col">Applicant</th>
                            <th scope="col">Member State</th>
                            <th scope="col">Organisation</th>
                            <th scope="col">Verification</th>
                            <th scope="col">Verification email</th>
                            <th scope="col">Submitted</th>
                            <th scope="col"><span class="sr-only">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($registrations as $registration)
                            @php
                                $applicantName = $registration->fullName() ?: 'Unnamed applicant';
                                $initials = Illuminate\Support\Str::upper(
                                    Illuminate\Support\Str::substr((string) $registration->first_name, 0, 1)
                                    .Illuminate\Support\Str::substr((string) $registration->surname, 0, 1),
                                );
                                $verificationStatus = $registration->official_email_verified_at ? 'Verified' : 'Pending';
                                $mailStatus = (string) ($registration->confirmation_email_status ?: 'pending');
                            @endphp
                            <tr>
                                <td data-label="Applicant">
                                    <div class="registration-applicant-cell">
                                        <span class="registration-avatar" aria-hidden="true">{{ $initials ?: '—' }}</span>
                                        <div>
                                            <a class="table-title-link" href="{{ route('admin.registrations.show', $registration) }}">{{ $applicantName }}</a>
                                            <small>{{ $registration->official_email }}</small>
                                            <code>{{ $registration->public_id }}</code>
                                        </div>
                                    </div>
                                </td>
                                <td data-label="Member State">{{ $registration->member_state ?: 'Not provided' }}</td>
                                <td data-label="Organisation">
                                    <span class="table-value-strong">{{ $registration->organisation ?: 'Not provided' }}</span>
                                    <small class="table-secondary-value">{{ $registration->delegation_capacity ?: 'Capacity not provided' }}</small>
                                </td>
                                <td data-label="Verification">
                                    <span class="badge {{ $statusClass($verificationStatus) }}">
                                        <span class="badge-dot" aria-hidden="true"></span>{{ $verificationStatus }}
                                    </span>
                                </td>
                                <td data-label="Verification email">
                                    <span class="badge {{ $statusClass($mailStatus) }}">
                                        <span class="badge-dot" aria-hidden="true"></span>{{ Illuminate\Support\Str::headline($mailStatus) }}
                                    </span>
                                </td>
                                <td data-label="Submitted">
                                    <time datetime="{{ $registration->created_at?->toAtomString() }}">{{ $registration->created_at?->format('d M Y') ?? 'Not available' }}</time>
                                    <small class="table-secondary-value">{{ $registration->created_at?->format('H:i T') }}</small>
                                </td>
                                <td class="table-actions" data-label="Actions">
                                    <a class="icon-button icon-button--outlined" href="{{ route('admin.registrations.show', $registration) }}" aria-label="View registration details" title="View details">
                                        @include('admin.partials.icon', ['name' => 'eye'])
                                    </a>
                                    <a class="icon-button icon-button--outlined" href="{{ route('admin.registrations.pdf', $registration) }}" aria-label="Download registration PDF" title="Download PDF">
                                        @include('admin.partials.icon', ['name' => 'download'])
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($registrations->hasPages())
                <div class="pagination-wrap">
                    {{ $registrations->appends(request()->except('page'))->onEachSide(1)->links() }}
                </div>
            @endif
        @endif
    </section>
@endsection
