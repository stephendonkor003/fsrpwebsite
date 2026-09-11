<div id="africa-coverage" class="africa-coverage" data-africa-map>
    <div class="coverage-heading">
        <div><p class="eyebrow"><span></span>{{ __('map.eyebrow') }}</p><h2>{{ __('map.title') }}</h2><p>{{ __('map.summary') }}</p></div>
        <div class="coverage-total"><strong>{{ count($africaMap['countries']) }}</strong><span>{{ trim(__('map.countries_count', ['count' => ''])) }}</span><small>{{ __('map.regions_count', ['count' => count($africaMap['regions'])]) }}</small></div>
    </div>
    <div class="africa-map-layout">
        <figure class="africa-map-figure">
            <svg class="africa-map-svg" viewBox="{{ $africaMap['viewBox'] }}" role="img" aria-label="{{ __('map.map_label') }}">
                <title>{{ __('map.map_label') }}</title>
                <desc>{{ __('map.coverage') }}. {{ __('map.select_hint') }}</desc>
                @foreach($africaMap['countries'] as $country)
                    <g class="africa-country" data-map-country="{{ $country['code'] }}" data-region="{{ $country['region'] }}" data-name="{{ $country['label'] }}" data-region-name="{{ $country['region_name'] }}" style="--region-color: {{ $country['color'] }}">
                        <title>{{ $country['label'] }} — {{ $country['region_name'] }}</title>
                        <path d="{{ $country['path'] }}" fill-rule="evenodd"></path>
                        @if(in_array($country['code'], ['CV', 'ST', 'KM', 'SC', 'MU'], true))
                            <circle class="island-marker" cx="{{ $country['cx'] }}" cy="{{ $country['cy'] }}" r="6"></circle>
                            <text class="island-code" x="{{ $country['cx'] + 10 }}" y="{{ $country['cy'] + 4 }}" direction="ltr">{{ $country['code'] }}</text>
                        @endif
                    </g>
                @endforeach
            </svg>
            <figcaption>{{ __('map.source_label') }} <a href="https://au.int/en/member_states/countryprofiles2" target="_blank" rel="noopener">{{ __('map.source_link_label') }} ↗</a></figcaption>
        </figure>
        <div class="africa-map-panel">
            <p class="coverage-statement">@include('site.partials.icon', ['name' => 'check']){{ __('map.coverage') }}</p>
            <div class="map-region-controls" role="group" aria-label="{{ __('map.region_label') }}">
                <button type="button" class="map-region-button active" data-region-filter="all" aria-pressed="true"><span class="map-region-dot all-regions-dot"></span><span>{{ __('map.all_regions') }}</span><strong>{{ count($africaMap['countries']) }}</strong></button>
                @foreach($africaMap['regions'] as $key => $region)
                    <button type="button" class="map-region-button" data-region-filter="{{ $key }}" data-summary="{{ __('map.country_count', ['count' => $region['count']]) }}" aria-pressed="false" style="--region-color: {{ $region['color'] }}"><span class="map-region-dot"></span><span>{{ $region['name'] }}</span><strong>{{ $region['count'] }}</strong></button>
                @endforeach
            </div>
            <label class="map-country-picker"><span>{{ __('map.country_label') }}</span><select data-country-picker><option value="">{{ __('map.all_regions') }}</option>@foreach($africaMap['countries'] as $country)<option value="{{ $country['code'] }}" data-region="{{ $country['region'] }}">{{ $country['label'] }}</option>@endforeach</select></label>
            <div class="map-selection" aria-live="polite" aria-atomic="true"><span data-selection-region>{{ __('map.regions_count', ['count' => count($africaMap['regions'])]) }}</span><strong data-selection-name>{{ __('map.all_regions') }}</strong><p data-selection-detail>{{ __('map.select_hint') }}</p></div>
        </div>
    </div>
    <details class="map-country-directory"><summary>{{ __('map.country_list') }} <span>{{ count($africaMap['countries']) }}</span></summary><div class="map-country-groups">@foreach($africaMap['regions'] as $key => $region)<div><h3><span style="--region-color: {{ $region['color'] }}" class="map-region-dot"></span>{{ $region['name'] }}</h3><ul>@foreach($africaMap['countries'] as $country)@if($country['region'] === $key)<li>{{ $country['label'] }}</li>@endif @endforeach</ul></div>@endforeach</div></details>
</div>
