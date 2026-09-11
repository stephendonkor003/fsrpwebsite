@extends('admin.layouts.app')

@section('title', $definition['label'])

@section('content')
    @php
        $localizedValue = static function (mixed $value): string {
            if (is_string($value)) {
                $decoded = json_decode($value, true);
                $value = is_array($decoded) ? $decoded : $value;
            }

            if (is_array($value)) {
                return (string) ($value[app()->getLocale()] ?? $value['en'] ?? collect($value)->first() ?? '');
            }

            return (string) ($value ?? '');
        };
        $titleField = $definition['title_field'] ?? 'title';
    @endphp

    <div class="page-heading">
        <div>
            <p class="eyebrow">Content library</p>
            <h1>{{ $definition['label'] }}</h1>
            <p>{{ $definition['description'] }}</p>
        </div>
        <div class="page-heading-actions">
            <a class="button button--primary" href="{{ route('admin.content.create', $type) }}">
                @include('admin.partials.icon', ['name' => 'plus'])
                <span>Add {{ Illuminate\Support\Str::lower($definition['singular']) }}</span>
            </a>
        </div>
    </div>

    <section class="panel content-list-panel" aria-labelledby="content-list-heading">
        <div class="panel-heading panel-heading--list">
            <div>
                <h2 id="content-list-heading">All {{ Illuminate\Support\Str::lower($definition['label']) }}</h2>
                <p>{{ number_format($items->total()) }} {{ Illuminate\Support\Str::plural('record', $items->total()) }}</p>
            </div>
        </div>

        @if($items->isEmpty())
            <div class="empty-state">
                <span class="empty-state-icon">@include('admin.partials.icon', ['name' => $type])</span>
                <p class="eyebrow">Nothing here yet</p>
                <h3>Create your first {{ Illuminate\Support\Str::lower($definition['singular']) }}</h3>
                <p>{{ $definition['description'] }}</p>
                <a class="button button--primary" href="{{ route('admin.content.create', $type) }}">
                    @include('admin.partials.icon', ['name' => 'plus'])
                    <span>Add {{ Illuminate\Support\Str::lower($definition['singular']) }}</span>
                </a>
            </div>
        @else
            <div class="table-scroll">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th scope="col">{{ $definition['singular'] }}</th>
                            <th scope="col">Status</th>
                            <th scope="col">Last updated</th>
                            <th scope="col"><span class="sr-only">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($items as $item)
                            @php
                                $itemTitle = $localizedValue($item->getAttribute($titleField)) ?: 'Untitled '.$definition['singular'];
                                $attributes = $item->getAttributes();
                                $statusField = array_key_exists('is_published', $attributes) ? 'is_published' : (array_key_exists('is_active', $attributes) ? 'is_active' : null);
                                $isLive = $statusField ? (bool) $item->getAttribute($statusField) : true;
                                $statusLabel = $statusField === 'is_active' ? ($isLive ? 'Active' : 'Hidden') : ($isLive ? 'Published' : 'Draft');
                                $secondaryText = null;

                                if ($type === 'events' && $item->start_at) {
                                    $secondaryText = \Illuminate\Support\Carbon::parse($item->start_at)->format('d M Y · H:i');
                                } elseif ($type === 'sessions' && $item->start_at) {
                                    $secondaryText = \Illuminate\Support\Carbon::parse($item->start_at)->format('d M Y · H:i');
                                } elseif ($type === 'news' && $item->category) {
                                    $secondaryText = $localizedValue($item->category);
                                } elseif ($type === 'pages' && $item->key) {
                                    $secondaryText = 'Page key: '.$item->key;
                                } elseif ($type === 'resources') {
                                    $secondaryText = strtoupper($item->language).' / '.$item->original_filename;
                                    if ($item->event && ! $item->event->is_published) {
                                        $isLive = false;
                                        $statusLabel = 'Event is draft';
                                    }
                                }
                            @endphp
                            <tr>
                                <td data-label="{{ $definition['singular'] }}">
                                    <div class="table-primary-cell">
                                        @if($item->getAttribute('image'))
                                            <img class="table-thumbnail" src="{{ $item->getAttribute('image') }}" alt="" loading="lazy">
                                        @else
                                            <span class="table-thumbnail table-thumbnail--placeholder">@include('admin.partials.icon', ['name' => $type])</span>
                                        @endif
                                        <div>
                                            <a class="table-title-link" href="{{ route('admin.content.edit', [$type, $item]) }}">{{ $itemTitle }}</a>
                                            @if($secondaryText)
                                                <small>{{ $secondaryText }}</small>
                                            @elseif($type === 'sessions' && $item->relationLoaded('event') && $item->event)
                                                <small>{{ $localizedValue($item->event->title) }}</small>
                                            @else
                                                <small>ID #{{ $item->getKey() }}</small>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td data-label="Status">
                                    <span class="badge {{ $isLive ? 'badge--published' : 'badge--draft' }}">
                                        <span class="badge-dot" aria-hidden="true"></span>
                                        {{ $statusLabel }}
                                    </span>
                                </td>
                                <td data-label="Last updated">
                                    <time datetime="{{ $item->updated_at?->toAtomString() }}">{{ $item->updated_at?->diffForHumans() ?? 'Not available' }}</time>
                                </td>
                                <td class="table-actions" data-label="Actions">
                                    <a class="icon-button icon-button--outlined" href="{{ route('admin.content.edit', [$type, $item]) }}" aria-label="Edit {{ $itemTitle }}" title="Edit">
                                        @include('admin.partials.icon', ['name' => 'edit'])
                                    </a>
                                    <form
                                        method="POST"
                                        action="{{ route('admin.content.destroy', [$type, $item]) }}"
                                        data-confirm-delete="Delete ‘{{ $itemTitle }}’? This action cannot be undone."
                                    >
                                        @csrf
                                        @method('DELETE')
                                        <button class="icon-button icon-button--danger" type="submit" aria-label="Delete {{ $itemTitle }}" title="Delete">
                                            @include('admin.partials.icon', ['name' => 'trash'])
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($items->hasPages())
                <div class="pagination-wrap">
                    {{ $items->onEachSide(1)->links() }}
                </div>
            @endif
        @endif
    </section>
@endsection
