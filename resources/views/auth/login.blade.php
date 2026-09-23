<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#172f2a">
    <title>Administrator sign in · African Union Events</title>
    <link rel="stylesheet" href="{{ asset('assets/admin.css') }}?v={{ filemtime(public_path('assets/admin.css')) }}">
</head>
<body class="login-page">
    <main class="login-shell">
        <section class="login-visual" aria-labelledby="login-welcome-title">
            <div class="login-visual-pattern" aria-hidden="true"></div>
            <a class="login-brand" href="{{ route('home', ['locale' => config('locales.default', 'en')]) }}" aria-label="Visit the African Union Events website">
                <img class="admin-brand-logo" src="{{ asset('images/brand/african-union-logo.png') }}" alt="African Union" width="56" height="56">
                <span>
                    <strong>African Union Events</strong>
                    <small>Events &amp; Programmes</small>
                </span>
            </a>

            <div class="login-visual-copy">
                <p class="eyebrow eyebrow--light">Back office</p>
                <h1 id="login-welcome-title">Shape every moment of the programme.</h1>
                <p>Manage events, sessions, stories, translations, and the public website from one secure workspace.</p>
            </div>

            <p class="login-visual-footnote">African Union official languages supported</p>
        </section>

        <section class="login-panel" aria-labelledby="login-title">
            <div class="login-form-wrap">
                <div class="login-mobile-brand" aria-hidden="true">
                    <img class="admin-brand-logo" src="{{ asset('images/brand/african-union-logo.png') }}" alt="" width="56" height="56">
                    <strong>African Union Events</strong>
                </div>

                <div class="login-heading">
                    <p class="eyebrow">Administration portal</p>
                    <h2 id="login-title">Welcome back</h2>
                    <p>Sign in with your administrator account to continue.</p>
                </div>

                @if(session('status'))
                    <div class="flash-message flash-message--success" role="status">
                        <span class="flash-message-icon">@include('admin.partials.icon', ['name' => 'check'])</span>
                        <p>{{ session('status') }}</p>
                    </div>
                @endif

                @if($errors->any())
                    <div class="form-error-summary" role="alert" tabindex="-1">
                        @include('admin.partials.icon', ['name' => 'alert'])
                        <div>
                            <strong>We could not sign you in.</strong>
                            <p>{{ $errors->first() }}</p>
                        </div>
                    </div>
                @endif

                <form class="login-form" method="POST" action="{{ route('login.store') }}">
                    @csrf

                    <div class="form-field">
                        <label for="email">Email address</label>
                        <div class="input-with-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <rect x="3" y="5" width="18" height="14" rx="2" />
                                <path d="m3 7 9 6 9-6" />
                            </svg>
                            <input
                                id="email"
                                name="email"
                                type="email"
                                value="{{ old('email') }}"
                                autocomplete="email"
                                inputmode="email"
                                required
                                autofocus
                                aria-invalid="{{ $errors->has('email') ? 'true' : 'false' }}"
                                @error('email') aria-describedby="email-error" @enderror
                            >
                        </div>
                        @error('email')
                            <p class="field-error" id="email-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="form-field">
                        <label for="password">Password</label>
                        <div class="input-with-icon input-with-action">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <rect x="4" y="10" width="16" height="11" rx="2" />
                                <path d="M8 10V7a4 4 0 0 1 8 0v3" />
                            </svg>
                            <input
                                id="password"
                                name="password"
                                type="password"
                                autocomplete="current-password"
                                required
                                aria-invalid="{{ $errors->has('password') ? 'true' : 'false' }}"
                                @error('password') aria-describedby="password-error" @enderror
                            >
                            <button class="password-toggle" type="button" data-password-toggle="password" aria-label="Show password" aria-pressed="false">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                    <path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z" />
                                    <circle cx="12" cy="12" r="2.5" />
                                </svg>
                            </button>
                        </div>
                        @error('password')
                            <p class="field-error" id="password-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <label class="checkbox-field checkbox-field--compact">
                        <input name="remember" type="checkbox" value="1" @checked(old('remember'))>
                        <span class="checkbox-control" aria-hidden="true"></span>
                        <span>Keep me signed in on this device</span>
                    </label>

                    <button class="button button--primary button--large button--full" type="submit">
                        <span>Sign in securely</span>
                        @include('admin.partials.icon', ['name' => 'arrow-right'])
                    </button>
                </form>

                <p class="login-help">Having trouble signing in? Contact your system administrator.</p>
            </div>
        </section>
    </main>

    <script src="{{ asset('assets/admin.js') }}?v={{ filemtime(public_path('assets/admin.js')) }}" defer></script>
</body>
</html>
