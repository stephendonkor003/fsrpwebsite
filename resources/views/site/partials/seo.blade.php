@php
    $seo = app(\App\Seo::class);
    $seoEvent = $routeName === 'events.show' ? $event : null;
    $seoPost = $routeName === 'news.show' ? $post : null;
    $metadata = $seo->page(request(), $siteName, $seoEvent, $seoPost);
    $structuredData = app(\App\StructuredData::class)->graph($locale, $siteName, $metadata['title'], $metadata['description'], $metadata['canonical'], $seoEvent, $seoPost);
@endphp
<title>{{ $metadata['title'] }}</title>
<meta name="description" content="{{ $metadata['description'] }}">
<meta name="robots" content="{{ $metadata['robots'] }}">
<link rel="canonical" href="{{ $metadata['canonical'] }}">
@foreach($metadata['alternates'] as $language => $href)
    <link rel="alternate" hreflang="{{ $language }}" href="{{ $href }}">
@endforeach
<meta property="og:site_name" content="{{ $siteName }}">
<meta property="og:type" content="{{ $metadata['type'] }}">
<meta property="og:title" content="{{ $metadata['title'] }}">
<meta property="og:description" content="{{ $metadata['description'] }}">
<meta property="og:url" content="{{ $metadata['canonical'] }}">
<meta property="og:locale" content="{{ $metadata['locale'] }}">
@foreach(config('seo.social_locales') as $code => $socialLocale)
    @if($code !== $locale)<meta property="og:locale:alternate" content="{{ $socialLocale }}">@endif
@endforeach
<meta property="og:image" content="{{ $metadata['image'] }}">
<meta property="og:image:secure_url" content="{{ $metadata['image'] }}">
<meta property="og:image:type" content="image/jpeg">
<meta property="og:image:width" content="{{ config('seo.image_width') }}">
<meta property="og:image:height" content="{{ config('seo.image_height') }}">
<meta property="og:image:alt" content="{{ $metadata['image_alt'] }}">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $metadata['title'] }}">
<meta name="twitter:description" content="{{ $metadata['description'] }}">
<meta name="twitter:image" content="{{ $metadata['image'] }}">
<meta name="twitter:image:alt" content="{{ $metadata['image_alt'] }}">
@if($seoPost?->published_at)
    <meta property="article:published_time" content="{{ $seoPost->published_at->toIso8601String() }}">
@endif
@if($seoPost?->updated_at)
    <meta property="article:modified_time" content="{{ $seoPost->updated_at->toIso8601String() }}">
@endif
@if(config('seo.google_verification'))
    <meta name="google-site-verification" content="{{ config('seo.google_verification') }}">
@endif
@if(config('seo.bing_verification'))
    <meta name="msvalidate.01" content="{{ config('seo.bing_verification') }}">
@endif
<script type="application/ld+json">{!! json_encode($structuredData, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR) !!}</script>
