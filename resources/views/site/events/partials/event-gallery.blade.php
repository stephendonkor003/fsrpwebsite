@php
    $galleryItems = collect($galleryDays)->flatMap(fn (array $galleryDay) => $galleryDay['items']);
    $galleryImageCount = $galleryItems->where('type', 'image')->count();
    $galleryVideoCount = $galleryItems->where('type', 'video')->count();
    $galleryPhotoLabel = trans_choice('portal.gallery_photos', $galleryImageCount, ['count' => number_format($galleryImageCount)]);
    $galleryVideoLabel = trans_choice('portal.gallery_videos', $galleryVideoCount, ['count' => number_format($galleryVideoCount)]);
    $galleryTotalLabel = match (true) {
        $galleryVideoCount === 0 => $galleryPhotoLabel,
        $galleryImageCount === 0 => $galleryVideoLabel,
        default => __('portal.gallery_total', [
            'photos' => $galleryPhotoLabel,
            'videos' => $galleryVideoLabel,
        ]),
    };
@endphp

<section class="section event-gallery-section" id="event-gallery" data-event-gallery>
    <div class="container">
        <div class="section-heading split-heading event-gallery-heading">
            <div>
                <p class="eyebrow"><span></span>{{ __('portal.gallery_eyebrow') }}</p>
                <h2>{{ __('portal.gallery_title') }}</h2>
            </div>
            <div class="event-gallery-intro">
                <p>{{ __('portal.gallery_summary', ['event' => $event->translate('title')]) }}</p>
                <span>{{ $galleryTotalLabel }}</span>
            </div>
        </div>

        <nav class="event-gallery-day-links" aria-label="{{ __('portal.gallery_jump') }}">
            @foreach($galleryDays as $galleryDay)
                <a href="#event-gallery-day-{{ $galleryDay['day'] }}">
                    <span>{{ __('portal.gallery_day', ['number' => $galleryDay['day']]) }}</span>
                    <strong>{{ $galleryDay['count'] }}</strong>
                </a>
            @endforeach
        </nav>

        <div class="event-gallery-days">
            @foreach($galleryDays as $galleryDay)
                @php
                    $galleryDate = \Illuminate\Support\Carbon::parse($galleryDay['date'])->locale($locale);
                @endphp
                <details class="event-gallery-day" id="event-gallery-day-{{ $galleryDay['day'] }}" data-gallery-day @if($loop->last) open @endif>
                    <summary>
                        <span class="event-gallery-day-number">{{ str_pad((string) $galleryDay['day'], 2, '0', STR_PAD_LEFT) }}</span>
                        <span class="event-gallery-day-title">
                            <strong>{{ __('portal.gallery_day', ['number' => $galleryDay['day']]) }}</strong>
                            <time datetime="{{ $galleryDay['date'] }}">{{ $galleryDate->translatedFormat('l, d F Y') }}</time>
                        </span>
                        <span class="event-gallery-day-count">{{ trans_choice('portal.gallery_items', $galleryDay['count'], ['count' => number_format($galleryDay['count'])]) }}</span>
                        <span class="event-gallery-day-toggle" aria-hidden="true">+</span>
                    </summary>

                    <div class="event-gallery-grid" data-gallery-items>
                        @foreach($galleryDay['items'] as $galleryItem)
                            @php
                                $isImage = $galleryItem['type'] === 'image';
                                $itemName = __($isImage ? 'portal.gallery_photo' : 'portal.gallery_video', ['number' => $galleryItem['sequence']]);
                                $itemDescription = __('portal.gallery_item_description', [
                                    'day' => $galleryDay['day'],
                                    'item' => $itemName,
                                ]);
                                $mediaUrl = asset(ltrim($galleryItem['url'], '/'));
                            @endphp
                            <a
                                class="event-gallery-card"
                                href="{{ $mediaUrl }}"
                                data-gallery-item
                                data-gallery-type="{{ $galleryItem['type'] }}"
                                data-gallery-src="{{ $mediaUrl }}"
                                data-gallery-caption="{{ $itemDescription }}"
                                aria-label="{{ __('portal.gallery_open_item', ['item' => $itemDescription]) }}"
                            >
                                @if($isImage)
                                    <img src="{{ $mediaUrl }}" alt="{{ $itemDescription }}" loading="lazy" decoding="async">
                                @else
                                    <video muted playsinline preload="none" data-gallery-preview-video data-src="{{ $mediaUrl }}" aria-hidden="true"></video>
                                @endif
                                <span class="event-gallery-card-overlay">
                                    <span class="event-gallery-card-type">
                                        @include('site.partials.icon', ['name' => $isImage ? 'image' : 'play'])
                                        {{ $isImage ? __('portal.gallery_photo_label') : __('portal.gallery_video_label') }}
                                    </span>
                                    <strong>{{ str_pad((string) $galleryItem['sequence'], 2, '0', STR_PAD_LEFT) }}</strong>
                                </span>
                            </a>
                        @endforeach
                    </div>
                </details>
            @endforeach
        </div>
    </div>

    <dialog class="event-gallery-dialog" data-gallery-dialog aria-label="{{ __('portal.gallery_viewer') }}">
        <div class="event-gallery-dialog-panel">
            <button class="event-gallery-dialog-close" type="button" data-gallery-close aria-label="{{ __('portal.gallery_close') }}">&times;</button>
            <button class="event-gallery-dialog-nav event-gallery-dialog-prev" type="button" data-gallery-prev aria-label="{{ __('portal.gallery_previous') }}">&#8592;</button>
            <figure>
                <div class="event-gallery-dialog-stage">
                    <img alt="" data-gallery-dialog-image hidden>
                    <video controls playsinline preload="metadata" data-gallery-dialog-video hidden></video>
                </div>
                <figcaption aria-live="polite" aria-atomic="true">
                    <span data-gallery-dialog-caption></span>
                    <strong data-gallery-dialog-counter></strong>
                </figcaption>
            </figure>
            <button class="event-gallery-dialog-nav event-gallery-dialog-next" type="button" data-gallery-next aria-label="{{ __('portal.gallery_next') }}">&#8594;</button>
        </div>
    </dialog>
</section>
