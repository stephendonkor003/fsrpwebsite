<?php

namespace Tests\Feature;

use App\AfricaMap;
use App\Models\HomeSection;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class AfricaMapTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_all_55_au_members_have_distinct_geometry_and_the_correct_regional_membership(): void
    {
        $expectedRegions = [
            'north' => ['DZ', 'EG', 'LY', 'MR', 'MA', 'EH', 'TN'],
            'west' => ['BJ', 'BF', 'CV', 'CI', 'GM', 'GH', 'GN', 'GW', 'LR', 'ML', 'NE', 'NG', 'SN', 'SL', 'TG'],
            'central' => ['BI', 'CM', 'CF', 'TD', 'CG', 'CD', 'GQ', 'GA', 'ST'],
            'east' => ['KM', 'DJ', 'ER', 'ET', 'KE', 'MG', 'MU', 'RW', 'SC', 'SO', 'SS', 'SD', 'TZ', 'UG'],
            'south' => ['AO', 'BW', 'SZ', 'LS', 'MW', 'MZ', 'NA', 'ZA', 'ZM', 'ZW'],
        ];

        $map = app(AfricaMap::class)->data('en');

        $this->assertCount(55, $map['countries']);
        $this->assertCount(55, array_unique(array_column($map['countries'], 'code')));
        $this->assertCount(55, array_unique(array_column($map['countries'], 'path')));
        $this->assertSame(array_keys($expectedRegions), array_keys($map['regions']));
        $this->assertCount(5, array_unique(array_column($map['regions'], 'color')));

        foreach ($expectedRegions as $region => $expectedCodes) {
            $actualCodes = array_column(array_filter($map['countries'], fn (array $country): bool => $country['region'] === $region), 'code');
            sort($expectedCodes);
            sort($actualCodes);

            $this->assertSame($expectedCodes, $actualCodes, $region);
            $this->assertSame(count($expectedCodes), $map['regions'][$region]['count'], $region);
        }

        foreach ($map['countries'] as $country) {
            $this->assertStringStartsWith('M', $country['path'], $country['code']);
            $this->assertStringEndsWith('Z', $country['path'], $country['code']);
            $this->assertSame($map['regions'][$country['region']]['color'], $country['color'], $country['code']);
        }
    }

    public function test_the_server_rendered_map_contains_every_country_and_a_readable_country_directory(): void
    {
        $document = $this->document((string) $this->view('site.partials.africa-map'));
        $countries = $document->query('//*[@data-africa-map]//*[@data-map-country]');
        $renderedCodes = [];

        foreach ($countries as $country) {
            $renderedCodes[] = $country->getAttribute('data-map-country');
            $this->assertSame(1, $document->query('./path[string-length(@d) > 0]', $country)->length);
        }

        $this->assertSame(55, $countries->length);
        $this->assertCount(55, array_unique($renderedCodes));
        $this->assertSame(55, $document->query('//*[@data-country-picker]/option[@value != ""]')->length);
        $this->assertSame(55, $document->query('//details//li')->length);
        $this->assertSame(6, $document->query('//*[@data-region-filter]')->length);
        $this->assertSame(5, $document->query('//*[contains(@class, "island-marker")]')->length);
        $this->assertSame('img', $document->evaluate('string(//svg/@role)'));
        $this->assertSame('https://au.int/en/member_states/countryprofiles2', $document->evaluate('string(//figcaption/a/@href)'));
    }

    #[TestWith(['en'])]
    #[TestWith(['fr'])]
    #[TestWith(['ar'])]
    #[TestWith(['pt'])]
    #[TestWith(['es'])]
    #[TestWith(['sw'])]
    public function test_map_labels_and_continent_wide_participation_are_available_in_each_language(string $locale): void
    {
        app()->setLocale($locale);
        $coverage = __('map.coverage');
        $countryName = __('map.country_names.EH');

        $view = $this->view('site.partials.africa-map');

        $view->assertSeeText($coverage)->assertSeeText($countryName)
            ->assertSeeText(__('map.country_list'))
            ->assertDontSee('map.coverage')->assertDontSee('map.country_names.EH');
        $document = $this->document((string) $view);
        $this->assertSame($countryName, $document->evaluate('string(//*[@data-map-country="EH"]/@data-name)'));
        $this->assertSame(__('map.map_label'), $document->evaluate('string(//svg/@aria-label)'));
    }

    #[TestWith(['/en'])]
    #[TestWith(['/en/about'])]
    public function test_the_home_and_about_pages_include_the_map_and_all_africa_coverage(string $path): void
    {
        HomeSection::create(['key' => 'about', 'label' => 'About African Union Events', 'is_active' => true, 'sort_order' => 1]);

        $response = $this->get($path)->assertOk()
            ->assertSeeText('Open to participants across all African countries');

        $document = $this->document($response->getContent());
        $this->assertSame(1, $document->query('//*[@data-africa-map]')->length);
        $this->assertSame(55, $document->query('//*[@data-africa-map]//*[@data-map-country]')->length);
    }

    private function document(string $html): DOMXPath
    {
        $previous = libxml_use_internal_errors(true);
        $document = new DOMDocument;
        $document->loadHTML('<?xml encoding="utf-8" ?>'.$html, LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        return new DOMXPath($document);
    }
}
