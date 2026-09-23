@extends('admin.layouts.app')

@section('title', 'Dashboard')

@section('content')
    @php
        $localizedValue = static function (mixed $value): string {
            if (is_string($value)) {
                $decoded = json_decode($value, true);
                $value = is_array($decoded) ? $decoded : $value;
            }

            if (is_array($value)) {
                return (string) ($value[app()->getLocale()] ?? $value['en'] ?? collect($value)->first() ?? '');
            }

            return (string) ($value ?? '');
        };

        $registrationFilters = array_merge([
            'event' => '',
            'member_state' => '',
            'period' => '30_days',
            'date_from' => '',
            'date_to' => '',
        ], $registrationFilters ?? []);

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

        $registrationTotal = (int) $metricValue($registrationMetrics ?? [], ['total', 'total_registrations'], 0);
        $registrationToday = (int) $metricValue($registrationMetrics ?? [], ['today', 'registered_today', 'today_registrations'], 0);
        $registrationLastSevenDays = (int) $metricValue($registrationMetrics ?? [], ['last_7_days', 'seven_days', 'recent'], 0);
        $registrationVerified = (int) $metricValue($registrationMetrics ?? [], ['verified', 'verified_registrations'], 0);
        $registrationVerificationRate = (float) $metricValue(
            $registrationMetrics ?? [],
            ['verification_rate'],
            $registrationTotal > 0 ? round(($registrationVerified / $registrationTotal) * 100, 1) : 0,
        );
        $dashboardEventOptions = $normaliseOptions(data_get($registrationFilterOptions ?? [], 'events', []));
        $dashboardMemberStateOptions = $normaliseOptions(data_get($registrationFilterOptions ?? [], 'member_states', []));
    @endphp

    <div class="page-heading page-heading--dashboard">
        <div>
            <p class="eyebrow">Workspace overview</p>
            <h1>Good {{ now()->hour < 12 ? 'morning' : (now()->hour < 18 ? 'afternoon' : 'evening') }}, {{ Illuminate\Support\Str::before(auth()->user()?->name ?? 'Administrator', ' ') }}.</h1>
            <p>Here is what is happening across your events and programme website.</p>
        </div>
        <div class="page-heading-actions">
            <a class="button button--secondary" href="{{ route('home', ['locale' => config('locales.default', 'en')]) }}" target="_blank" rel="noopener">
                <span>View website</span>
                @include('admin.partials.icon', ['name' => 'external'])
            </a>
            <a class="button button--secondary" href="{{ route('admin.registrations.index') }}">
                @include('admin.partials.icon', ['name' => 'registrations'])
                <span>Registrations</span>
            </a>
            <a class="button button--primary" href="{{ route('admin.content.create', 'events') }}">
                @include('admin.partials.icon', ['name' => 'plus'])
                <span>Add event</span>
            </a>
        </div>
    </div>

    <section class="dashboard-registration-workspace" aria-labelledby="dashboard-registrations-heading">
        <div class="dashboard-section-heading">
            <div>
                <p class="eyebrow">Registration intelligence</p>
                <h2 id="dashboard-registrations-heading">Delegate activity</h2>
                <p>Track daily volume, email verification, and Member State participation.</p>
            </div>
            <a class="text-link" href="{{ route('admin.registrations.index') }}">
                <span>Open registration module</span>
                @include('admin.partials.icon', ['name' => 'arrow-right'])
            </a>
        </div>

        <section class="panel registration-filter-panel registration-filter-panel--dashboard" aria-labelledby="dashboard-registration-filters-heading">
            <div class="panel-heading registration-filter-heading">
                <div>
                    <h3 id="dashboard-registration-filters-heading">Dashboard filters</h3>
                    <p>Choose a reporting period and country to update every registration card and graph below.</p>
                </div>
                <a class="text-link" href="{{ route('admin.dashboard') }}">Reset</a>
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
            <form class="registration-filter-form registration-filter-form--dashboard" method="GET" action="{{ route('admin.dashboard') }}">
                <div class="filter-field">
                    <label for="dashboard-registration-period">Period</label>
                    <select id="dashboard-registration-period" name="period">
                        @foreach(['today' => 'Today', '7_days' => 'Last 7 days', '30_days' => 'Last 30 days', 'all' => 'All time'] as $value => $label)
                            <option value="{{ $value }}" @selected((string) $registrationFilters['period'] === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="filter-field">
                    <label for="dashboard-registration-date-from">From</label>
                    <input id="dashboard-registration-date-from" name="date_from" type="date" value="{{ $registrationFilters['date_from'] }}">
                </div>
                <div class="filter-field">
                    <label for="dashboard-registration-date-to">To</label>
                    <input id="dashboard-registration-date-to" name="date_to" type="date" value="{{ $registrationFilters['date_to'] }}">
                </div>
                @if($dashboardEventOptions !== [])
                    <div class="filter-field">
                        <label for="dashboard-registration-event">Event</label>
                        <select id="dashboard-registration-event" name="event">
                            <option value="">All events</option>
                            @foreach($dashboardEventOptions as $option)
                                <option value="{{ $option['value'] }}" @selected((string) $registrationFilters['event'] === $option['value'])>{{ $option['label'] }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div class="filter-field">
                    <label for="dashboard-registration-member-state">Member State</label>
                    <select id="dashboard-registration-member-state" name="member_state">
                        <option value="">All Member States</option>
                        @foreach($dashboardMemberStateOptions as $option)
                            <option value="{{ $option['value'] }}" @selected((string) $registrationFilters['member_state'] === $option['value'])>{{ $option['label'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="filter-actions">
                    <button class="button button--primary" type="submit">
                        @include('admin.partials.icon', ['name' => 'filter'])
                        <span>Update dashboard</span>
                    </button>
                </div>
            </form>
        </section>

        <div class="registration-metric-grid registration-metric-grid--dashboard">
            <article class="registration-metric-card">
                <span class="registration-metric-icon">@include('admin.partials.icon', ['name' => 'registrations'])</span>
                <div><p>Total registrations</p><strong>{{ number_format($registrationTotal) }}</strong><small>In the selected period</small></div>
            </article>
            <article class="registration-metric-card">
                <span class="registration-metric-icon registration-metric-icon--gold">@include('admin.partials.icon', ['name' => 'calendar'])</span>
                <div><p>Today</p><strong>{{ number_format($registrationToday) }}</strong><small>New delegate records</small></div>
            </article>
            <article class="registration-metric-card">
                <span class="registration-metric-icon registration-metric-icon--info">@include('admin.partials.icon', ['name' => 'trend'])</span>
                <div><p>Last 7 days</p><strong>{{ number_format($registrationLastSevenDays) }}</strong><small>Rolling seven-day volume</small></div>
            </article>
            <article class="registration-metric-card">
                <span class="registration-metric-icon registration-metric-icon--success">@include('admin.partials.icon', ['name' => 'verified'])</span>
                <div><p>Verified emails</p><strong>{{ number_format($registrationVerified) }}</strong><small>{{ number_format($registrationVerificationRate, 1) }}% of matching records</small></div>
            </article>
        </div>

        <div class="registration-insights-grid registration-insights-grid--dashboard">
            <section class="panel registration-chart-panel" aria-labelledby="dashboard-registration-trend-heading">
                <div class="panel-heading">
                    <div>
                        <p class="eyebrow">Day by day</p>
                        <h3 id="dashboard-registration-trend-heading">Registration trend</h3>
                    </div>
                </div>
                @if(collect($registrationTrend ?? [])->isEmpty())
                    <div class="chart-empty-state">No registrations match this reporting period.</div>
                @else
                    <div class="daily-bar-chart" role="list" aria-label="Daily registration totals">
                        @foreach($registrationTrend as $day)
                            @php
                                $dayCount = (int) data_get($day, 'count', 0);
                                $dayMaximum = (int) ($maximumRegistrationDayCount ?? 0);
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

            <section class="panel registration-chart-panel" aria-labelledby="dashboard-registration-countries-heading">
                <div class="panel-heading">
                    <div>
                        <p class="eyebrow">Geographic reach</p>
                        <h3 id="dashboard-registration-countries-heading">Leading Member States</h3>
                    </div>
                </div>
                @if(collect($registrationCountries ?? [])->isEmpty())
                    <div class="chart-empty-state">Country information will appear as registrations arrive.</div>
                @else
                    <ol class="country-bar-list">
                        @foreach($registrationCountries as $country)
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

        <section class="panel dashboard-recent-registrations" aria-labelledby="dashboard-recent-registrations-heading">
            <div class="panel-heading">
                <div>
                    <p class="eyebrow">Latest activity</p>
                    <h3 id="dashboard-recent-registrations-heading">Recent registrations</h3>
                </div>
                <a class="text-link" href="{{ route('admin.registrations.index') }}">
                    <span>View all</span>
                    @include('admin.partials.icon', ['name' => 'arrow-right'])
                </a>
            </div>
            @if(collect($recentRegistrations ?? [])->isEmpty())
                <div class="compact-empty-state">
                    <span class="empty-state-icon">@include('admin.partials.icon', ['name' => 'registrations'])</span>
                    <div><h3>No registrations yet</h3><p>The latest delegate submissions will appear here.</p></div>
                </div>
            @else
                <div class="table-scroll">
                    <table class="data-table registration-data-table registration-data-table--compact">
                        <thead><tr><th scope="col">Applicant</th><th scope="col">Member State</th><th scope="col">Verification</th><th scope="col">Submitted</th><th scope="col"><span class="sr-only">Actions</span></th></tr></thead>
                        <tbody>
                            @foreach($recentRegistrations as $registration)
                                @php
                                    $applicantName = $registration->fullName() ?: 'Unnamed applicant';
                                    $isVerified = $registration->official_email_verified_at !== null;
                                @endphp
                                <tr>
                                    <td data-label="Applicant">
                                        <div class="registration-applicant-cell registration-applicant-cell--compact">
                                            <span class="registration-avatar" aria-hidden="true">{{ Illuminate\Support\Str::upper(Illuminate\Support\Str::substr((string) $registration->first_name, 0, 1).Illuminate\Support\Str::substr((string) $registration->surname, 0, 1)) ?: '—' }}</span>
                                            <div><a class="table-title-link" href="{{ route('admin.registrations.show', $registration) }}">{{ $applicantName }}</a><small>{{ $registration->organisation ?: 'Organisation not provided' }}</small></div>
                                        </div>
                                    </td>
                                    <td data-label="Member State">{{ $registration->member_state ?: 'Not provided' }}</td>
                                    <td data-label="Verification"><span class="badge {{ $isVerified ? 'badge--published' : 'badge--draft' }}"><span class="badge-dot" aria-hidden="true"></span>{{ $isVerified ? 'Verified' : 'Pending' }}</span></td>
                                    <td data-label="Submitted"><time datetime="{{ $registration->created_at?->toAtomString() }}">{{ $registration->created_at?->format('d M Y, H:i') ?? 'Not available' }}</time></td>
                                    <td class="table-actions" data-label="Actions"><a class="icon-button icon-button--outlined" href="{{ route('admin.registrations.show', $registration) }}" aria-label="View registration details">@include('admin.partials.icon', ['name' => 'eye'])</a></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    </section>

    <section class="dashboard-content-overview" aria-labelledby="overview-heading">
        <div class="dashboard-section-heading dashboard-section-heading--content">
            <div>
                <p class="eyebrow">Website publishing</p>
                <h2 id="overview-heading">Content overview</h2>
                <p>Keep the public event portal accurate and up to date.</p>
            </div>
        </div>
        <div class="stat-grid">
            @foreach($stats as $stat)
                <article class="stat-card">
                    <span class="stat-card-icon stat-card-icon--{{ data_get($stat, 'icon', 'grid') }}">
                        @include('admin.partials.icon', ['name' => data_get($stat, 'icon', 'grid')])
                    </span>
                    <div>
                        <p>{{ data_get($stat, 'label') }}</p>
                        <strong>{{ number_format((int) data_get($stat, 'value', 0)) }}</strong>
                    </div>
                </article>
            @endforeach
        </div>
    </section>

    <div class="dashboard-grid">
        <section class="panel" aria-labelledby="upcoming-events-heading">
            <div class="panel-heading">
                <div>
                    <p class="eyebrow">Coming up</p>
                    <h2 id="upcoming-events-heading">Upcoming events</h2>
                </div>
                <a class="text-link" href="{{ route('admin.content.index', 'events') }}">
                    <span>View all</span>
                    @include('admin.partials.icon', ['name' => 'arrow-right'])
                </a>
            </div>

            @if($upcomingEvents->isEmpty())
                <div class="compact-empty-state">
                    <span class="empty-state-icon">@include('admin.partials.icon', ['name' => 'calendar'])</span>
                    <div>
                        <h3>No upcoming events</h3>
                        <p>Publish an event to see it here.</p>
                    </div>
                    <a class="button button--secondary button--small" href="{{ route('admin.content.create', 'events') }}">Create event</a>
                </div>
            @else
                <div class="activity-list">
                    @foreach($upcomingEvents as $event)
                        @php($eventDate = $event->start_at ? Illuminate\Support\Carbon::parse($event->start_at) : null)
                        <article class="activity-row">
                            <div class="date-tile" aria-label="{{ $eventDate?->format('F j, Y') ?? 'Date pending' }}">
                                <span>{{ $eventDate?->format('M') ?? 'TBC' }}</span>
                                <strong>{{ $eventDate?->format('d') ?? '—' }}</strong>
                            </div>
                            <div class="activity-row-content">
                                <h3>
                                    <a href="{{ route('admin.content.edit', ['events', $event]) }}">{{ $localizedValue($event->title) ?: 'Untitled event' }}</a>
                                </h3>
                                <p>
                                    {{ $eventDate?->format('H:i') ?? 'Time pending' }}
                                    @if($event->venue)
                                        <span aria-hidden="true">·</span> {{ $localizedValue($event->venue) }}
                                    @endif
                                </p>
                            </div>
                            @if($event->mode)
                                <span class="badge badge--neutral">{{ Illuminate\Support\Str::headline($event->mode) }}</span>
                            @endif
                        </article>
                    @endforeach
                </div>
            @endif
        </section>

        <section class="panel" aria-labelledby="recent-news-heading">
            <div class="panel-heading">
                <div>
                    <p class="eyebrow">Editorial</p>
                    <h2 id="recent-news-heading">Recent news</h2>
                </div>
                <a class="text-link" href="{{ route('admin.content.index', 'news') }}">
                    <span>View all</span>
                    @include('admin.partials.icon', ['name' => 'arrow-right'])
                </a>
            </div>

            @if($recentNews->isEmpty())
                <div class="compact-empty-state">
                    <span class="empty-state-icon">@include('admin.partials.icon', ['name' => 'news'])</span>
                    <div>
                        <h3>No news stories yet</h3>
                        <p>Your latest stories will appear here.</p>
                    </div>
                    <a class="button button--secondary button--small" href="{{ route('admin.content.create', 'news') }}">Write a story</a>
                </div>
            @else
                <div class="activity-list activity-list--news">
                    @foreach($recentNews as $story)
                        @php($publicationDate = $story->published_at ? Illuminate\Support\Carbon::parse($story->published_at) : null)
                        <article class="activity-row">
                            @if($story->image)
                                <img class="activity-thumbnail" src="{{ $story->image }}" alt="" loading="lazy">
                            @else
                                <span class="activity-thumbnail activity-thumbnail--placeholder">@include('admin.partials.icon', ['name' => 'news'])</span>
                            @endif
                            <div class="activity-row-content">
                                <h3>
                                    <a href="{{ route('admin.content.edit', ['news', $story]) }}">{{ $localizedValue($story->title) ?: 'Untitled story' }}</a>
                                </h3>
                                <p>{{ $publicationDate?->format('d M Y') ?? 'Publication not scheduled' }}</p>
                            </div>
                            <span class="badge {{ $story->is_published ? 'badge--published' : 'badge--draft' }}">
                                <span class="badge-dot" aria-hidden="true"></span>
                                {{ $story->is_published ? 'Published' : 'Draft' }}
                            </span>
                        </article>
                    @endforeach
                </div>
            @endif
        </section>
    </div>

    <section class="quick-actions" aria-labelledby="quick-actions-heading">
        <div class="section-heading">
            <div>
                <p class="eyebrow">Shortcuts</p>
                <h2 id="quick-actions-heading">Quick actions</h2>
            </div>
        </div>
        <div class="quick-action-grid">
            <a class="quick-action-card" href="{{ route('admin.content.create', 'sessions') }}">
                <span class="quick-action-icon">@include('admin.partials.icon', ['name' => 'clock'])</span>
                <span><strong>Add a session</strong><small>Build the event agenda</small></span>
                @include('admin.partials.icon', ['name' => 'arrow-right', 'class' => 'quick-action-arrow'])
            </a>
            <a class="quick-action-card" href="{{ route('admin.content.create', 'slides') }}">
                <span class="quick-action-icon">@include('admin.partials.icon', ['name' => 'image'])</span>
                <span><strong>Add a hero slide</strong><small>Refresh the homepage lead</small></span>
                @include('admin.partials.icon', ['name' => 'arrow-right', 'class' => 'quick-action-arrow'])
            </a>
            <a class="quick-action-card" href="{{ route('admin.settings.index') }}">
                <span class="quick-action-icon">@include('admin.partials.icon', ['name' => 'settings'])</span>
                <span><strong>Update site details</strong><small>Brand, contact, and social links</small></span>
                @include('admin.partials.icon', ['name' => 'arrow-right', 'class' => 'quick-action-arrow'])
            </a>
        </div>
    </section>
@endsection
