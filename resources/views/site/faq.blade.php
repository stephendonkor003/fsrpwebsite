@extends('layouts.site')

@section('title', __('ui.faq.title'))
@section('meta_description', __('ui.faq.subtitle'))

@section('content')
    @include('site.partials.page-hero', [
        'title' => __('ui.faq.title'),
        'eyebrow' => __('ui.faq.help_eyebrow'),
        'summary' => __('ui.faq.page_summary'),
        'heroImage' => asset('images/fsrp/field-implementation.jpeg'),
        'heroClass' => 'page-hero-faq',
    ])

    <section class="faq-search-band">
        <div class="container faq-search-inner">
            <form method="get" action="{{ route('faq', $locale) }}"><label><span class="sr-only">{{ __('ui.faq.search_placeholder') }}</span><svg aria-hidden="true" viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m16 16 5 5"/></svg><input type="search" name="q" value="{{ $search }}" placeholder="{{ __('ui.faq.search_placeholder') }}"></label><button class="button button-gold" type="submit">{{ __('ui.actions.search') }}</button></form>
        </div>
    </section>

    <section class="section faq-page-section">
        <div class="container faq-page-grid">
            <aside class="faq-sidebar"><p class="eyebrow"><span></span>{{ __('ui.faq.categories') }}</p><h2>{{ __('ui.faq.find_answer') }}</h2><p>{{ __('ui.faq.sidebar_text') }}</p><div class="faq-contact-card"><span>?</span><h3>{{ __('ui.faq.still_need_help') }}</h3><p>{{ __('ui.faq.contact_us') }}</p>@if(data_get($siteSettings, 'contact_email.value'))<a href="mailto:{{ data_get($siteSettings, 'contact_email.value') }}">{{ __('ui.footer.contact') }} <span aria-hidden="true">↗</span></a>@endif</div></aside>
            <div class="accordion-list faq-page-list">
                @forelse($faqs as $faq)
                    <details class="faq-item" @if($loop->first && !$search) open @endif>
                        <summary><span>{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span><span class="faq-summary-copy"><small>{{ $faq->translate('category') }}</small><strong>{{ $faq->translate('question') }}</strong></span><i aria-hidden="true"></i></summary>
                        <div class="faq-answer"><p>{{ $faq->translate('answer') }}</p></div>
                    </details>
                @empty
                    <div class="empty-state"><span>○</span><h2>{{ __('ui.empty.no_faqs_title') }}</h2><p>{{ __('ui.search.try_again') }}</p><a class="button button-dark" href="{{ route('faq', $locale) }}">{{ __('ui.filter.clear') }}</a></div>
                @endforelse
            </div>
        </div>
    </section>
@endsection
