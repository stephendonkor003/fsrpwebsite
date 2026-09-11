<section class="page-hero {{ $heroClass ?? '' }}">
    @if(!empty($heroImage))<img src="{{ $heroImage }}" alt="">@endif
    <div class="page-hero-overlay"></div>
    <div class="page-hero-pattern" aria-hidden="true"></div>
    <div class="container page-hero-inner">
        <nav class="breadcrumbs" aria-label="{{ __('ui.common.breadcrumbs') }}">
            <a href="{{ route('home', $locale) }}">{{ __('ui.nav.home') }}</a>
            <svg aria-hidden="true" viewBox="0 0 24 24"><path d="m9 18 6-6-6-6"/></svg>
            <span aria-current="page">{{ $title }}</span>
        </nav>
        @if(!empty($eyebrow))<p class="eyebrow eyebrow-light"><span></span>{{ $eyebrow }}</p>@endif
        <h1>{{ $title }}</h1>
        @if(!empty($summary))<p>{{ $summary }}</p>@endif
    </div>
</section>
