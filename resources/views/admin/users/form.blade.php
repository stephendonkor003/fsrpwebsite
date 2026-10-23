@extends('admin.layouts.app')

@php
    $isEditing = $formMode === 'edit';
    $isOwnAccount = $isEditing && $user->is(auth()->user());
@endphp

@section('title', $isEditing ? 'Edit user' : 'Create new user')

@section('content')
    <div class="users-workspace">
        <div class="page-heading page-heading--editor">
            <div>
                <a class="back-link" href="{{ route('admin.users.index') }}">@include('admin.partials.icon', ['name' => 'arrow-left'])<span>Back to users</span></a>
                <p class="eyebrow">Account administration</p>
                <h1>{{ $isEditing ? 'Edit user' : 'Create new user' }}</h1>
                <p>{{ $isEditing ? 'Update account details, change access, or set a new password.' : 'Add an account and choose its login status and administration access.' }}</p>
            </div>
        </div>

        @if($errors->any())
            <div class="form-error-summary" role="alert" tabindex="-1" data-error-summary>
                @include('admin.partials.icon', ['name' => 'alert'])
                <div><strong>Please review the highlighted fields.</strong><p>{{ $errors->first() }}</p></div>
            </div>
        @endif

        <form method="POST" action="{{ $isEditing ? route('admin.users.update', $user) : route('admin.users.store') }}" class="content-editor-form">
            @csrf
            @if($isEditing) @method('PUT') @endif
            <div class="editor-grid">
                <div class="editor-main form-stack">
                    <section class="panel editor-panel" aria-labelledby="user-details-heading">
                        <div class="panel-heading"><div><h2 id="user-details-heading">Account details</h2><p>The email address is used to sign in.</p></div></div>
                        <div class="form-stack users-panel-body">
                            @foreach(['name' => 'Full name', 'email' => 'Email address'] as $field => $label)
                                <div class="form-field">
                                    <label for="{{ $field }}">{{ $label }} <span class="required-mark">*</span></label>
                                    <input id="{{ $field }}" name="{{ $field }}" type="{{ $field === 'email' ? 'email' : 'text' }}" value="{{ old($field, $user?->{$field}) }}" maxlength="255" autocomplete="{{ $field }}" required aria-invalid="{{ $errors->has($field) ? 'true' : 'false' }}" @error($field) aria-describedby="{{ $field }}-error" @enderror>
                                    @error($field)<p class="field-error" id="{{ $field }}-error">{{ $message }}</p>@enderror
                                </div>
                            @endforeach
                        </div>
                    </section>

                    <section class="panel editor-panel" aria-labelledby="user-password-heading">
                        <div class="panel-heading"><div><h2 id="user-password-heading">{{ $isEditing ? 'Change password' : 'Account password' }}</h2><p>{{ $isEditing ? 'Leave both fields empty to keep the current password.' : 'Choose a unique password and share it securely with the account holder.' }}</p></div></div>
                        <div class="form-stack users-panel-body">
                            @foreach(['password' => ($isEditing ? 'New password' : 'Password'), 'password_confirmation' => 'Confirm password'] as $field => $label)
                                <div class="form-field">
                                    <label for="{{ $field }}">{{ $label }}@unless($isEditing) <span class="required-mark">*</span>@endunless</label>
                                    <input id="{{ $field }}" name="{{ $field }}" type="password" autocomplete="new-password" minlength="12" maxlength="72" @required(! $isEditing) aria-invalid="{{ $errors->has($field) ? 'true' : 'false' }}" aria-describedby="{{ 'password-help'.($errors->has($field) ? ' '.$field.'-error' : '') }}">
                                    @error($field)<p class="field-error" id="{{ $field }}-error">{{ $message }}</p>@enderror
                                </div>
                            @endforeach
                            <p class="field-help" id="password-help">Use at least 12 characters with uppercase and lowercase letters, a number, and a symbol. Changing a password ends the user's existing sessions.</p>
                        </div>
                    </section>
                </div>

                <aside class="editor-sidebar">
                    <section class="panel editor-panel" aria-labelledby="user-access-heading">
                        <div class="panel-heading"><div><h2 id="user-access-heading">Login &amp; permissions</h2><p>Control access without deleting the account.</p></div></div>
                        <div class="form-stack users-panel-body">
                            @foreach(['is_active' => ['Allow login', 'Turn off to block sign ins and end current sessions.'], 'is_admin' => ['Administrator access', 'Required to sign in to this administration portal.']] as $field => [$label, $help])
                                <div class="form-field">
                                    <input type="hidden" name="{{ $field }}" value="{{ $isOwnAccount ? '1' : '0' }}">
                                    <label class="toggle-field" for="{{ $field }}">
                                        <span><strong>{{ $label }}</strong><small>{{ $help }}</small></span>
                                        <span class="toggle-control">
                                            <input id="{{ $field }}" name="{{ $field }}" type="checkbox" value="1" @checked($isOwnAccount || old($field, $user?->{$field} ?? true)) @disabled($isOwnAccount) aria-describedby="{{ $field }}-help">
                                            <span aria-hidden="true"></span>
                                        </span>
                                    </label>
                                    <p class="field-help" id="{{ $field }}-help">{{ $isOwnAccount ? 'Your own administrator access is protected.' : $help }}</p>
                                    @error($field)<p class="field-error">{{ $message }}</p>@enderror
                                </div>
                            @endforeach
                        </div>
                    </section>
                    <section class="panel users-panel-body">
                        <h2>Access guidance</h2>
                        <p class="users-access-note">Only active administrators can enter the administration portal. Standard accounts have no administration access.</p>
                        @if($isEditing)<p class="users-access-note">Created {{ $user->created_at?->format('d M Y') }}. Last sign in: {{ $user->last_login_at?->format('d M Y, H:i') ?? 'Never' }}.</p>@endif
                    </section>
                </aside>
            </div>
            <div class="editor-actions">
                <span class="save-hint">{{ $isEditing ? 'Changes take effect when you save.' : 'The account will use the access settings above.' }}</span>
                <div class="editor-action-buttons">
                    <a class="button button--secondary" href="{{ route('admin.users.index') }}">Cancel</a>
                    <button class="button button--primary" type="submit">{{ $isEditing ? 'Save changes' : 'Create user' }}</button>
                </div>
            </div>
        </form>
    </div>
@endsection
