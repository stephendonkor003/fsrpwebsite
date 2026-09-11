@extends('admin.layouts.app')

@section('title', 'Site settings')

@section('content')
    @php
        $firstLocale = array_key_first($locales);
        $translatedSettings = [
            ['name' => 'site_name', 'label' => 'Website name', 'type' => 'text', 'required' => true, 'help' => 'The name used in the header, page titles, and search results.'],
            ['name' => 'tagline', 'label' => 'Website tagline', 'type' => 'textarea', 'required' => true, 'help' => 'A concise description of the initiative.'],
            ['name' => 'address', 'label' => 'Office address', 'type' => 'textarea'],
            ['name' => 'footer_blurb', 'label' => 'Footer introduction', 'type' => 'textarea', 'help' => 'A short summary shown beside the footer navigation.'],
            ['name' => 'copyright', 'label' => 'Copyright line', 'type' => 'text', 'help' => 'The year can be included directly, for example: © 2026 African Union.'],
        ];
        $plainSettings = [
            ['name' => 'contact_email', 'label' => 'Contact email', 'type' => 'email', 'help' => 'Public email address for website enquiries.'],
            ['name' => 'contact_phone', 'label' => 'Contact phone', 'type' => 'tel'],
            ['name' => 'facebook_url', 'label' => 'Facebook URL', 'type' => 'url'],
            ['name' => 'linkedin_url', 'label' => 'LinkedIn URL', 'type' => 'url'],
            ['name' => 'youtube_url', 'label' => 'YouTube URL', 'type' => 'url'],
        ];
        $logoPath = data_get($settings->get('logo')?->value, 'value');
    @endphp

    <div class="page-heading">
        <div>
            <p class="eyebrow">Configuration</p>
            <h1>Site settings</h1>
            <p>Manage the public identity, contact details, footer copy, and social channels.</p>
        </div>
        <a class="button button--secondary" href="{{ route('home', ['locale' => config('locales.default', 'en')]) }}" target="_blank" rel="noopener">
            <span>View website</span>
            @include('admin.partials.icon', ['name' => 'external'])
        </a>
    </div>

    @if($errors->any())
        <div class="form-error-summary" role="alert" tabindex="-1" data-error-summary>
            @include('admin.partials.icon', ['name' => 'alert'])
            <div>
                <strong>Please review the highlighted fields.</strong>
                <p>{{ $errors->count() }} {{ Illuminate\Support\Str::plural('issue', $errors->count()) }} prevented the settings from being saved.</p>
            </div>
        </div>
    @endif

    <form class="settings-form" method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="settings-grid">
            <div class="settings-main">
                <section class="panel editor-panel" aria-labelledby="site-copy-heading">
                    <div class="panel-heading panel-heading--editor">
                        <div>
                            <p class="eyebrow">Public copy</p>
                            <h2 id="site-copy-heading">Website details</h2>
                            <p>Keep the website identity consistent in all six African Union languages.</p>
                        </div>
                    </div>

                    <div class="language-tabs" data-language-tabs>
                        <div class="language-tab-list" role="tablist" aria-label="Settings language">
                            @foreach($locales as $localeCode => $localeDefinition)
                                @php
                                    $localeName = is_array($localeDefinition) ? ($localeDefinition['name'] ?? strtoupper($localeCode)) : (string) $localeDefinition;
                                    $nativeName = is_array($localeDefinition) ? ($localeDefinition['native_name'] ?? $localeName) : $localeName;
                                    $localeHasErrors = $errors->has("settings.*.$localeCode");
                                @endphp
                                <button
                                    id="settings-language-tab-{{ $localeCode }}"
                                    class="language-tab {{ $localeCode === $firstLocale ? 'is-active' : '' }} {{ $localeHasErrors ? 'has-error' : '' }}"
                                    type="button"
                                    role="tab"
                                    aria-selected="{{ $localeCode === $firstLocale ? 'true' : 'false' }}"
                                    aria-controls="settings-language-panel-{{ $localeCode }}"
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
                                id="settings-language-panel-{{ $localeCode }}"
                                class="language-panel"
                                role="tabpanel"
                                aria-labelledby="settings-language-tab-{{ $localeCode }}"
                                data-language-panel="{{ $localeCode }}"
                                dir="{{ $localeDirection }}"
                                @if($localeCode !== $firstLocale) hidden @endif
                            >
                                <div class="language-panel-heading">
                                    <span class="language-code">{{ strtoupper($localeCode) }}</span>
                                    <div>
                                        <h3>{{ $localeName }} settings</h3>
                                        <p>{{ $localeCode === 'en' ? 'Primary website language' : 'Translated website copy' }}</p>
                                    </div>
                                </div>

                                <div class="form-stack">
                                    @foreach($translatedSettings as $field)
                                        @php
                                            $fieldName = "settings[{$field['name']}][$localeCode]";
                                            $errorKey = "settings.{$field['name']}.$localeCode";
                                            $inputId = "setting-{$field['name']}-$localeCode";
                                            $fieldValue = data_get($settings->get($field['name'])?->value, $localeCode, '');
                                            $isRequired = (bool) ($field['required'] ?? false) && $localeCode === 'en';
                                        @endphp
                                        @include('admin.content._field', [
                                            'field' => $field,
                                            'fieldName' => $fieldName,
                                            'errorKey' => $errorKey,
                                            'inputId' => $inputId,
                                            'value' => $fieldValue,
                                            'isRequired' => $isRequired,
                                            'events' => [],
                                        ])
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </section>
            </div>

            <aside class="settings-sidebar" aria-label="Brand and contact settings">
                <section class="panel editor-panel" aria-labelledby="brand-heading">
                    <div class="panel-heading panel-heading--editor">
                        <div>
                            <p class="eyebrow">Identity</p>
                            <h2 id="brand-heading">Website logo</h2>
                            <p>Upload a clear horizontal or square logo with a transparent background.</p>
                        </div>
                    </div>
                    @include('admin.content._field', [
                        'field' => ['name' => 'logo', 'label' => 'Logo file', 'type' => 'image', 'help' => 'JPG, PNG, or WebP. Maximum file size: 4 MB.'],
                        'fieldName' => 'logo',
                        'errorKey' => 'logo',
                        'inputId' => 'setting-logo',
                        'value' => $logoPath,
                        'isRequired' => false,
                        'events' => [],
                    ])
                </section>

                <section class="panel editor-panel" aria-labelledby="contact-heading">
                    <div class="panel-heading panel-heading--editor">
                        <div>
                            <p class="eyebrow">Get in touch</p>
                            <h2 id="contact-heading">Contact details</h2>
                            <p>These details may appear in the website footer.</p>
                        </div>
                    </div>
                    <div class="form-stack form-stack--compact">
                        @foreach(array_slice($plainSettings, 0, 2) as $field)
                            @include('admin.content._field', [
                                'field' => $field,
                                'fieldName' => "settings[{$field['name']}]",
                                'errorKey' => "settings.{$field['name']}",
                                'inputId' => 'setting-'.$field['name'],
                                'value' => data_get($settings->get($field['name'])?->value, 'value', ''),
                                'isRequired' => false,
                                'events' => [],
                            ])
                        @endforeach
                    </div>
                </section>

                <section class="panel editor-panel" aria-labelledby="social-heading">
                    <div class="panel-heading panel-heading--editor">
                        <div>
                            <p class="eyebrow">Connect</p>
                            <h2 id="social-heading">Social channels</h2>
                            <p>Leave a field blank to hide that channel.</p>
                        </div>
                    </div>
                    <div class="form-stack form-stack--compact">
                        @foreach(array_slice($plainSettings, 2) as $field)
                            @include('admin.content._field', [
                                'field' => $field,
                                'fieldName' => "settings[{$field['name']}]",
                                'errorKey' => "settings.{$field['name']}",
                                'inputId' => 'setting-'.$field['name'],
                                'value' => data_get($settings->get($field['name'])?->value, 'value', ''),
                                'isRequired' => false,
                                'events' => [],
                            ])
                        @endforeach
                    </div>
                </section>
            </aside>
        </div>

        <div class="editor-actions">
            <span class="save-hint">Settings apply across the public website.</span>
            <div class="editor-action-buttons">
                <a class="button button--secondary" href="{{ route('admin.dashboard') }}">Cancel</a>
                <button class="button button--primary" type="submit">
                    @include('admin.partials.icon', ['name' => 'check'])
                    <span>Save settings</span>
                </button>
            </div>
        </div>
    </form>
@endsection
