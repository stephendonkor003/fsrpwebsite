@extends('admin.layouts.app')

@section('title', 'Homepage layout')

@section('content')
    @php
        $sectionDescriptions = [
            'hero' => 'Homepage introduction and rotating hero slides.',
            'slides' => 'Homepage introduction and rotating hero slides.',
            'programs' => 'Strategic themes and programme tracks.',
            'programme' => 'Strategic themes and programme tracks.',
            'events' => 'Featured and upcoming events.',
            'sessions' => 'Upcoming agenda sessions and speakers.',
            'news' => 'Latest announcements and stories.',
            'faqs' => 'A selection of frequently asked questions.',
            'faq' => 'A selection of frequently asked questions.',
            'cta' => 'Closing call-to-action and contact prompt.',
        ];
    @endphp

    <div class="page-heading">
        <div>
            <p class="eyebrow">Homepage</p>
            <h1>Section layout</h1>
            <p>Choose which homepage sections are visible and control the order in which visitors see them.</p>
        </div>
        <a class="button button--secondary" href="{{ route('home', ['locale' => config('locales.default', 'en')]) }}" target="_blank" rel="noopener">
            <span>Preview homepage</span>
            @include('admin.partials.icon', ['name' => 'external'])
        </a>
    </div>

    @if($errors->any())
        <div class="form-error-summary" role="alert" tabindex="-1" data-error-summary>
            @include('admin.partials.icon', ['name' => 'alert'])
            <div>
                <strong>The homepage layout could not be saved.</strong>
                <p>Please review the section order and try again.</p>
            </div>
        </div>
    @endif

    <form class="homepage-layout-form" method="POST" action="{{ route('admin.home-sections.update') }}">
        @csrf
        @method('PUT')

        <section class="panel section-organizer" aria-labelledby="section-organizer-heading">
            <div class="panel-heading panel-heading--list">
                <div>
                    <p class="eyebrow">Page structure</p>
                    <h2 id="section-organizer-heading">Homepage sections</h2>
                    <p>Use the order fields to arrange sections. Hidden sections keep their content and can be restored at any time.</p>
                </div>
                <span class="badge badge--neutral">{{ $sections->count() }} {{ Illuminate\Support\Str::plural('section', $sections->count()) }}</span>
            </div>

            @if($sections->isEmpty())
                <div class="empty-state">
                    <span class="empty-state-icon">@include('admin.partials.icon', ['name' => 'home'])</span>
                    <p class="eyebrow">No sections configured</p>
                    <h3>The homepage layout is empty</h3>
                    <p>Run the application seeder to install the default homepage sections.</p>
                </div>
            @else
                <div class="section-order-list" data-sortable-sections>
                    @foreach($sections as $index => $section)
                        @php
                            $sectionLabel = $section->label ?: Illuminate\Support\Str::headline($section->key);
                            $sectionDescription = $sectionDescriptions[$section->key] ?? 'A managed content section on the public homepage.';
                            $isActive = (bool) old("sections.$index.is_active", $section->is_active);
                        @endphp
                        <article class="section-order-row {{ $isActive ? 'is-active' : 'is-hidden' }}" data-section-row>
                            <input type="hidden" name="sections[{{ $index }}][id]" value="{{ $section->getKey() }}">

                            <button class="drag-handle" type="button" data-drag-handle aria-label="Move {{ $sectionLabel }} section" title="Drag to reorder">
                                @include('admin.partials.icon', ['name' => 'drag'])
                            </button>

                            <span class="section-order-number" aria-hidden="true">{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</span>

                            <div class="section-order-copy">
                                <div class="section-order-title">
                                    <h3>{{ $sectionLabel }}</h3>
                                    <span class="badge {{ $isActive ? 'badge--published' : 'badge--draft' }}" data-section-status>
                                        <span class="badge-dot" aria-hidden="true"></span>
                                        <span data-section-status-label>{{ $isActive ? 'Visible' : 'Hidden' }}</span>
                                    </span>
                                </div>
                                <p>{{ $sectionDescription }}</p>
                                <small>Section key: {{ $section->key }}</small>
                            </div>

                            <div class="section-order-control">
                                <label for="section-order-{{ $section->getKey() }}">Order</label>
                                <input
                                    id="section-order-{{ $section->getKey() }}"
                                    name="sections[{{ $index }}][sort_order]"
                                    type="number"
                                    value="{{ old("sections.$index.sort_order", $section->sort_order) }}"
                                    min="0"
                                    max="100"
                                    inputmode="numeric"
                                    required
                                    data-section-order
                                    aria-invalid="{{ $errors->has("sections.$index.sort_order") ? 'true' : 'false' }}"
                                    @error("sections.$index.sort_order") aria-describedby="section-order-{{ $section->getKey() }}-error" @enderror
                                >
                                @error("sections.$index.sort_order")
                                    <p class="field-error" id="section-order-{{ $section->getKey() }}-error">{{ $message }}</p>
                                @enderror
                            </div>

                            <label class="toggle-field toggle-field--inline" for="section-active-{{ $section->getKey() }}">
                                <span class="sr-only">Show {{ $sectionLabel }} on the homepage</span>
                                <input type="hidden" name="sections[{{ $index }}][is_active]" value="0">
                                <span class="toggle-control">
                                    <input
                                        id="section-active-{{ $section->getKey() }}"
                                        name="sections[{{ $index }}][is_active]"
                                        type="checkbox"
                                        value="1"
                                        @checked($isActive)
                                        data-section-toggle
                                    >
                                    <span aria-hidden="true"></span>
                                </span>
                            </label>
                        </article>
                    @endforeach
                </div>
            @endif
        </section>

        @if($sections->isNotEmpty())
            <div class="editor-actions">
                <span class="save-hint">The updated order takes effect as soon as you save.</span>
                <div class="editor-action-buttons">
                    <a class="button button--secondary" href="{{ route('admin.dashboard') }}">Cancel</a>
                    <button class="button button--primary" type="submit">
                        @include('admin.partials.icon', ['name' => 'check'])
                        <span>Save homepage layout</span>
                    </button>
                </div>
            </div>
        @endif
    </form>
@endsection
