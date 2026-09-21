<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\Faq;
use App\Models\HomeSection;
use App\Models\NewsPost;
use App\Models\Page;
use App\Models\Program;
use App\Models\Session;
use App\Models\Setting;
use App\Models\Slide;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SeedInvestmentSummitSeeder extends Seeder
{
    public const EVENT_SLUG = 'inaugural-seed-investment-summit';

    public const EVENT_PATH = '/events/inaugural-seed-investment-summit';

    public const REGISTRATION_PATH = '/events/inaugural-seed-investment-summit/register';

    public const IMAGE_PATH = '/images/seed-investment-summit/seed-investment-summit-2026.jpeg';

    public const FLYER_SHA256 = '1a94a28d5834d6f0669e7592ff7547020d3129ed075807e8f0cc20d827cc7393';

    public function run(): void
    {
        self::assertRequiredAssetsExist();

        DB::transaction(function (): void {
            $this->retireLegacyContent();
            $this->seedPlatformSettings();
            $this->seedAboutPage();
            $this->seedHomeSections();

            Event::query()
                ->where('slug', '!=', self::EVENT_SLUG)
                ->where('is_featured', true)
                ->update(['is_featured' => false]);

            $event = Event::query()->updateOrCreate(
                ['slug' => self::EVENT_SLUG],
                [
                    'title' => $this->t(
                        'Inaugural Seed Investment Summit',
                        'Sommet inaugural sur l’investissement semencier',
                        'القمة الافتتاحية للاستثمار في البذور',
                        'Cimeira Inaugural de Investimento em Sementes',
                        'Cumbre inaugural de inversión en semillas',
                        'Mkutano wa Kwanza wa Uwekezaji katika Mbegu',
                    ),
                    'excerpt' => $this->t(
                        'Resilient Seed Systems for a Food Secure Africa',
                        'Des systèmes semenciers résilients pour une Afrique en sécurité alimentaire',
                        'نظم بذور قادرة على الصمود من أجل أفريقيا آمنة غذائياً',
                        'Sistemas de sementes resilientes para uma África com segurança alimentar',
                        'Sistemas de semillas resilientes para una África con seguridad alimentaria',
                        'Mifumo thabiti ya mbegu kwa Afrika yenye uhakika wa chakula',
                    ),
                    'body' => $this->t(
                        $this->englishConceptNoteSummary(),
                        'La Commission de l’Union africaine convoque le Sommet inaugural sur l’investissement semencier du 5 au 7 octobre 2026 au Palazzo Convention Centre, à Ezulwini, en Eswatini, sous le thème « Des systèmes semenciers résilients pour une Afrique en sécurité alimentaire ».',
                        'تعقد مفوضية الاتحاد الأفريقي القمة الافتتاحية للاستثمار في البذور في الفترة من 5 إلى 7 أكتوبر 2026 في مركز بالاتسو للمؤتمرات في إزولويني، إسواتيني، تحت شعار «نظم بذور قادرة على الصمود من أجل أفريقيا آمنة غذائياً».',
                        'A Comissão da União Africana convoca a Cimeira Inaugural de Investimento em Sementes de 5 a 7 de outubro de 2026 no Palazzo Convention Centre, em Ezulwini, Eswatini, sob o tema «Sistemas de sementes resilientes para uma África com segurança alimentar».',
                        'La Comisión de la Unión Africana convoca la Cumbre inaugural de inversión en semillas del 5 al 7 de octubre de 2026 en el Palazzo Convention Centre, en Ezulwini, Eswatini, bajo el lema «Sistemas de semillas resilientes para una África con seguridad alimentaria».',
                        'Tume ya Umoja wa Afrika inaitisha Mkutano wa Kwanza wa Uwekezaji katika Mbegu tarehe 5–7 Oktoba 2026 katika Palazzo Convention Centre, Ezulwini, Eswatini, chini ya kaulimbiu “Mifumo thabiti ya mbegu kwa Afrika yenye uhakika wa chakula.”',
                    ),
                    'venue' => $this->t('Palazzo Convention Centre, Ezulwini, Eswatini'),
                    'start_at' => '2026-10-05 00:00:00',
                    'end_at' => '2026-10-07 23:59:59',
                    'mode' => 'in-person',
                    'registration_url' => self::REGISTRATION_PATH,
                    'image' => self::IMAGE_PATH,
                    'is_featured' => true,
                    'is_published' => true,
                ],
            );

            $this->seedHomepageSlide();
            $this->seedProgramme($event);
        });
    }

    public static function assertRequiredAssetsExist(): void
    {
        if (! is_file(public_path(ltrim(self::IMAGE_PATH, '/')))) {
            throw new RuntimeException('Required Seed Investment Summit flyer is missing: '.self::IMAGE_PATH);
        }
    }

    private function retireLegacyContent(): void
    {
        Event::query()->whereIn('slug', [
            'pan-african-leadership-summit',
            'women-in-public-leadership-forum',
            'continental-digital-trade-lab',
            'youth-climate-innovation-assembly',
            'regional-peacebuilders-roundtable',
        ])->update(['is_published' => false, 'is_featured' => false]);

        Program::query()->whereIn('slug', [
            'leadership-governance',
            'peace-resilience',
            'trade-innovation',
            'youth-climate',
            'fsrp-digital-advisory-early-warning',
            'fsrp-productive-base-resilience',
            'fsrp-regional-food-markets',
            'fsrp-food-crisis-preparedness',
        ])->update(['is_published' => false]);

        NewsPost::query()->whereIn('slug', [
            'registration-opens-for-leadership-summit',
            'first-speakers-announced-for-2026-programme',
            'continental-knowledge-library-launches',
            'addis-ababa-host-city-guide',
            '22nd-caadp-partnership-platform-participant-information',
        ])->update(['is_published' => false, 'is_featured' => false]);

        Faq::query()->whereIn('question->en', [
            'How do I register for an event?',
            'Is there a fee to participate?',
            'Which languages are available?',
            'Can I join sessions online?',
            'Are venues accessible?',
            'Do you provide visa or travel support?',
            'Will session recordings be available?',
            'Can I receive a certificate of participation?',
            'What is FSRP Events?',
            'How do I register for the 22nd CAADP Partnership Platform?',
            'Where is the event taking place?',
            'Which interpretation languages are planned?',
            'Where can I find the programme?',
            'Who should I contact about travel and participation?',
            'Who convenes the 22nd CAADP Partnership Platform?',
            'When and where is the CAADP partner event?',
            'Where can I download the CAADP programme?',
            'What interpretation is listed for CAADP?',
            'How do I confirm participation and travel arrangements?',
        ])->update(['is_published' => false]);
    }

    private function seedPlatformSettings(): void
    {
        $settings = [
            ['key' => 'site_name', 'value' => $this->t('African Union Events'), 'group' => 'general'],
            ['key' => 'tagline', 'value' => $this->t('African Union events, programmes and partnerships across Africa'), 'group' => 'general'],
            ['key' => 'footer_blurb', 'value' => $this->t('Discover current and previous African Union events, programmes, speakers and official resources.'), 'group' => 'footer'],
            ['key' => 'copyright', 'value' => $this->t('African Union Events'), 'group' => 'footer'],
            ['key' => 'logo', 'value' => ['value' => '/images/brand/african-union-logo.png'], 'group' => 'general'],
        ];

        foreach ($settings as $setting) {
            Setting::query()->updateOrCreate(['key' => $setting['key']], $setting);
        }

        $contactEmail = Setting::query()->firstOrNew(['key' => 'contact_email']);
        $currentEmail = strtolower(trim((string) ($contactEmail->value['value'] ?? '')));

        if (! $contactEmail->exists || in_array($currentEmail, [
            'fsrpinfo@africanunion.org',
            'hello@continentalforum.africa',
        ], true)) {
            $contactEmail->fill([
                'value' => ['value' => ''],
                'group' => 'contact',
            ])->save();
        }
    }

    private function seedAboutPage(): void
    {
        Page::query()->updateOrCreate(
            ['key' => 'about'],
            [
                'eyebrow' => $this->t('About African Union Events'),
                'title' => $this->t('A shared home for African Union events'),
                'body' => $this->t('Discover current and previous African Union convenings, their programmes, speakers and official event resources in one place.'),
                'image' => self::IMAGE_PATH,
                'is_published' => true,
            ],
        );
    }

    private function seedHomeSections(): void
    {
        $sections = [
            ['key' => 'hero', 'label' => 'Current event', 'is_active' => true, 'sort_order' => 1],
            ['key' => 'events', 'label' => 'Featured event', 'is_active' => true, 'sort_order' => 2],
            ['key' => 'sessions', 'label' => 'Event programme', 'is_active' => true, 'sort_order' => 3],
            ['key' => 'speakers', 'label' => 'Featured speakers', 'is_active' => true, 'sort_order' => 4],
            ['key' => 'programs', 'label' => 'Programme themes', 'is_active' => false, 'sort_order' => 5],
            ['key' => 'resources', 'label' => 'Event resources', 'is_active' => true, 'sort_order' => 6],
            ['key' => 'media', 'label' => 'Event media', 'is_active' => false, 'sort_order' => 7],
            ['key' => 'news', 'label' => 'Event updates', 'is_active' => false, 'sort_order' => 8],
            ['key' => 'about', 'label' => 'About the platform', 'is_active' => true, 'sort_order' => 9],
            ['key' => 'faq', 'label' => 'Frequently asked questions', 'is_active' => false, 'sort_order' => 10],
            ['key' => 'cta', 'label' => 'Explore African Union events', 'is_active' => true, 'sort_order' => 11],
        ];

        foreach ($sections as $section) {
            HomeSection::query()->updateOrCreate(['key' => $section['key']], $section);
        }
    }

    private function seedHomepageSlide(): void
    {
        Slide::query()->where('is_active', true)->update(['is_active' => false]);

        Slide::query()->updateOrCreate(
            ['button_url' => self::EVENT_PATH],
            [
                'eyebrow' => $this->t('African Union Inaugural Africa Seed Summit'),
                'title' => $this->t(
                    'Inaugural Seed Investment Summit',
                    'Sommet inaugural sur l’investissement semencier',
                    'القمة الافتتاحية للاستثمار في البذور',
                    'Cimeira Inaugural de Investimento em Sementes',
                    'Cumbre inaugural de inversión en semillas',
                    'Mkutano wa Kwanza wa Uwekezaji katika Mbegu',
                ),
                'subtitle' => $this->t(
                    'Resilient Seed Systems for a Food Secure Africa',
                    'Des systèmes semenciers résilients pour une Afrique en sécurité alimentaire',
                    'نظم بذور قادرة على الصمود من أجل أفريقيا آمنة غذائياً',
                    'Sistemas de sementes resilientes para uma África com segurança alimentar',
                    'Sistemas de semillas resilientes para una África con seguridad alimentaria',
                    'Mifumo thabiti ya mbegu kwa Afrika yenye uhakika wa chakula',
                ),
                'button_text' => $this->t(
                    'View summit details',
                    'Voir les détails du sommet',
                    'عرض تفاصيل القمة',
                    'Ver detalhes da cimeira',
                    'Ver detalles de la cumbre',
                    'Tazama maelezo ya mkutano',
                ),
                'image' => self::IMAGE_PATH,
                'video_url' => null,
                'is_active' => true,
                'sort_order' => 1,
            ],
        );
    }

    private function seedProgramme(Event $event): void
    {
        $programme = [
            [
                'date' => '2026-10-05',
                'title' => $this->t('Meeting of Senior Officials'),
                'summary' => $this->t('Senior officials convene on the opening day of the Summit.'),
            ],
            [
                'date' => '2026-10-06',
                'title' => $this->t('Meeting of the Ministers of Agriculture'),
                'summary' => $this->t('Ministers responsible for Agriculture meet on the second day.'),
            ],
            [
                'date' => '2026-10-07',
                'title' => $this->t('Summit for AU Heads of State and Government'),
                'summary' => $this->t('AU Heads of State and Government convene on the Summit’s final day.'),
            ],
        ];

        foreach ($programme as $index => $day) {
            Session::query()->updateOrCreate(
                [
                    'event_id' => $event->id,
                    'start_at' => $day['date'].' 00:00:00',
                ],
                [
                    'title' => $day['title'],
                    'summary' => $day['summary'],
                    'speaker_name' => $this->t(''),
                    'speaker_role' => $this->t(''),
                    'location' => $this->t('Palazzo Convention Centre, Ezulwini, Eswatini'),
                    'track' => $this->t('Summit programme'),
                    'end_at' => null,
                    'format' => 'in-person',
                    'sort_order' => $index + 1,
                    'is_published' => true,
                    'is_all_day' => true,
                ],
            );
        }
    }

    private function englishConceptNoteSummary(): string
    {
        return implode("\n\n", [
            'The African Union Commission is convening the Inaugural Seed Investment Summit from 5–7 October 2026 at the Palazzo Convention Centre in Ezulwini, Eswatini, under the theme “Resilient Seed Systems for a Food Secure Africa.”',
            'Reliable access to high-quality, appropriate seed is essential to agricultural productivity and food security. The Summit will advance the CAADP Kampala Declaration and Action Plan and the Africa Seed and Biotechnology Programme by bringing together political leadership, public institutions, farmers, researchers, the private sector and development agencies.',
            'The Summit will consider the Ezulwini Declaration on Africa’s seed systems, mobilise sustainable public and private investment, strengthen enabling policy and regulatory environments, and launch the Seed Sector Performance Index 2025 Status Report for Africa.',
        ]);
    }

    /**
     * @return array{en: string, fr: string, ar: string, pt: string, es: string, sw: string}
     */
    private function t(
        string $english,
        ?string $french = null,
        ?string $arabic = null,
        ?string $portuguese = null,
        ?string $spanish = null,
        ?string $swahili = null,
    ): array {
        return [
            'en' => $english,
            'fr' => $french ?? $english,
            'ar' => $arabic ?? $english,
            'pt' => $portuguese ?? $english,
            'es' => $spanish ?? $english,
            'sw' => $swahili ?? $english,
        ];
    }
}
