<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SpeakersPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_speakers_page_renders_the_twelve_unique_seed_summit_profiles(): void
    {
        $expectedProfiles = [
            ['source_order' => 1, 'name' => 'H.E Moses Vilakati', 'title' => 'Commissioner, Agriculture, Rural Development, Blue Economy & Sustainable Development', 'organisation' => 'African Union Commission', 'image' => 'images/seed-investment-summit/speakers/01-he-moses-vilakati.png'],
            ['source_order' => 2, 'name' => 'Hon. Russell Dlamini', 'title' => 'Prime Minister', 'organisation' => 'Kingdom of Eswatini', 'image' => 'images/seed-investment-summit/speakers/02-hon-russell-dlamini.png'],
            ['source_order' => 3, 'name' => 'Hon. John Dumelo', 'title' => 'Deputy Minister, Agriculture and Food Security', 'organisation' => 'Republic of Ghana', 'image' => 'images/seed-investment-summit/speakers/03-hon-john-dumelo.png'],
            ['source_order' => 4, 'name' => 'Hon. Dr. Michael Roberto Kenyi Leggi', 'title' => 'Undersecretary for Agriculture and Food Security', 'organisation' => 'Republic of South Sudan', 'image' => 'images/seed-investment-summit/speakers/04-hon-dr-michael-roberto-kenyi-leggi.png'],
            ['source_order' => 5, 'name' => 'Honourable Dr. Anxious Jongwe Masuka', 'title' => 'Minister of Lands, Agriculture, Fisheries, Water and Rural Development', 'organisation' => 'Republic of Zimbabwe', 'image' => 'images/seed-investment-summit/speakers/05-honourable-dr-anxious-jongwe-masuka.png'],
            ['source_order' => 6, 'name' => 'Hon. Zahra Ige.', 'title' => 'Deputy Minister of Agriculture and Irrigation', 'organisation' => 'Republic of Somalia', 'image' => 'images/seed-investment-summit/speakers/06-hon-zahra-ige.png'],
            ['source_order' => 7, 'name' => 'Hon. Dr Isata Kamanda', 'title' => 'Deputy Minister of Agriculture and Food Security', 'organisation' => 'Republic of Sierra Leone', 'image' => 'images/seed-investment-summit/speakers/07-hon-dr-isata-kamanda.png'],
            ['source_order' => 8, 'name' => 'J. Alexander Nuetah', 'title' => 'Minister, Ministry of Agriculture', 'organisation' => 'Republic of Liberia', 'image' => 'images/seed-investment-summit/speakers/08-j-alexander-nuetah.png'],
            ['source_order' => 9, 'name' => 'Abdellah Bah El Mad', 'title' => 'African Union Commission Ambassador', 'organisation' => 'Sahrawi Arab Democratic Republic (SADR)', 'image' => 'images/seed-investment-summit/speakers/09-abdellah-bah-el-mad.png'],
            ['source_order' => 10, 'name' => 'Dr. Edwin Gorataone Dikoloti', 'title' => 'Minister, of Lands and Agriculture', 'organisation' => 'Republic of Botswana', 'image' => 'images/seed-investment-summit/speakers/10-dr-edwin-gorataone-dikoloti.png'],
            ['source_order' => 11, 'name' => 'Hon. Prof. Emmanuel Mbetid-Bessane', 'title' => 'Minister of Agriculture and Rural Development', 'organisation' => 'Central African Republic (CAR)', 'image' => 'images/seed-investment-summit/speakers/11-hon-prof-emmanuel-mbetid-bessane.png'],
            ['source_order' => 13, 'name' => 'Wallace Jude Keith Cosgrow', 'title' => 'Minister, Fisheries, Agriculture & Blue Economy', 'organisation' => 'Republic of Seychelles', 'image' => 'images/seed-investment-summit/speakers/13-wallace-jude-keith-cosgrow.png'],
        ];

        $response = $this->get('/en/speakers');

        $response
            ->assertViewIs('site.speakers')
            ->assertSee('data-speaker-open', false)
            ->assertSee('class="speaker-modal summit-speaker-dialog"', false)
            ->assertSee('class="summit-speaker-dialog-toolbar"', false)
            ->assertDontSeeText('Nardos Bekele-Thomas')
            ->assertDontSeeText('Elias Mpedi Magosi')
            ->assertDontSeeText('Gabriel Mbairobe')
            ->assertDontSeeText('22nd CAADP Partnership Platform')
            ->assertDontSee('images/seed-investment-summit/speakers/12-wallace-jude-keith-cosgrow.png', false)
            ->assertSee(route('speakers', 'en'), false);

        $renderedProfiles = $response->viewData('speakers');

        $this->assertSame($expectedProfiles, $renderedProfiles);
        $this->assertSame(12, substr_count($response->getContent(), 'data-speaker-profile'));
        $this->assertSame(12, substr_count($response->getContent(), 'class="summit-speaker-dialog-toolbar"'));
        $this->assertSame(24, substr_count($response->getContent(), 'loading="lazy" decoding="async"'));

        foreach ($expectedProfiles as $expectedProfile) {
            $response
                ->assertSeeText($expectedProfile['name'])
                ->assertSeeText($expectedProfile['title'])
                ->assertSeeText($expectedProfile['organisation'])
                ->assertSee($expectedProfile['image'], false)
                ->assertSee('id="speaker-profile-'.$expectedProfile['source_order'].'"', false)
                ->assertSee('data-speaker-open="speaker-dialog-'.$expectedProfile['source_order'].'"', false)
                ->assertSee('aria-controls="speaker-dialog-'.$expectedProfile['source_order'].'"', false)
                ->assertSee('id="speaker-dialog-'.$expectedProfile['source_order'].'"', false)
                ->assertSee('aria-labelledby="speaker-name-'.$expectedProfile['source_order'].'"', false)
                ->assertSee('aria-describedby="speaker-role-'.$expectedProfile['source_order'].'"', false)
                ->assertSee('id="speaker-name-'.$expectedProfile['source_order'].'"', false)
                ->assertSee('id="speaker-role-'.$expectedProfile['source_order'].'"', false);

            $this->assertFileExists(public_path($expectedProfile['image']));
        }
    }

    public function test_homepage_speaker_teaser_renders_only_the_first_four_profiles(): void
    {
        app()->setLocale('fr');
        $speakers = config('seed_summit_speakers');

        $view = $this->view('site.sections.speakers', [
            'speakers' => $speakers,
            'locale' => 'fr',
        ]);
        $content = (string) $view;

        $view
            ->assertSeeText(__('portal.speakers_eyebrow'))
            ->assertSeeText($speakers[0]['name'])
            ->assertSeeText($speakers[1]['name'])
            ->assertSeeText($speakers[2]['name'])
            ->assertSeeText($speakers[3]['name'])
            ->assertDontSeeText($speakers[4]['name']);
        $this->assertSame(4, substr_count($content, 'class="summit-speaker-teaser-card"'));
    }
}
