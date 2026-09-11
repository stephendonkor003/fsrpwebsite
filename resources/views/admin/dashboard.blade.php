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
            <a class="button button--primary" href="{{ route('admin.content.create', 'events') }}">
                @include('admin.partials.icon', ['name' => 'plus'])
                <span>Add event</span>
            </a>
        </div>
    </div>

    <section aria-labelledby="overview-heading">
        <div class="section-heading section-heading--sr-only">
            <h2 id="overview-heading">Content overview</h2>
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
