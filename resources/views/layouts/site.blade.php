@php
    $siteName = __('portal.brand');
    $logo = asset('images/brand/african-union-logo.png');
    $routeName = request()->route()?->getName();
    $isPrivateRegistrationRoute = in_array($routeName, ['seed-summit.registrations.show', 'seed-summit.registrations.pdf', 'seed-summit.registrations.verify-email.show', 'seed-summit.registrations.verify-email'], true);
@endphp
<!DOCTYPE html>
<html lang="{{ $locale }}" dir="{{ $locale === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#006b3f">
    @include('site.partials.seo')
    <link rel="icon" href="{{ $logo }}">
    <link rel="stylesheet" href="{{ asset('assets/site.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/african-union-events.css') }}">
    @stack('styles')
</head>
<body class="events-platform page-{{ str_replace('.', '-', (string) $routeName) }}">
    <a class="skip-link" href="#main-content">{{ __('ui.common.skip_to_content') }}</a>
    <header class="site-header" data-header>
        <div class="container main-nav">
            <a class="brand header-brand" href="{{ route('home', $locale) }}" aria-label="{{ $siteName }} — {{ __('ui.nav.home') }}">
                <img src="{{ $logo }}" alt="African Union" width="100" height="63">
                <span class="brand-copy"><strong>{{ $siteName }}</strong><small>{{ __('portal.descriptor') }}</small></span>
            </a>
            <button class="nav-toggle" type="button" aria-controls="primary-navigation" aria-expanded="false" data-nav-toggle><span class="nav-toggle-lines" aria-hidden="true"><i></i><i></i><i></i></span><span class="sr-only">{{ __('ui.common.open_menu') }}</span></button>
            <nav id="primary-navigation" class="primary-navigation" aria-label="{{ __('ui.common.primary_navigation') }}" data-navigation>
                @foreach(['home' => __('ui.nav.home'), 'events.index' => __('ui.nav.events'), 'programs' => __('portal.programme'), 'speakers' => __('ui.sessions.speakers'), 'resources.index' => __('portal.resources'), 'news.index' => __('ui.nav.news'), 'about' => __('ui.nav.about')] as $name => $label)
                    <a href="{{ route($name, $locale) }}" @class(['active' => $routeName === $name || ($name === 'events.index' && $routeName === 'events.show') || ($name === 'news.index' && $routeName === 'news.show')]) @if($routeName === $name) aria-current="page" @endif>{{ $label }}</a>
                @endforeach
                @unless($isPrivateRegistrationRoute)<div class="language-menu">
                    <button class="language-trigger" type="button" aria-expanded="false" aria-label="{{ __('ui.labels.language') }}" data-language-trigger>@include('site.partials.icon', ['name' => 'globe'])<span>{{ strtoupper($locale) }}</span>@include('site.partials.icon', ['name' => 'chevron'])</button>
                    <div class="language-dropdown" data-language-dropdown>
                        @foreach($locales as $code => $language)
                            @php
                                $parameters = array_merge(request()->query(), request()->route()?->parameters() ?? [], ['locale' => $code]);
                                $languageUrl = $routeName ? route($routeName, $parameters) : route('home', $code);
                            @endphp
                            <a href="{{ $languageUrl }}" lang="{{ $code }}" dir="{{ $language['direction'] }}" @class(['active' => $code === $locale])>{{ $language['native_name'] }} @if($code === $locale)<span aria-hidden="true">✓</span>@endif</a>
                        @endforeach
                    </div>
                </div>@endunless
                <a class="nav-cta" href="{{ route('seed-summit.registration.create', $locale) }}">{{ __('ui.actions.register_now') }} @include('site.partials.icon', ['name' => 'arrow'])</a>
            </nav>
        </div>
    </header>
    <main id="main-content">@yield('content')</main>
    <footer class="site-footer">
        <div class="container footer-grid">
            <div class="footer-brand">
                <a class="brand" href="{{ route('home', $locale) }}"><img src="{{ $logo }}" alt="African Union" width="100" height="63"><span class="brand-copy"><strong>{{ $siteName }}</strong><small>{{ __('portal.descriptor') }}</small></span></a>
                <p>{{ __('portal.footer_about') }}</p>
                <a class="footer-main-site" href="{{ route('events.index', $locale) }}">{{ __('portal.all_events') }} @include('site.partials.icon', ['name' => 'arrow'])</a>
            </div>
            <div class="footer-column"><h2>{{ __('portal.footer_links') }}</h2><a href="{{ route('events.index', $locale) }}">{{ __('ui.nav.events') }}</a><a href="{{ route('programs', $locale) }}">{{ __('portal.programme') }}</a><a href="{{ route('speakers', $locale) }}">{{ __('ui.sessions.speakers') }}</a><a href="{{ route('resources.index', $locale) }}">{{ __('portal.downloads') }}</a><a href="{{ route('faq', $locale) }}">{{ __('ui.nav.faq') }}</a></div>
            <div class="footer-column"><h2>{{ __('portal.footer_platform') }}</h2><a href="{{ route('about', $locale) }}">{{ __('ui.nav.about') }}</a><a href="{{ route('news.index', $locale) }}">{{ __('ui.nav.news') }}</a><a href="{{ route('login') }}">{{ __('ui.footer.administration') }}</a></div>
            <div class="footer-column"><h2>{{ __('ui.footer.languages') }}</h2><p>{{ __('portal.footer_note') }}</p><div class="language-pills">@foreach($locales as $code => $language)<a href="{{ route('home', $code) }}" lang="{{ $code }}">{{ $language['native_name'] }}</a>@endforeach</div></div>
        </div>
        <div class="container footer-bottom"><p>{{ __('portal.copyright', ['year' => date('Y')]) }}</p><span>{{ __('portal.region') }}</span></div>
    </footer>
    <button class="back-to-top" type="button" aria-label="{{ __('ui.common.back_to_top') }}" data-back-to-top><svg aria-hidden="true" viewBox="0 0 24 24"><path d="m6 14 6-6 6 6"/></svg></button>
    <script src="{{ asset('assets/site.js') }}" defer></script>
    @stack('scripts')
</body>
</html>
