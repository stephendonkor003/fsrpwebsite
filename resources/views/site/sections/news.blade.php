<section class="section section-news">
    <div class="container">
        <div class="section-heading split-heading">
            <div><p class="eyebrow"><span></span>{{ __('ui.home.news_eyebrow') }}</p><h2>{{ __('ui.home.news_title') }}</h2></div>
            <a class="text-link" href="{{ route('news.index', $locale) }}">{{ __('ui.common.visit_newsroom') }} <span aria-hidden="true">↗</span></a>
        </div>
        <div class="news-grid">
            @forelse($newsPosts as $post)
                <article class="news-card">
                    <a class="news-image" href="{{ route('news.show', [$locale, $post->slug]) }}">
                        <img src="{{ $post->image ?: asset('images/seed-investment-summit/seed-investment-summit-2026.jpeg') }}" alt="{{ $post->translate('title') }}" loading="lazy" decoding="async">
                    </a>
                    <div class="news-content">
                        <div class="card-meta"><span class="tag tag-sand">{{ $post->translate('category') }}</span><time datetime="{{ $post->published_at?->toDateString() }}">{{ $post->published_at?->translatedFormat('d M Y') }}</time></div>
                        <h3><a href="{{ route('news.show', [$locale, $post->slug]) }}">{{ $post->translate('title') }}</a></h3>
                        <p>{{ $post->translate('excerpt') }}</p>
                        <a class="text-link text-link-small" href="{{ route('news.show', [$locale, $post->slug]) }}">{{ __('ui.common.read_story') }} <span aria-hidden="true">↗</span></a>
                    </div>
                </article>
            @empty
                <div class="empty-state"><h3>{{ __('ui.empty.no_news_title') }}</h3><p>{{ __('ui.empty.no_news_text') }}</p></div>
            @endforelse
        </div>
    </div>
</section>
