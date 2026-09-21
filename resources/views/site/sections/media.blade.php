@php
    $videos = $slides->filter(fn ($slide) => filled($slide->video_url));
@endphp
@if($videos->isNotEmpty())
<section class="section portal-media" id="event-videos"><div class="container"><div class="section-heading split-heading heading-light"><div><p class="eyebrow eyebrow-light"><span></span>{{ __('portal.media_eyebrow') }}</p><h2>{{ __('portal.media_title') }}</h2></div><p>{{ __('portal.media_summary') }}</p></div><div class="video-grid">@foreach($videos as $video)<article class="video-card"><video controls playsinline preload="none" poster="{{ $video->image }}" aria-label="{{ __('portal.video', ['number' => $loop->iteration]) }}" data-feature-video src="{{ $video->video_url }}"><a href="{{ $video->video_url }}">{{ __('portal.watch') }}</a></video><div><span class="video-number">0{{ $loop->iteration }}</span><h3>{{ $video->translate('title') }}</h3></div></article>@endforeach</div><p class="media-credit">{{ __('portal.photo_credit') }}</p></div></section>
@endif
