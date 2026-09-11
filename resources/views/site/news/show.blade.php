@extends('layouts.site')

@section('title', $post->translate('title'))
@section('meta_description', $post->translate('excerpt'))

@section('content')
    <article>
        <header class="article-header">
            <div class="container article-header-inner">
                <nav class="breadcrumbs breadcrumbs-dark" aria-label="{{ __('ui.common.breadcrumbs') }}"><a href="{{ route('home', $locale) }}">{{ __('ui.nav.home') }}</a><svg aria-hidden="true" viewBox="0 0 24 24"><path d="m9 18 6-6-6-6"/></svg><a href="{{ route('news.index', $locale) }}">{{ __('ui.nav.news') }}</a></nav>
                <div class="card-meta"><span class="tag tag-green">{{ $post->translate('category') }}</span><time datetime="{{ $post->published_at?->toDateString() }}">{{ __('ui.news.published_on', ['date' => $post->published_at?->translatedFormat('d F Y')]) }}</time></div>
                <h1>{{ $post->translate('title') }}</h1>
                <p>{{ $post->translate('excerpt') }}</p>
                <div class="article-reading"><span>{{ __('seo.editorial_team') }}</span><i></i><span>{{ __('ui.news.minutes_read', ['count' => max(2, (int) ceil(str_word_count($post->translate('body')) / 220))]) }}</span></div>
            </div>
        </header>
        <div class="container article-image-wrap"><img src="{{ $post->image ?: asset('images/fsrp/field-implementation.jpeg') }}" alt=""></div>
        <div class="container article-layout">
            <aside class="article-share"><span>{{ __('ui.actions.share') }}</span><button type="button" data-copy-link data-success-label="{{ __('portal.copy_success') }}" data-prompt-label="{{ __('portal.copy_prompt') }}" aria-label="{{ __('ui.common.copy_link') }}"><svg aria-hidden="true" viewBox="0 0 24 24"><path d="M10 13a5 5 0 0 0 7.5.5l2-2a5 5 0 0 0-7-7l-1 1M14 11a5 5 0 0 0-7.5-.5l-2 2a5 5 0 0 0 7 7l1-1"/></svg></button></aside>
            <div class="prose article-prose">{!! nl2br(e($post->translate('body'))) !!}</div>
        </div>
    </article>

    @if($relatedPosts->isNotEmpty())
        <section class="section surface-cream">
            <div class="container"><div class="section-heading split-heading"><div><p class="eyebrow"><span></span>{{ __('ui.news.related') }}</p><h2>{{ __('ui.news.keep_reading') }}</h2></div><a class="text-link" href="{{ route('news.index', $locale) }}">{{ __('ui.common.visit_newsroom') }} <span aria-hidden="true">↗</span></a></div><div class="news-grid">@foreach($relatedPosts as $related)<article class="news-card"><a class="news-image" href="{{ route('news.show', [$locale, $related->slug]) }}"><img src="{{ $related->image ?: asset('images/fsrp/water-food-resilience-3.jpg') }}" alt="{{ $related->translate('title') }}" loading="lazy" decoding="async"></a><div class="news-content"><div class="card-meta"><span class="tag tag-sand">{{ $related->translate('category') }}</span><time>{{ $related->published_at?->translatedFormat('d M Y') }}</time></div><h3><a href="{{ route('news.show', [$locale, $related->slug]) }}">{{ $related->translate('title') }}</a></h3><a class="text-link text-link-small" href="{{ route('news.show', [$locale, $related->slug]) }}">{{ __('ui.actions.read_more') }} <span aria-hidden="true">↗</span></a></div></article>@endforeach</div></div>
        </section>
    @endif
@endsection
