<?php

namespace App;

use Locale;

class AfricaMap
{
    /** @return array<string, mixed> */
    public function data(string $locale): array
    {
        $map = json_decode(file_get_contents(public_path('assets/Africa/africa-countries.json')), true, 512, JSON_THROW_ON_ERROR);
        $colors = ['north' => '#c68a24', 'west' => '#2e8b69', 'central' => '#3688a0', 'east' => '#aa657c', 'south' => '#7466a3'];
        $regions = [];

        foreach ($colors as $key => $color) {
            $regions[$key] = ['name' => __('map.regions.'.$key, [], $locale), 'color' => $color, 'count' => 0];
        }

        foreach ($map['countries'] as &$country) {
            $name = class_exists(Locale::class) ? Locale::getDisplayRegion('und_'.$country['code'], $locale) : $country['name'];
            $country['label'] = $country['code'] === 'EH' ? __('map.country_names.EH', [], $locale) : $name;
            $country['color'] = $colors[$country['region']];
            $country['region_name'] = $regions[$country['region']]['name'];
            $regions[$country['region']]['count']++;
        }
        unset($country);

        $map['regions'] = $regions;

        return $map;
    }
}
