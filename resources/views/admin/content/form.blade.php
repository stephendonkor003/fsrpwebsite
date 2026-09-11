@extends('admin.layouts.app')

@php
    $isEditing = (bool) $item->exists;
@endphp

@section('title', $isEditing ? 'Edit '.$definition['singular'] : 'Add '.$definition['singular'])

@section('content')
    @php
        $translatableFields = collect($definition['fields'])->filter(fn (array $field): bool => (bool) ($field['translatable'] ?? false));
        $standardFields = collect($definition['fields'])->reject(fn (array $field): bool => (bool) ($field['translatable'] ?? false));
        $firstLocale = array_key_first($locales);
    @endphp

    <div class="page-heading page-heading--editor">
        <div>
            <a class="back-link" href="{{ route('admin.content.index', $type) }}">
                @include('admin.partials.icon', ['name' => 'arrow-left'])
                <span>Back to {{ Illuminate\Support\Str::lower($definition['label']) }}</span>
            </a>
            <p class="eyebrow">{{ $isEditing ? 'Edit content' : 'New content' }}</p>
            <h1>{{ $isEditing ? 'Edit '.$definition['singular'] : 'Add '.$definition['singular'] }}</h1>
            <p>{{ $isEditing ? 'Update the content and publication settings below.' : $definition['description'] }}</p>
        </div>
        @if($isEditing)
            @php
                $attributes = $item->getAttributes();
                $statusField = array_key_exists('is_published', $attributes) ? 'is_published' : (array_key_exists('is_active', $attributes) ? 'is_active' : null);
                $isLive = $statusField ? (bool) $item->getAttribute($statusField) : true;
            @endphp
            <span class="badge badge--large {{ $isLive ? 'badge--published' : 'badge--draft' }}">
                <span class="badge-dot" aria-hidden="true"></span>
                {{ $statusField === 'is_active' ? ($isLive ? 'Active' : 'Hidden') : ($isLive ? 'Published' : 'Draft') }}
            </span>
        @endif
    </div>

    @if($errors->any())
        <div class="form-error-summary" role="alert" tabindex="-1" data-error-summary>
            @include('admin.partials.icon', ['name' => 'alert'])
            <div>
                <strong>Please review the highlighted fields.</strong>
                <p>{{ $errors->count() }} {{ Illuminate\Support\Str::plural('issue', $errors->count()) }} prevented this content from being saved.</p>
            </div>
        </div>
    @endif

    <form
        class="content-editor-form"
        method="POST"
        action="{{ $isEditing ? route('admin.content.update', [$type, $item]) : route('admin.content.store', $type) }}"
        enctype="multipart/form-data"
    >
        @csrf
        @if($isEditing)
            @method('PUT')
        @endif

        <div class="editor-grid">
            <div class="editor-main">
                @if($translatableFields->isNotEmpty())
                    <section class="panel editor-panel" aria-labelledby="translations-heading">
                        <div class="panel-heading panel-heading--editor">
                            <div>
                                <p class="eyebrow">Six AU languages</p>
                                <h2 id="translations-heading">Translated content</h2>
                                <p>English is required where marked. Add the other languages now or return to them later.</p>
                            </div>
                        </div>

                        <div class="language-tabs" data-language-tabs>
                            <div class="language-tab-list" role="tablist" aria-label="Content language">
                                @foreach($locales as $localeCode => $localeDefinition)
                                    @php
                                        $localeName = is_array($localeDefinition) ? ($localeDefinition['name'] ?? strtoupper($localeCode)) : (string) $localeDefinition;
                                        $nativeName = is_array($localeDefinition) ? ($localeDefinition['native_name'] ?? $localeName) : $localeName;
                                        $localeHasErrors = $errors->has("translations.$localeCode.*");
                                    @endphp
                                    <button
                                        id="language-tab-{{ $localeCode }}"
                                        class="language-tab {{ $localeCode === $firstLocale ? 'is-active' : '' }} {{ $localeHasErrors ? 'has-error' : '' }}"
                                        type="button"
                                        role="tab"
                                        aria-selected="{{ $localeCode === $firstLocale ? 'true' : 'false' }}"
                                        aria-controls="language-panel-{{ $localeCode }}"
                                        tabindex="{{ $localeCode === $firstLocale ? '0' : '-1' }}"
                                        data-language-tab="{{ $localeCode }}"
                                        data-has-errors="{{ $localeHasErrors ? 'true' : 'false' }}"
                                    >
                                        <span>{{ strtoupper($localeCode) }}</span>
                                        <small>{{ $nativeName }}</small>
                                        @if($localeHasErrors)
                                            <span class="language-error-dot"><span class="sr-only">Contains errors</span></span>
                                        @endif
                                    </button>
                                @endforeach
                            </div>

                            @foreach($locales as $localeCode => $localeDefinition)
                                @php
                                    $localeDirection = is_array($localeDefinition) ? ($localeDefinition['direction'] ?? 'ltr') : 'ltr';
                                    $localeName = is_array($localeDefinition) ? ($localeDefinition['name'] ?? strtoupper($localeCode)) : (string) $localeDefinition;
                                @endphp
                                <div
                                    id="language-panel-{{ $localeCode }}"
                                    class="language-panel"
                                    role="tabpanel"
                                    aria-labelledby="language-tab-{{ $localeCode }}"
                                    data-language-panel="{{ $localeCode }}"
                                    dir="{{ $localeDirection }}"
                                    @if($localeCode !== $firstLocale) hidden @endif
                                >
                                    <div class="language-panel-heading">
                                        <span class="language-code">{{ strtoupper($localeCode) }}</span>
                                        <div>
                                            <h3>{{ $localeName }} content</h3>
                                            <p>{{ $localeCode === 'en' ? 'Primary website language' : 'Translated website content' }}</p>
                                        </div>
                                    </div>

                                    <div class="form-stack">
                                        @foreach($translatableFields as $field)
                                            @php
                                                $storedTranslations = $item->getAttribute($field['name']);
                                                if (is_string($storedTranslations)) {
                                                    $decodedTranslations = json_decode($storedTranslations, true);
                                                    $storedTranslations = is_array($decodedTranslations) ? $decodedTranslations : [];
                                                }
                                                $storedValue = is_array($storedTranslations) ? ($storedTranslations[$localeCode] ?? '') : '';
                                                $fieldName = "translations[$localeCode][{$field['name']}]";
                                                $errorKey = "translations.$localeCode.{$field['name']}";
                                                $inputId = "translations-$localeCode-{$field['name']}";
                                                $isRequired = (bool) ($field['required'] ?? false) && $localeCode === 'en';
                                            @endphp
                                            @include('admin.content._field', [
                                                'field' => $field,
                                                'fieldName' => $fieldName,
                                                'errorKey' => $errorKey,
                                                'inputId' => $inputId,
                                                'value' => $storedValue,
                                                'isRequired' => $isRequired,
                                                'events' => $events,
                                            ])
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </section>
                @endif
            </div>

            <aside class="editor-sidebar" aria-label="Content details and publishing options">
                <section class="panel editor-panel">
                    <div class="panel-heading panel-heading--editor">
                        <div>
                            <p class="eyebrow">Details</p>
                            <h2>Content settings</h2>
                            <p>Configure dates, media, links, and visibility.</p>
                        </div>
                    </div>

                    <div class="form-stack form-stack--compact">
                        @foreach($standardFields as $field)
                            @php
                                $fieldValue = $item->exists
                                    ? $item->getAttribute($field['name'])
                                    : ($field['default'] ?? null);

                                if (($field['type'] ?? null) === 'datetime' && $fieldValue) {
                                    $fieldValue = \Illuminate\Support\Carbon::parse($fieldValue)->format('Y-m-d\TH:i');
                                }
                            @endphp
                            @include('admin.content._field', [
                                'field' => $field,
                                'fieldName' => $field['name'],
                                'errorKey' => $field['name'],
                                'inputId' => 'field-'.$field['name'],
                                'value' => $fieldValue,
                                'isRequired' => (bool) ($field['required'] ?? false) && (($field['type'] ?? '') !== 'document' || ! $isEditing),
                                'events' => $events,
                            ])
                        @endforeach
                    </div>
                </section>

                @if($isEditing)
                    <div class="editor-meta">
                        <p><span>Created</span><time datetime="{{ $item->created_at?->toAtomString() }}">{{ $item->created_at?->format('d M Y, H:i') }}</time></p>
                        <p><span>Last updated</span><time datetime="{{ $item->updated_at?->toAtomString() }}">{{ $item->updated_at?->diffForHumans() }}</time></p>
                    </div>
                @endif
            </aside>
        </div>

        <div class="editor-actions">
            <div>
                <span class="save-hint">Changes become visible according to the publication setting above.</span>
            </div>
            <div class="editor-action-buttons">
                <a class="button button--secondary" href="{{ route('admin.content.index', $type) }}">Cancel</a>
                <button class="button button--primary" type="submit">
                    @include('admin.partials.icon', ['name' => 'check'])
                    <span>{{ $isEditing ? 'Save changes' : 'Create '.$definition['singular'] }}</span>
                </button>
            </div>
        </div>
    </form>
@endsection
