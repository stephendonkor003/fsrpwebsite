<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#172f2a">
    <title>@yield('title', 'Dashboard') · FSRP Events Administration</title>
    <link rel="stylesheet" href="{{ asset('assets/admin.css') }}">
    @stack('head')
</head>
<body class="admin-shell">
    <a class="skip-link" href="#main-content">Skip to main content</a>

    <div class="admin-app" data-admin-app>
        <button
            class="sidebar-scrim"
            type="button"
            data-sidebar-overlay
            aria-label="Close navigation"
            tabindex="-1"
        ></button>

        <aside class="admin-sidebar" id="admin-sidebar" aria-label="Administration navigation" data-sidebar>
            <div class="sidebar-brand-row">
                <a class="admin-brand" href="{{ route('admin.dashboard') }}" aria-label="FSRP Events administration home">
                    <img class="admin-brand-logo" src="{{ asset('images/fsrp/african-union-logo.png') }}" alt="African Union" width="56" height="56">
                    <span class="admin-brand-copy">
                        <strong>FSRP Events</strong>
                        <small>Administration</small>
                    </span>
                </a>

                <button class="icon-button sidebar-close" type="button" data-sidebar-close aria-label="Close navigation">
                    @include('admin.partials.icon', ['name' => 'close'])
                </button>
            </div>

            <nav class="sidebar-nav">
                <p class="sidebar-nav-label">Overview</p>
                <a class="sidebar-link {{ request()->routeIs('admin.dashboard') ? 'is-active' : '' }}" href="{{ route('admin.dashboard') }}" @if(request()->routeIs('admin.dashboard')) aria-current="page" @endif>
                    @include('admin.partials.icon', ['name' => 'dashboard'])
                    <span>Dashboard</span>
                </a>

                <p class="sidebar-nav-label">Website content</p>
                @foreach(config('admin-content.types', []) as $navType => $navDefinition)
                    @php($isCurrentContent = request()->routeIs('admin.content.*') && request()->route('type') === $navType)
                    <a class="sidebar-link {{ $isCurrentContent ? 'is-active' : '' }}" href="{{ route('admin.content.index', $navType) }}" @if($isCurrentContent) aria-current="page" @endif>
                        @include('admin.partials.icon', ['name' => $navType])
                        <span>{{ $navDefinition['label'] }}</span>
                    </a>
                @endforeach

                <p class="sidebar-nav-label">Configuration</p>
                <a class="sidebar-link {{ request()->routeIs('admin.home-sections.*') ? 'is-active' : '' }}" href="{{ route('admin.home-sections.index') }}" @if(request()->routeIs('admin.home-sections.*')) aria-current="page" @endif>
                    @include('admin.partials.icon', ['name' => 'home'])
                    <span>Homepage layout</span>
                </a>
                <a class="sidebar-link {{ request()->routeIs('admin.settings.*') ? 'is-active' : '' }}" href="{{ route('admin.settings.index') }}" @if(request()->routeIs('admin.settings.*')) aria-current="page" @endif>
                    @include('admin.partials.icon', ['name' => 'settings'])
                    <span>Site settings</span>
                </a>
            </nav>

            <div class="sidebar-footer">
                <a class="sidebar-site-link" href="{{ route('home', ['locale' => config('locales.default', 'en')]) }}" target="_blank" rel="noopener">
                    <span>View live website</span>
                    @include('admin.partials.icon', ['name' => 'external'])
                </a>
            </div>
        </aside>

        <div class="admin-workspace">
            <header class="admin-topbar">
                <button
                    class="icon-button sidebar-open"
                    type="button"
                    data-sidebar-open
                    aria-controls="admin-sidebar"
                    aria-expanded="false"
                    aria-label="Open navigation"
                >
                    @include('admin.partials.icon', ['name' => 'menu'])
                </button>

                <div class="topbar-context">
                    <span>FSRP Events</span>
                    <span aria-hidden="true">/</span>
                    <strong>@yield('title', 'Dashboard')</strong>
                </div>

                <details class="user-menu">
                    <summary>
                        <span class="user-avatar" aria-hidden="true">{{ Illuminate\Support\Str::upper(Illuminate\Support\Str::substr(auth()->user()?->name ?? 'A', 0, 1)) }}</span>
                        <span class="user-summary-copy">
                            <strong>{{ auth()->user()?->name ?? 'Administrator' }}</strong>
                            <small>Administrator</small>
                        </span>
                        <span class="user-menu-chevron" aria-hidden="true">⌄</span>
                    </summary>
                    <div class="user-menu-panel">
                        <div class="user-menu-identity">
                            <strong>{{ auth()->user()?->name ?? 'Administrator' }}</strong>
                            <small>{{ auth()->user()?->email }}</small>
                        </div>
                        <form method="POST" action="{{ route('admin.logout') }}">
                            @csrf
                            <button class="user-menu-action" type="submit">
                                @include('admin.partials.icon', ['name' => 'logout'])
                                <span>Sign out</span>
                            </button>
                        </form>
                    </div>
                </details>
            </header>

            <main class="admin-main" id="main-content" tabindex="-1">
                @if(session('status'))
                    <div class="flash-message flash-message--success" role="status" data-flash-message>
                        <span class="flash-message-icon">@include('admin.partials.icon', ['name' => 'check'])</span>
                        <p>{{ session('status') }}</p>
                        <button class="flash-message-close" type="button" data-dismiss-flash aria-label="Dismiss message">×</button>
                    </div>
                @endif

                @if(session('error'))
                    <div class="flash-message flash-message--error" role="alert" data-flash-message>
                        <span class="flash-message-icon">@include('admin.partials.icon', ['name' => 'alert'])</span>
                        <p>{{ session('error') }}</p>
                        <button class="flash-message-close" type="button" data-dismiss-flash aria-label="Dismiss message">×</button>
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>

    <script src="{{ asset('assets/admin.js') }}" defer></script>
    @stack('scripts')
</body>
</html>
