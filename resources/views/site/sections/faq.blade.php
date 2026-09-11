@if($faqs->isNotEmpty())
<section class="section portal-faq">
    <div class="container portal-faq-layout">
        <div><p class="eyebrow"><span></span>{{ __('portal.faq_eyebrow') }}</p><h2>{{ __('portal.faq_title') }}</h2><a class="text-link" href="{{ route('faq', $locale) }}">{{ __('ui.nav.faq') }} @include('site.partials.icon', ['name' => 'arrow'])</a></div>
        <div class="faq-list">
            @foreach($faqs as $faq)<details class="faq-item"><summary><span>{{ $faq->translate('question') }}</span><i aria-hidden="true"></i></summary><div class="faq-answer"><p>{{ $faq->translate('answer') }}</p></div></details>@endforeach
        </div>
    </div>
</section>
@endif
