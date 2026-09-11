@php
    $fieldType = $field['type'] ?? 'text';
    $fieldValue = old($errorKey, $value ?? ($field['default'] ?? null));
    $isRequired = $isRequired ?? (bool) ($field['required'] ?? false);
    $errorId = $inputId.'-error';
    $helpId = $inputId.'-help';
    $defaultHelp = match ($fieldType) {
        'slug' => 'Use lowercase letters, numbers, and hyphens only.',
        'richtext' => 'Use short paragraphs and clear headings for easier reading.',
        'image' => 'JPG, PNG, or WebP. Maximum file size: 6 MB.',
        'document' => 'PDF, DOCX, or XLSX. Maximum file size: 20 MB. Choose a new file to replace the current document.',
        'datetime' => 'Times are entered in the website’s configured timezone.',
        default => null,
    };
    $helpText = $field['help'] ?? $defaultHelp;
    $describedBy = collect([
        $helpText ? $helpId : null,
        $errors->has($errorKey) ? $errorId : null,
    ])->filter()->implode(' ');
@endphp

<div class="form-field form-field--{{ $fieldType }} {{ $errors->has($errorKey) ? 'has-error' : '' }}">
    @if($fieldType === 'boolean')
        <input type="hidden" name="{{ $fieldName }}" value="0">
        <label class="toggle-field" for="{{ $inputId }}">
            <span>
                <strong>{{ $field['label'] }}</strong>
                @if($field['description'] ?? false)
                    <small>{{ $field['description'] }}</small>
                @else
                    <small>Turn this on to make the option active.</small>
                @endif
            </span>
            <span class="toggle-control">
                <input
                    id="{{ $inputId }}"
                    name="{{ $fieldName }}"
                    type="checkbox"
                    value="1"
                    @checked((bool) $fieldValue)
                    @if($describedBy) aria-describedby="{{ $describedBy }}" @endif
                >
                <span aria-hidden="true"></span>
            </span>
        </label>
    @else
        <label for="{{ $inputId }}">
            {{ $field['label'] }}
            @if($isRequired)
                <span class="required-mark" aria-hidden="true">*</span>
                <span class="sr-only">(required)</span>
            @endif
        </label>

        @switch($fieldType)
            @case('textarea')
                <textarea
                    id="{{ $inputId }}"
                    name="{{ $fieldName }}"
                    rows="5"
                    @if($field['placeholder'] ?? false) placeholder="{{ $field['placeholder'] }}" @endif
                    @if($isRequired) required @endif
                    @if($describedBy) aria-describedby="{{ $describedBy }}" @endif
                    aria-invalid="{{ $errors->has($errorKey) ? 'true' : 'false' }}"
                >{{ $fieldValue }}</textarea>
                @break

            @case('richtext')
                <div class="richtext-field" data-richtext-field>
                    <div class="richtext-toolbar" aria-hidden="true">
                        <span><strong>B</strong></span>
                        <span><em>I</em></span>
                        <span>H2</span>
                        <span>• List</span>
                        <span>Link</span>
                    </div>
                    <textarea
                        id="{{ $inputId }}"
                        name="{{ $fieldName }}"
                        rows="12"
                        data-richtext-input
                        @if($isRequired) required @endif
                        @if($describedBy) aria-describedby="{{ $describedBy }}" @endif
                        aria-invalid="{{ $errors->has($errorKey) ? 'true' : 'false' }}"
                    >{{ $fieldValue }}</textarea>
                </div>
                @break

            @case('number')
                <input
                    id="{{ $inputId }}"
                    name="{{ $fieldName }}"
                    type="number"
                    value="{{ $fieldValue }}"
                    min="{{ $field['min'] ?? 0 }}"
                    @if(isset($field['max'])) max="{{ $field['max'] }}" @endif
                    step="{{ $field['step'] ?? 1 }}"
                    inputmode="numeric"
                    @if($isRequired) required @endif
                    @if($describedBy) aria-describedby="{{ $describedBy }}" @endif
                    aria-invalid="{{ $errors->has($errorKey) ? 'true' : 'false' }}"
                >
                @break

            @case('select')
                <div class="select-wrap">
                    <select
                        id="{{ $inputId }}"
                        name="{{ $fieldName }}"
                        @if($isRequired) required @endif
                        @if($describedBy) aria-describedby="{{ $describedBy }}" @endif
                        aria-invalid="{{ $errors->has($errorKey) ? 'true' : 'false' }}"
                    >
                        <option value="">Select an option</option>
                        @foreach($field['options'] ?? [] as $optionValue => $optionLabel)
                            <option value="{{ $optionValue }}" @selected((string) $fieldValue === (string) $optionValue)>{{ $optionLabel }}</option>
                        @endforeach
                    </select>
                </div>
                @break

            @case('event')
                <div class="select-wrap">
                    <select
                        id="{{ $inputId }}"
                        name="{{ $fieldName }}"
                        @if($isRequired) required @endif
                        @if($describedBy) aria-describedby="{{ $describedBy }}" @endif
                        aria-invalid="{{ $errors->has($errorKey) ? 'true' : 'false' }}"
                    >
                        <option value="">No event selected</option>
                        @foreach($events ?? [] as $eventOption)
                            @php
                                $eventOptionTitle = $eventOption->title;
                                if (is_string($eventOptionTitle)) {
                                    $decodedEventTitle = json_decode($eventOptionTitle, true);
                                    $eventOptionTitle = is_array($decodedEventTitle) ? $decodedEventTitle : $eventOptionTitle;
                                }
                                $eventOptionLabel = is_array($eventOptionTitle)
                                    ? ($eventOptionTitle['en'] ?? collect($eventOptionTitle)->first() ?? 'Untitled event')
                                    : $eventOptionTitle;
                            @endphp
                            <option value="{{ $eventOption->getKey() }}" @selected((string) $fieldValue === (string) $eventOption->getKey())>{{ $eventOptionLabel }}{{ $eventOption->is_published ? '' : ' (Draft)' }}</option>
                        @endforeach
                    </select>
                </div>
                @break

            @case('datetime')
                <input
                    id="{{ $inputId }}"
                    name="{{ $fieldName }}"
                    type="datetime-local"
                    value="{{ $fieldValue }}"
                    @if($isRequired) required @endif
                    @if($describedBy) aria-describedby="{{ $describedBy }}" @endif
                    aria-invalid="{{ $errors->has($errorKey) ? 'true' : 'false' }}"
                >
                @break

            @case('image')
                <div class="image-upload" data-image-upload>
                    @if($value)
                        <div class="image-upload-preview" data-image-preview>
                            <img src="{{ $value }}" alt="Current {{ Illuminate\Support\Str::lower($field['label']) }}">
                            <span>Current image</span>
                        </div>
                    @else
                        <div class="image-upload-preview image-upload-preview--empty" data-image-preview>
                            @include('admin.partials.icon', ['name' => 'image'])
                            <span>No image selected</span>
                        </div>
                    @endif
                    <label class="image-upload-picker" for="{{ $inputId }}">
                        @include('admin.partials.icon', ['name' => 'upload'])
                        <span><strong>Choose an image</strong><small>or drag and drop it here</small></span>
                    </label>
                    <input
                        id="{{ $inputId }}"
                        class="image-upload-input"
                        name="{{ $fieldName }}"
                        type="file"
                        accept="image/jpeg,image/png,image/webp"
                        data-image-input
                        @if($describedBy) aria-describedby="{{ $describedBy }}" @endif
                        aria-invalid="{{ $errors->has($errorKey) ? 'true' : 'false' }}"
                    >
                </div>
                @break

            @case('document')
                @if($item->exists && $item->getAttribute('original_filename'))
                    <p class="field-help">
                        Current file: <a href="{{ route('admin.resources.download', ['resource' => $item]) }}" download="{{ $item->original_filename }}">{{ $item->original_filename }}</a>
                        ({{ number_format($item->file_size / 1024, 0) }} KB)
                    </p>
                @endif
                <input
                    id="{{ $inputId }}"
                    name="{{ $fieldName }}"
                    type="file"
                    accept=".pdf,.docx,.xlsx,application/pdf,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
                    @if(! $item->exists) required @endif
                    @if($describedBy) aria-describedby="{{ $describedBy }}" @endif
                    aria-invalid="{{ $errors->has($errorKey) ? 'true' : 'false' }}"
                >
                @break

            @case('slug')
                <div class="input-prefix">
                    <span aria-hidden="true">/</span>
                    <input
                        id="{{ $inputId }}"
                        name="{{ $fieldName }}"
                        type="text"
                        value="{{ $fieldValue }}"
                        pattern="[a-z0-9]+(?:-[a-z0-9]+)*"
                        autocomplete="off"
                        data-slug-input
                        @if($isRequired) required @endif
                        @if($describedBy) aria-describedby="{{ $describedBy }}" @endif
                        aria-invalid="{{ $errors->has($errorKey) ? 'true' : 'false' }}"
                    >
                </div>
                @break

            @case('url')
                <input
                    id="{{ $inputId }}"
                    name="{{ $fieldName }}"
                    type="url"
                    value="{{ $fieldValue }}"
                    placeholder="https://"
                    inputmode="url"
                    autocomplete="url"
                    @if($isRequired) required @endif
                    @if($describedBy) aria-describedby="{{ $describedBy }}" @endif
                    aria-invalid="{{ $errors->has($errorKey) ? 'true' : 'false' }}"
                >
                @break

            @case('email')
                <input
                    id="{{ $inputId }}"
                    name="{{ $fieldName }}"
                    type="email"
                    value="{{ $fieldValue }}"
                    inputmode="email"
                    autocomplete="email"
                    @if($isRequired) required @endif
                    @if($describedBy) aria-describedby="{{ $describedBy }}" @endif
                    aria-invalid="{{ $errors->has($errorKey) ? 'true' : 'false' }}"
                >
                @break

            @case('tel')
                <input
                    id="{{ $inputId }}"
                    name="{{ $fieldName }}"
                    type="tel"
                    value="{{ $fieldValue }}"
                    inputmode="tel"
                    autocomplete="tel"
                    @if($isRequired) required @endif
                    @if($describedBy) aria-describedby="{{ $describedBy }}" @endif
                    aria-invalid="{{ $errors->has($errorKey) ? 'true' : 'false' }}"
                >
                @break

            @default
                <input
                    id="{{ $inputId }}"
                    name="{{ $fieldName }}"
                    type="text"
                    value="{{ $fieldValue }}"
                    @if($field['placeholder'] ?? false) placeholder="{{ $field['placeholder'] }}" @endif
                    @if($isRequired) required @endif
                    @if($describedBy) aria-describedby="{{ $describedBy }}" @endif
                    aria-invalid="{{ $errors->has($errorKey) ? 'true' : 'false' }}"
                >
        @endswitch
    @endif

    @if($helpText)
        <p class="field-help" id="{{ $helpId }}">{{ $helpText }}</p>
    @endif

    @error($errorKey)
        <p class="field-error" id="{{ $errorId }}">{{ $message }}</p>
    @enderror
</div>
