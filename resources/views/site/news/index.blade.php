@extends('layouts.site')

@section('title', __('ui.news.title'))
@section('meta_description', __('ui.news.subtitle'))

@section('content')
    @include('site.partials.page-hero', [
        'title' => __('ui.news.title'),
        'eyebrow' => __('ui.news.newsroom_eyebrow'),
        'summary' => __('ui.news.page_summary'),
        'heroImage' => asset('images/fsrp/water-food-resilience-3.jpg'),
    ])

    <section class="listing-toolbar-wrap simple-toolbar-wrap">
        <div class="container">
            <form class="listing-toolbar simple-toolbar" method="get" action="{{ route('news.index', $locale) }}">
                <label class="search-field"><span class="sr-only">{{ __('ui.search.title') }}</span><svg aria-hidden="true" viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m16 16 5 5"/></svg><input type="search" name="q" value="{{ $search }}" placeholder="{{ __('ui.news.search_placeholder') }}"></label>
                <button class="button button-dark" type="submit">{{ __('ui.actions.search') }}</button>
                @if($search)<a href="{{ route('news.index', $locale) }}">{{ __('ui.filter.clear') }}</a>@endif
            </form>
        </div>
    </section>

    <section class="section listing-section">
        <div class="container">
            <div class="listing-intro"><div><p class="eyebrow"><span></span>{{ __('ui.news.latest') }}</p><h2>{{ $search ? __('ui.search.results_for', ['query' => $search]) : __('ui.news.stories_title') }}</h2></div><p>{{ __('ui.pagination.showing', ['from' => $posts->firstItem() ?? 0, 'to' => $posts->lastItem() ?? 0, 'total' => $posts->total()]) }}</p></div>
            <div class="listing-grid news-listing-grid">
                @forelse($posts as $post)
                    <article class="listing-card news-list-card">
                        <a class="listing-image" href="{{ route('news.show', [$locale, $post->slug]) }}"><img src="{{ $post->image ?: asset(['images/fsrp/field-implementation.jpeg','images/fsrp/water-food-resilience-3.jpg','images/fsrp/field-implementation.jpeg'][$loop->index % 3]) }}" alt="{{ $post->translate('title') }}" loading="lazy" decoding="async">@if($post->is_featured)<span class="featured-label">{{ __('ui.news.featured') }}</span>@endif</a>
                        <div class="listing-content"><div class="card-meta"><span class="tag tag-sand">{{ $post->translate('category') }}</span><time datetime="{{ $post->published_at?->toDateString() }}">{{ $post->published_at?->translatedFormat('d M Y') }}</time></div><h3><a href="{{ route('news.show', [$locale, $post->slug]) }}">{{ $post->translate('title') }}</a></h3><p>{{ $post->translate('excerpt') }}</p><a class="text-link text-link-small" href="{{ route('news.show', [$locale, $post->slug]) }}">{{ __('ui.actions.read_more') }} <span aria-hidden="true">↗</span></a></div>
                    </article>
                @empty
                    <div class="empty-state listing-empty"><span>○</span><h2>{{ __('ui.empty.no_news_title') }}</h2><p>{{ __('ui.empty.no_news_text') }}</p><a class="button button-dark" href="{{ route('news.index', $locale) }}">{{ __('ui.filter.clear') }}</a></div>
                @endforelse
            </div>
            <div class="pagination-wrap">{{ $posts->links() }}</div>
        </div>
    </section>
@endsection
