<article class="resource-card">
    <div class="resource-card-top"><span class="resource-icon">@include('site.partials.icon', ['name' => 'document'])</span><span class="resource-type">{{ strtoupper(pathinfo($resource->original_filename, PATHINFO_EXTENSION)) }}</span></div>
    <span class="resource-category">{{ __('portal.category.'.$resource->category) }}</span>
    <h3>{{ $resource->translate('title') }}</h3>
    @if($resource->translate('description'))<p>{{ $resource->translate('description') }}</p>@endif
    @if($resource->event)<a class="resource-event" href="{{ route('events.show', [$locale, $resource->event->slug]) }}">{{ $resource->event->translate('title') }}</a>@endif
    <div class="resource-meta"><span>{{ $locales[$resource->language]['native_name'] ?? strtoupper($resource->language) }}</span><span>{{ $resource->file_size >= 1048576 ? number_format($resource->file_size / 1048576, 1).' MB' : max(1, (int) ceil($resource->file_size / 1024)).' KB' }}</span></div>
    <a class="resource-download" href="{{ route('resources.download', [$locale, $resource->id]) }}" download="{{ $resource->original_filename }}">{{ __('portal.download') }} @include('site.partials.icon', ['name' => 'download'])</a>
</article>
