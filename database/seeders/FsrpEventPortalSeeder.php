<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\EventResource;
use App\Models\Faq;
use App\Models\HomeSection;
use App\Models\NewsPost;
use App\Models\Page;
use App\Models\Program;
use App\Models\Session;
use App\Models\Setting;
use App\Models\Slide;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class FsrpEventPortalSeeder extends Seeder
{
    public const EVENT_SLUG = '22nd-caadp-partnership-platform';

    public const BRIEF_PATH = 'event-resources/22nd-caadp-pp-information-note.pdf';

    public const PROGRAMME_PATH = 'event-resources/22nd-caadp-pp-programme-overview.pdf';

    /**
     * Publish FSRP portal content and the partner event supplied in the CAADP brief.
     */
    public function run(): void
    {
        foreach ([self::BRIEF_PATH, self::PROGRAMME_PATH] as $path) {
            if (! Storage::disk('local')->exists($path)) {
                throw new RuntimeException('Required event document is missing: '.$path);
            }
        }

        DB::transaction(function (): void {
            $this->retireOriginalDemonstrationContent();
            $this->seedSettings();
            $this->seedSlides();
            $this->seedPrograms();
            $this->seedAboutPage();
            $this->seedFaqs();
            $this->seedHomeSections();
            $event = $this->seedPartnerEvent();
            $this->seedDailyOverview($event);
            $this->seedResources($event);
            $this->seedPartnerUpdate($event);
        });
    }

    private function retireOriginalDemonstrationContent(): void
    {
        $demoEvents = [
            'pan-african-leadership-summit' => 'Pan-African Leadership Summit',
            'women-in-public-leadership-forum' => 'Women in Public Leadership Forum',
            'continental-digital-trade-lab' => 'Continental Digital Trade Lab',
            'youth-climate-innovation-assembly' => 'Youth Climate Innovation Assembly',
            'regional-peacebuilders-roundtable' => 'Regional Peacebuilders Roundtable',
        ];
        $demoEventIds = [];

        foreach ($demoEvents as $slug => $title) {
            $query = Event::query()->where('slug', $slug)->where('title->en', $title);
            $demoEventIds = array_merge($demoEventIds, (clone $query)->pluck('id')->all());
            $query->update(['is_published' => false]);
        }

        Session::query()->whereIn('event_id', $demoEventIds)->whereIn('title->en', [
            'Opening plenary: Africaâ€™s next chapter',
            'Designing institutions people trust',
            'Financing implementation and partnerships',
            'From representation to influence',
            'Mentoring clinic: leading through transition',
            'Interoperable markets by design',
            'African startup solutions showcase',
            'Climate solutions studio',
        ])->update(['is_published' => false]);

        foreach ([
            'registration-opens-for-leadership-summit' => 'Registration opens for the Pan-African Leadership Summit',
            'first-speakers-announced-for-2026-programme' => 'First speakers announced for the 2026 programme',
            'continental-knowledge-library-launches' => 'Continental knowledge library launches',
            'addis-ababa-host-city-guide' => 'Your host-city guide to Addis Ababa',
        ] as $slug => $title) {
            NewsPost::query()->where('slug', $slug)->where('title->en', $title)->update(['is_published' => false]);
        }

        foreach ([
            'leadership-governance' => 'Leadership & Governance',
            'peace-resilience' => 'Peace & Resilience',
            'trade-innovation' => 'Trade & Innovation',
            'youth-climate' => 'Youth & Climate Action',
        ] as $slug => $title) {
            Program::query()->where('slug', $slug)->where('title->en', $title)->update(['is_published' => false]);
        }

        Slide::query()->whereIn('title->en', [
            'Ideas that move a continent forward',
            'Build partnerships that outlast the programme',
            'Turn every session into shared progress',
        ])->update(['is_active' => false]);

        Faq::query()->whereIn('question->en', [
            'How do I register for an event?',
            'Is there a fee to participate?',
            'Which languages are available?',
            'Can I join sessions online?',
            'Are venues accessible?',
            'Do you provide visa or travel support?',
            'Will session recordings be available?',
            'Can I receive a certificate of participation?',
        ])->update(['is_published' => false]);
    }

    private function seedSettings(): void
    {
        $settings = [
            'site_name' => $this->t('FSRP Events', 'Ã‰vÃ©nements FSRP', 'ÙØ¹Ø§Ù„ÙŠØ§Øª FSRP', 'Eventos FSRP', 'Eventos FSRP', 'Matukio ya FSRP'),
            'tagline' => $this->t('FSRP events and shared learning across Africa', 'Ã‰vÃ©nements FSRP et apprentissage partagÃ© Ã  travers lâ€™Afrique', 'ÙØ¹Ø§Ù„ÙŠØ§Øª FSRP ÙˆØ§Ù„ØªØ¹Ù„Ù‘Ù… Ø§Ù„Ù…Ø´ØªØ±Ùƒ ÙÙŠ Ø¬Ù…ÙŠØ¹ Ø£Ù†Ø­Ø§Ø¡ Ø¥ÙØ±ÙŠÙ‚ÙŠØ§', 'Eventos FSRP e aprendizagem partilhada em toda a Ãfrica', 'Eventos FSRP y aprendizaje compartido en toda Ãfrica', 'Matukio ya FSRP na kujifunza pamoja kote Afrika'),
            'footer_blurb' => $this->t('Connecting people, knowledge and partnerships for resilient food systems.', 'Relier les personnes, les connaissances et les partenariats pour des systÃ¨mes alimentaires rÃ©silients.', 'Ø±Ø¨Ø· Ø§Ù„Ø£Ø´Ø®Ø§Øµ ÙˆØ§Ù„Ù…Ø¹Ø§Ø±Ù ÙˆØ§Ù„Ø´Ø±Ø§ÙƒØ§Øª Ù…Ù† Ø£Ø¬Ù„ Ù†Ø¸Ù… ØºØ°Ø§Ø¦ÙŠØ© Ù‚Ø§Ø¯Ø±Ø© Ø¹Ù„Ù‰ Ø§Ù„ØµÙ…ÙˆØ¯.', 'Ligar pessoas, conhecimento e parcerias para sistemas alimentares resilientes.', 'Conectar personas, conocimientos y alianzas para sistemas alimentarios resilientes.', 'Kuunganisha watu, maarifa na ushirikiano kwa mifumo ya chakula yenye ustahimilivu.'),
            'copyright' => $this->t('FSRP Events Â· Food system resilience', 'Ã‰vÃ©nements FSRP Â· RÃ©silience des systÃ¨mes alimentaires', 'ÙØ¹Ø§Ù„ÙŠØ§Øª FSRP Â· ØµÙ…ÙˆØ¯ Ø§Ù„Ù†Ø¸Ù… Ø§Ù„ØºØ°Ø§Ø¦ÙŠØ©', 'Eventos FSRP Â· ResiliÃªncia dos sistemas alimentares', 'Eventos FSRP Â· Resiliencia de los sistemas alimentarios', 'Matukio ya FSRP Â· Ustahimilivu wa mifumo ya chakula'),
            'address' => $this->t(''),
        ];

        foreach ($settings as $key => $value) {
            Setting::query()->updateOrCreate(['key' => $key], [
                'value' => $value,
                'group' => in_array($key, ['footer_blurb', 'copyright'], true) ? 'footer' : 'general',
            ]);
        }

        foreach ([
            'logo' => ['/images/fsrp/african-union-logo.png', 'general'],
            'contact_email' => ['fsrpinfo@africanunion.org', 'contact'],
            'contact_phone' => ['', 'contact'],
            'facebook_url' => ['', 'social'],
            'linkedin_url' => ['', 'social'],
            'youtube_url' => ['', 'social'],
        ] as $key => [$value, $group]) {
            Setting::query()->updateOrCreate(['key' => $key], ['value' => ['value' => $value], 'group' => $group]);
        }
    }

    private function seedSlides(): void
    {
        $slides = [
            [
                'title' => $this->t('Welcome to the 22nd CAADP Partnership Platform'),
                'subtitle' => $this->t('A continental conference in Harare focused on implementation, coordination and accountability for stronger food systems.'),
                'image' => '/images/caadp/caadp-partnership-1.jpeg',
                'video_url' => '/videos/fsrp/fsrp-program-video.mp4',
                'button_url' => '/events',
                'button_text' => $this->t('Explore events'),
            ],
            [
                'title' => $this->t('Partnering for resilient food systems'),
                'subtitle' => $this->t('Session streams, knowledge exchanges and joint action plans built around shared commitments.'),
                'image' => '/images/caadp/caadp-partnership-2.jpeg',
                'video_url' => null,
                'button_url' => '/program-outline',
                'button_text' => $this->t('Explore programme themes'),
            ],
            [
                'title' => $this->t('Prepared together for action'),
                'subtitle' => $this->t('Explore practical guidance on implementation milestones at every stage of the CAADP journey.'),
                'image' => '/images/caadp/caadp-partnership-3.jpeg',
                'video_url' => null,
                'button_url' => '/program-outline',
                'button_text' => $this->t('Explore programme themes'),
            ],
            [
                'title' => $this->t('Cross-country collaboration and partnerships'),
                'subtitle' => $this->t('Learn from the region through shared sessions, tools and practical next steps.'),
                'image' => '/images/caadp/caadp-partnership-4.jpeg',
                'video_url' => null,
                'button_url' => '/events',
                'button_text' => $this->t('Explore events'),
            ],
        ];

        foreach ($slides as $index => $slide) {
            $this->saveTranslatedRecord(Slide::class, 'title', array_merge($slide, [
                'eyebrow' => $this->t('Food System Resilience Program'),
                'subtitle' => $slide['subtitle'],
                'is_active' => true,
                'sort_order' => $index + 1,
            ]));
        }
    }

    private function seedPrograms(): void
    {
        $programs = [
            [
                'slug' => 'fsrp-digital-advisory-early-warning',
                'title' => $this->t('Digital advisory & early warning', 'Conseil numÃ©rique et alerte prÃ©coce', 'Ø§Ù„Ø¥Ø±Ø´Ø§Ø¯ Ø§Ù„Ø±Ù‚Ù…ÙŠ ÙˆØ§Ù„Ø¥Ù†Ø°Ø§Ø± Ø§Ù„Ù…Ø¨ÙƒØ±', 'Aconselhamento digital e alerta precoce', 'Asesoramiento digital y alerta temprana', 'Ushauri wa kidijitali na tahadhari ya mapema'),
                'excerpt' => $this->t('Share knowledge on agricultural advice, climate information and preparedness for food security shocks.', 'Partager les connaissances sur le conseil agricole, lâ€™information climatique et la prÃ©paration aux chocs alimentaires.', 'ØªØ¨Ø§Ø¯Ù„ Ø§Ù„Ù…Ø¹Ø§Ø±Ù Ø­ÙˆÙ„ Ø§Ù„Ø¥Ø±Ø´Ø§Ø¯ Ø§Ù„Ø²Ø±Ø§Ø¹ÙŠ ÙˆØ§Ù„Ù…Ø¹Ù„ÙˆÙ…Ø§Øª Ø§Ù„Ù…Ù†Ø§Ø®ÙŠØ© ÙˆØ§Ù„Ø§Ø³ØªØ¹Ø¯Ø§Ø¯ Ù„ØµØ¯Ù…Ø§Øª Ø§Ù„Ø£Ù…Ù† Ø§Ù„ØºØ°Ø§Ø¦ÙŠ.', 'Partilhar conhecimento sobre aconselhamento agrÃ­cola, informaÃ§Ã£o climÃ¡tica e preparaÃ§Ã£o para choques alimentares.', 'Compartir conocimientos sobre asesoramiento agrÃ­cola, informaciÃ³n climÃ¡tica y preparaciÃ³n ante crisis alimentarias.', 'Kushirikishana maarifa kuhusu ushauri wa kilimo, taarifa za hali ya hewa na maandalizi ya mishtuko ya chakula.'),
                'icon' => 'spark',
            ],
            [
                'slug' => 'fsrp-productive-base-resilience',
                'title' => $this->t('Productive-base resilience', 'RÃ©silience de la base productive', 'ØµÙ…ÙˆØ¯ Ø§Ù„Ù‚Ø§Ø¹Ø¯Ø© Ø§Ù„Ø¥Ù†ØªØ§Ø¬ÙŠØ©', 'ResiliÃªncia da base produtiva', 'Resiliencia de la base productiva', 'Ustahimilivu wa msingi wa uzalishaji'),
                'excerpt' => $this->t('Connect experience in resilient landscapes, water use, production systems and agricultural value chains.', 'Relier les expÃ©riences sur les paysages rÃ©silients, lâ€™eau, les systÃ¨mes de production et les chaÃ®nes de valeur agricoles.', 'Ø±Ø¨Ø· Ø§Ù„Ø®Ø¨Ø±Ø§Øª ÙÙŠ Ø§Ù„Ø£Ø±Ø§Ø¶ÙŠ Ø§Ù„ØµØ§Ù…Ø¯Ø© ÙˆØ§Ø³ØªØ®Ø¯Ø§Ù… Ø§Ù„Ù…ÙŠØ§Ù‡ ÙˆÙ†Ø¸Ù… Ø§Ù„Ø¥Ù†ØªØ§Ø¬ ÙˆØ³Ù„Ø§Ø³Ù„ Ø§Ù„Ù‚ÙŠÙ…Ø© Ø§Ù„Ø²Ø±Ø§Ø¹ÙŠØ©.', 'Ligar experiÃªncias em paisagens resilientes, utilizaÃ§Ã£o da Ã¡gua, sistemas de produÃ§Ã£o e cadeias de valor agrÃ­colas.', 'Conectar experiencias sobre paisajes resilientes, uso del agua, sistemas de producciÃ³n y cadenas de valor agrÃ­colas.', 'Kuunganisha uzoefu wa mandhari stahimilivu, matumizi ya maji, mifumo ya uzalishaji na minyororo ya thamani ya kilimo.'),
                'icon' => 'leaf',
            ],
            [
                'slug' => 'fsrp-regional-food-markets',
                'title' => $this->t('Regional food markets & trade', 'MarchÃ©s alimentaires rÃ©gionaux et commerce', 'Ø£Ø³ÙˆØ§Ù‚ Ø§Ù„ØºØ°Ø§Ø¡ Ø§Ù„Ø¥Ù‚Ù„ÙŠÙ…ÙŠØ© ÙˆØ§Ù„ØªØ¬Ø§Ø±Ø©', 'Mercados alimentares regionais e comÃ©rcio', 'Mercados alimentarios regionales y comercio', 'Masoko ya kikanda ya chakula na biashara'),
                'excerpt' => $this->t('Bring partners together around market links, cross-border food trade and practical barriers facing value chains.', 'RÃ©unir les partenaires autour des marchÃ©s, du commerce alimentaire transfrontalier et des obstacles aux chaÃ®nes de valeur.', 'Ø¬Ù…Ø¹ Ø§Ù„Ø´Ø±ÙƒØ§Ø¡ Ø­ÙˆÙ„ Ø±ÙˆØ§Ø¨Ø· Ø§Ù„Ø£Ø³ÙˆØ§Ù‚ ÙˆØªØ¬Ø§Ø±Ø© Ø§Ù„ØºØ°Ø§Ø¡ Ø¹Ø¨Ø± Ø§Ù„Ø­Ø¯ÙˆØ¯ ÙˆØ§Ù„Ø¹ÙˆØ§Ø¦Ù‚ Ø£Ù…Ø§Ù… Ø³Ù„Ø§Ø³Ù„ Ø§Ù„Ù‚ÙŠÙ…Ø©.', 'Reunir parceiros em torno das ligaÃ§Ãµes de mercado, do comÃ©rcio alimentar transfronteiriÃ§o e dos obstÃ¡culos Ã s cadeias de valor.', 'Reunir a socios en torno a los vÃ­nculos de mercado, el comercio alimentario transfronterizo y los obstÃ¡culos de las cadenas de valor.', 'Kukutanisha washirika kuhusu uhusiano wa masoko, biashara ya chakula kuvuka mipaka na vikwazo vya minyororo ya thamani.'),
                'icon' => 'bridge',
            ],
            [
                'slug' => 'fsrp-food-crisis-preparedness',
                'title' => $this->t('Food crisis preparedness', 'PrÃ©paration aux crises alimentaires', 'Ø§Ù„Ø§Ø³ØªØ¹Ø¯Ø§Ø¯ Ù„Ù„Ø£Ø²Ù…Ø§Øª Ø§Ù„ØºØ°Ø§Ø¦ÙŠØ©', 'PreparaÃ§Ã£o para crises alimentares', 'PreparaciÃ³n ante crisis alimentarias', 'Maandalizi ya kukabili migogoro ya chakula'),
                'excerpt' => $this->t('Exchange approaches to early action, coordinated emergency response and accountable follow-up during food security shocks.', 'Ã‰changer sur lâ€™action prÃ©coce, la rÃ©ponse dâ€™urgence coordonnÃ©e et le suivi responsable face aux chocs alimentaires.', 'ØªØ¨Ø§Ø¯Ù„ Ù†Ù‡Ø¬ Ø§Ù„Ø¹Ù…Ù„ Ø§Ù„Ù…Ø¨ÙƒØ± ÙˆØ§Ù„Ø§Ø³ØªØ¬Ø§Ø¨Ø© Ø§Ù„Ù…Ù†Ø³Ù‚Ø© Ù„Ù„Ø·ÙˆØ§Ø±Ø¦ ÙˆØ§Ù„Ù…ØªØ§Ø¨Ø¹Ø© Ø§Ù„Ù…Ø³Ø¤ÙˆÙ„Ø© Ø£Ø«Ù†Ø§Ø¡ ØµØ¯Ù…Ø§Øª Ø§Ù„Ø£Ù…Ù† Ø§Ù„ØºØ°Ø§Ø¦ÙŠ.', 'Trocar abordagens de aÃ§Ã£o antecipada, resposta coordenada a emergÃªncias e acompanhamento responsÃ¡vel durante choques alimentares.', 'Intercambiar enfoques de acciÃ³n temprana, respuesta coordinada a emergencias y seguimiento responsable ante crisis alimentarias.', 'Kubadilishana mbinu za hatua za mapema, mwitikio ulioratibiwa wa dharura na ufuatiliaji wenye uwajibikaji wakati wa mishtuko ya chakula.'),
                'icon' => 'compass',
            ],
        ];

        foreach ($programs as $index => $program) {
            Program::query()->updateOrCreate(['slug' => $program['slug']], array_merge($program, [
                'body' => $program['excerpt'],
                'sort_order' => $index + 1,
                'is_published' => true,
            ]));
        }
    }

    private function seedAboutPage(): void
    {
        Page::query()->updateOrCreate(['key' => 'about'], [
            'eyebrow' => $this->t('About FSRP Events', 'Ã€ propos des Ã©vÃ©nements FSRP', 'Ø­ÙˆÙ„ ÙØ¹Ø§Ù„ÙŠØ§Øª FSRP', 'Sobre os Eventos FSRP', 'Acerca de Eventos FSRP', 'Kuhusu Matukio ya FSRP'),
            'title' => $this->t('Connecting people for resilient food systems', 'Rassembler les acteurs pour des systÃ¨mes alimentaires rÃ©silients', 'Ø±Ø¨Ø· Ø§Ù„Ø£Ø´Ø®Ø§Øµ Ù…Ù† Ø£Ø¬Ù„ Ù†Ø¸Ù… ØºØ°Ø§Ø¦ÙŠØ© ØµØ§Ù…Ø¯Ø©', 'Ligar pessoas para sistemas alimentares resilientes', 'Conectar personas para sistemas alimentarios resilientes', 'Kuunganisha watu kwa mifumo ya chakula yenye ustahimilivu'),
            'body' => $this->t(
                'FSRP Events brings together programme themes, learning resources and relevant partner convenings around food system resilience across Africa. Explore digital advisory services, productive-base resilience, regional food markets and preparedness for food security shocks. Partner events identify their own conveners. The CAADP information on this portal is based on the supplied 22nd Partnership Platform key-information brief.',
                'FSRP Events rassemble les thÃ¨mes du programme, les ressources et les rencontres de partenaires autour de la rÃ©silience des systÃ¨mes alimentaires dans toute lâ€™Afrique. Explorez le conseil numÃ©rique, la rÃ©silience de la base productive, les marchÃ©s alimentaires rÃ©gionaux et la prÃ©paration aux chocs. Chaque Ã©vÃ©nement partenaire prÃ©cise ses organisateurs. Les informations CAADP proviennent de la note fournie sur la 22e Plateforme de partenariat.',
                'ØªØ¬Ù…Ø¹ ÙØ¹Ø§Ù„ÙŠØ§Øª FSRP Ù…Ø­Ø§ÙˆØ± Ø§Ù„Ø¨Ø±Ù†Ø§Ù…Ø¬ ÙˆÙ…ÙˆØ§Ø±Ø¯ Ø§Ù„ØªØ¹Ù„Ù‘Ù… ÙˆÙ„Ù‚Ø§Ø¡Ø§Øª Ø§Ù„Ø´Ø±ÙƒØ§Ø¡ Ø°Ø§Øª Ø§Ù„ØµÙ„Ø© Ø¨ØµÙ…ÙˆØ¯ Ø§Ù„Ù†Ø¸Ù… Ø§Ù„ØºØ°Ø§Ø¦ÙŠØ© ÙÙŠ Ø¬Ù…ÙŠØ¹ Ø£Ù†Ø­Ø§Ø¡ Ø¥ÙØ±ÙŠÙ‚ÙŠØ§. Ø§Ø³ØªÙƒØ´Ù Ø§Ù„Ø¥Ø±Ø´Ø§Ø¯ Ø§Ù„Ø±Ù‚Ù…ÙŠ ÙˆØµÙ…ÙˆØ¯ Ø§Ù„Ù‚Ø§Ø¹Ø¯Ø© Ø§Ù„Ø¥Ù†ØªØ§Ø¬ÙŠØ© ÙˆØ£Ø³ÙˆØ§Ù‚ Ø§Ù„ØºØ°Ø§Ø¡ Ø§Ù„Ø¥Ù‚Ù„ÙŠÙ…ÙŠØ© ÙˆØ§Ù„Ø§Ø³ØªØ¹Ø¯Ø§Ø¯ Ù„Ù„ØµØ¯Ù…Ø§Øª. ØªØ­Ø¯Ø¯ ÙØ¹Ø§Ù„ÙŠØ§Øª Ø§Ù„Ø´Ø±ÙƒØ§Ø¡ Ø§Ù„Ø¬Ù‡Ø§Øª Ø§Ù„Ù…Ù†Ø¸Ù…Ø© Ù„Ù‡Ø§. ØªØ³ØªÙ†Ø¯ Ù…Ø¹Ù„ÙˆÙ…Ø§Øª CAADP Ø¥Ù„Ù‰ Ø§Ù„Ù…ÙˆØ¬Ø² Ø§Ù„Ù…Ù‚Ø¯Ù… Ø¹Ù† Ù…Ù†ØµØ© Ø§Ù„Ø´Ø±Ø§ÙƒØ© Ø§Ù„Ø«Ø§Ù†ÙŠØ© ÙˆØ§Ù„Ø¹Ø´Ø±ÙŠÙ†.',
                'FSRP Events reÃºne temas do programa, recursos de aprendizagem e encontros de parceiros sobre a resiliÃªncia dos sistemas alimentares em toda a Ãfrica. Explore aconselhamento digital, resiliÃªncia da base produtiva, mercados alimentares regionais e preparaÃ§Ã£o para choques. Os eventos de parceiros identificam os seus organizadores. A informaÃ§Ã£o CAADP baseia-se na nota fornecida sobre a 22.Âª Plataforma de Parceria.',
                'FSRP Events reÃºne temas del programa, recursos de aprendizaje y encuentros de socios sobre la resiliencia de los sistemas alimentarios en toda Ãfrica. Explore asesoramiento digital, resiliencia de la base productiva, mercados alimentarios regionales y preparaciÃ³n ante crisis. Los eventos de socios identifican a sus organizadores. La informaciÃ³n CAADP se basa en la nota facilitada de la 22.Âª Plataforma de AsociaciÃ³n.',
                'Matukio ya FSRP yanakusanya mada za programu, nyenzo za kujifunza na mikutano ya washirika kuhusu ustahimilivu wa mifumo ya chakula katika Afrika nzima. Chunguza ushauri wa kidijitali, ustahimilivu wa uzalishaji, masoko ya kikanda na maandalizi ya mishtuko. Matukio ya washirika yanataja waandaaji wao. Taarifa za CAADP zinatokana na muhtasari uliotolewa wa Jukwaa la 22 la Ushirikiano.',
            ),
            'image' => '/images/fsrp/water-food-resilience-2.jpg',
            'is_published' => true,
        ]);
    }

    private function seedFaqs(): void
    {
        $faqs = [
            [
                $this->t('What is FSRP Events?', 'Quâ€™est-ce que FSRP Events ?', 'Ù…Ø§ Ù‡ÙŠ ÙØ¹Ø§Ù„ÙŠØ§Øª FSRPØŸ', 'O que sÃ£o os Eventos FSRP?', 'Â¿QuÃ© es Eventos FSRP?', 'Matukio ya FSRP ni nini?'),
                $this->t('A place to explore programme themes, shared learning and partner events relevant to food system resilience across Africa.', 'Un espace consacrÃ© aux thÃ¨mes du programme, Ã  lâ€™apprentissage partagÃ© et aux Ã©vÃ©nements partenaires sur la rÃ©silience des systÃ¨mes alimentaires dans toute lâ€™Afrique.', 'Ù…Ù†ØµØ© Ù„Ø§Ø³ØªÙƒØ´Ø§Ù Ù…Ø­Ø§ÙˆØ± Ø§Ù„Ø¨Ø±Ù†Ø§Ù…Ø¬ ÙˆØ§Ù„ØªØ¹Ù„Ù‘Ù… Ø§Ù„Ù…Ø´ØªØ±Ùƒ ÙˆÙØ¹Ø§Ù„ÙŠØ§Øª Ø§Ù„Ø´Ø±ÙƒØ§Ø¡ Ø§Ù„Ù…ØªØ¹Ù„Ù‚Ø© Ø¨ØµÙ…ÙˆØ¯ Ø§Ù„Ù†Ø¸Ù… Ø§Ù„ØºØ°Ø§Ø¦ÙŠØ© ÙÙŠ Ø¬Ù…ÙŠØ¹ Ø£Ù†Ø­Ø§Ø¡ Ø¥ÙØ±ÙŠÙ‚ÙŠØ§.', 'Um espaÃ§o para explorar temas do programa, aprendizagem partilhada e eventos de parceiros sobre resiliÃªncia alimentar em toda a Ãfrica.', 'Un espacio para explorar temas del programa, aprendizaje compartido y eventos de socios sobre resiliencia alimentaria en toda Ãfrica.', 'Mahali pa kuchunguza mada za programu, kujifunza pamoja na matukio ya washirika kuhusu ustahimilivu wa chakula Afrika nzima.'),
            ],
            [
                $this->t('Who convenes the 22nd CAADP Partnership Platform?', 'Qui organise la 22e Plateforme de partenariat du PDDAA ?', 'Ù…Ù† ÙŠÙ†Ø¸Ù… Ù…Ù†ØµØ© Ø´Ø±Ø§ÙƒØ© CAADP Ø§Ù„Ø«Ø§Ù†ÙŠØ© ÙˆØ§Ù„Ø¹Ø´Ø±ÙŠÙ†ØŸ', 'Quem organiza a 22.Âª Plataforma de Parceria CAADP?', 'Â¿QuiÃ©n convoca la 22.Âª Plataforma de AsociaciÃ³n CAADP?', 'Nani anaandaa Jukwaa la 22 la Ushirikiano wa CAADP?'),
                $this->t('This partner event is convened by the African Union Commission (AUC) and AUDA-NEPAD, with Member States, regional economic communities, technical institutions and development partners. It is not presented here as an FSRP-convened event.', 'Cet Ã©vÃ©nement partenaire est organisÃ© par la Commission de lâ€™Union africaine (CUA) et lâ€™AUDA-NEPAD, avec les Ã‰tats membres, les communautÃ©s Ã©conomiques rÃ©gionales, les institutions techniques et les partenaires de dÃ©veloppement.', 'ØªÙ†Ø¸Ù… Ù‡Ø°Ù‡ Ø§Ù„ÙØ¹Ø§Ù„ÙŠØ© Ø§Ù„Ø´Ø±ÙŠÙƒØ© Ù…ÙÙˆØ¶ÙŠØ© Ø§Ù„Ø§ØªØ­Ø§Ø¯ Ø§Ù„Ø¥ÙØ±ÙŠÙ‚ÙŠ ÙˆÙˆÙƒØ§Ù„Ø© Ø§Ù„Ø§ØªØ­Ø§Ø¯ Ø§Ù„Ø¥ÙØ±ÙŠÙ‚ÙŠ Ù„Ù„ØªÙ†Ù…ÙŠØ©-Ù†ÙŠØ¨Ø§Ø¯ØŒ Ù…Ø¹ Ø§Ù„Ø¯ÙˆÙ„ Ø§Ù„Ø£Ø¹Ø¶Ø§Ø¡ ÙˆØ§Ù„Ù…Ø¬Ù…ÙˆØ¹Ø§Øª Ø§Ù„Ø§Ù‚ØªØµØ§Ø¯ÙŠØ© Ø§Ù„Ø¥Ù‚Ù„ÙŠÙ…ÙŠØ© ÙˆØ§Ù„Ù…Ø¤Ø³Ø³Ø§Øª Ø§Ù„ÙÙ†ÙŠØ© ÙˆØ´Ø±ÙƒØ§Ø¡ Ø§Ù„ØªÙ†Ù…ÙŠØ©.', 'Este evento de parceiros Ã© organizado pela ComissÃ£o da UniÃ£o Africana e pela AUDA-NEPAD, com Estados-Membros, comunidades econÃ³micas regionais, instituiÃ§Ãµes tÃ©cnicas e parceiros de desenvolvimento.', 'Este evento de socios es convocado por la ComisiÃ³n de la UniÃ³n Africana y AUDA-NEPAD, con los Estados miembros, comunidades econÃ³micas regionales, instituciones tÃ©cnicas y socios de desarrollo.', 'Tukio hili la washirika linaandaliwa na Tume ya Umoja wa Afrika na AUDA-NEPAD, pamoja na nchi wanachama, jumuiya za kiuchumi za kikanda, taasisi za kiufundi na washirika wa maendeleo.'),
            ],
            [
                $this->t('When and where is the CAADP partner event?', 'Quand et oÃ¹ se tient lâ€™Ã©vÃ©nement partenaire PDDAA ?', 'Ù…ØªÙ‰ ÙˆØ£ÙŠÙ† ØªÙ‚Ø§Ù… ÙØ¹Ø§Ù„ÙŠØ© CAADP Ø§Ù„Ø´Ø±ÙŠÙƒØ©ØŸ', 'Quando e onde decorre o evento parceiro CAADP?', 'Â¿CuÃ¡ndo y dÃ³nde se celebra el evento socio CAADP?', 'Tukio la washirika la CAADP litafanyika lini na wapi?'),
                $this->t('15â€“18 September 2026 in Harare, Zimbabwe, in person. The brief does not identify a specific venue or daily session times; these must be confirmed by the conveners.', 'Du 15 au 18 septembre 2026 Ã  Harare, au Zimbabwe, en prÃ©sentiel. Le lieu prÃ©cis et les horaires des sessions ne figurent pas dans la note et doivent Ãªtre confirmÃ©s par les organisateurs.', 'Ù…Ù† 15 Ø¥Ù„Ù‰ 18 Ø³Ø¨ØªÙ…Ø¨Ø± 2026 ÙÙŠ Ù‡Ø±Ø§Ø±ÙŠØŒ Ø²ÙŠÙ…Ø¨Ø§Ø¨ÙˆÙŠØŒ Ø­Ø¶ÙˆØ±ÙŠØ§Ù‹. Ù„Ø§ ÙŠØ­Ø¯Ø¯ Ø§Ù„Ù…ÙˆØ¬Ø² Ù…ÙƒØ§Ù†Ø§Ù‹ Ø¯Ù‚ÙŠÙ‚Ø§Ù‹ Ø£Ùˆ Ù…ÙˆØ§Ø¹ÙŠØ¯ Ø§Ù„Ø¬Ù„Ø³Ø§Øª Ø§Ù„ÙŠÙˆÙ…ÙŠØ©Ø› ÙˆÙŠØ¬Ø¨ ØªØ£ÙƒÙŠØ¯Ù‡Ø§ Ù…Ù† Ø§Ù„Ù…Ù†Ø¸Ù…ÙŠÙ†.', 'De 15 a 18 de setembro de 2026, em Harare, ZimbabuÃ©, presencialmente. O local especÃ­fico e os horÃ¡rios das sessÃµes devem ser confirmados pelos organizadores.', 'Del 15 al 18 de septiembre de 2026 en Harare, Zimbabue, de forma presencial. Los organizadores deben confirmar el lugar especÃ­fico y los horarios de las sesiones.', 'Tarehe 15â€“18 Septemba 2026, Harare, Zimbabwe, kwa kuhudhuria ana kwa ana. Ukumbi maalumu na saa za vipindi hazijatajwa kwenye muhtasari; waandaaji wanapaswa kuzithibitisha.'),
            ],
            [
                $this->t('Where can I download the CAADP programme?', 'OÃ¹ tÃ©lÃ©charger le programme PDDAA ?', 'Ø£ÙŠÙ† ÙŠÙ…ÙƒÙ†Ù†ÙŠ ØªÙ†Ø²ÙŠÙ„ Ø¨Ø±Ù†Ø§Ù…Ø¬ CAADPØŸ', 'Onde posso descarregar o programa CAADP?', 'Â¿DÃ³nde puedo descargar el programa CAADP?', 'Ninaweza kupakua programu ya CAADP wapi?'),
                $this->t('Use the event downloads to access the original English key-information brief and the four-day programme overview PDF. The overview follows the brief and is not a detailed timed agenda.', 'Les tÃ©lÃ©chargements de lâ€™Ã©vÃ©nement proposent la note dâ€™information originale en anglais et un aperÃ§u PDF des quatre journÃ©es. Cet aperÃ§u suit la note et ne constitue pas un programme horaire dÃ©taillÃ©.', 'Ø§Ø³ØªØ®Ø¯Ù… ØªÙ†Ø²ÙŠÙ„Ø§Øª Ø§Ù„ÙØ¹Ø§Ù„ÙŠØ© Ù„Ù„ÙˆØµÙˆÙ„ Ø¥Ù„Ù‰ Ù…ÙˆØ¬Ø² Ø§Ù„Ù…Ø¹Ù„ÙˆÙ…Ø§Øª Ø§Ù„Ø£ØµÙ„ÙŠ Ø¨Ø§Ù„Ø¥Ù†Ø¬Ù„ÙŠØ²ÙŠØ© ÙˆÙ…Ù„Ù PDF Ù„Ù†Ø¸Ø±Ø© Ø¹Ø§Ù…Ø© Ø¹Ù„Ù‰ Ø§Ù„Ø¨Ø±Ù†Ø§Ù…Ø¬ Ù„Ø£Ø±Ø¨Ø¹Ø© Ø£ÙŠØ§Ù…. ØªØªØ¨Ø¹ Ø§Ù„Ù†Ø¸Ø±Ø© Ø§Ù„Ø¹Ø§Ù…Ø© Ø§Ù„Ù…ÙˆØ¬Ø² ÙˆÙ„Ø§ ØªÙ…Ø«Ù„ Ø¬Ø¯ÙˆÙ„Ø§Ù‹ Ø²Ù…Ù†ÙŠØ§Ù‹ ØªÙØµÙŠÙ„ÙŠØ§Ù‹.', 'Nos documentos do evento encontra a nota original em inglÃªs e o PDF com a visÃ£o geral dos quatro dias. O documento segue a nota e nÃ£o Ã© uma agenda detalhada com horÃ¡rios.', 'Los documentos del evento incluyen la nota original en inglÃ©s y el PDF del programa de cuatro dÃ­as. El resumen sigue la nota y no constituye una agenda detallada con horarios.', 'Tumia sehemu ya nyaraka za tukio kupata muhtasari asili wa Kiingereza na PDF ya programu ya siku nne. Muhtasari unafuata waraka na si ratiba yenye saa za kina.'),
            ],
            [
                $this->t('What interpretation is listed for CAADP?', 'Quelles langues dâ€™interprÃ©tation sont prÃ©vues pour le PDDAA ?', 'Ù…Ø§ Ù„ØºØ§Øª Ø§Ù„ØªØ±Ø¬Ù…Ø© Ø§Ù„ÙÙˆØ±ÙŠØ© Ø§Ù„Ù…Ø°ÙƒÙˆØ±Ø© Ù„ÙØ¹Ø§Ù„ÙŠØ© CAADPØŸ', 'Que lÃ­nguas de interpretaÃ§Ã£o estÃ£o previstas para o CAADP?', 'Â¿QuÃ© idiomas de interpretaciÃ³n se indican para CAADP?', 'Ni lugha zipi za ukalimani zilizoorodheshwa kwa CAADP?'),
                $this->t('The brief lists Arabic, English, French and Portuguese. The portal also supports Spanish and Swahili for browsing; this does not imply event interpretation in those languages.', 'La note mentionne lâ€™arabe, lâ€™anglais, le franÃ§ais et le portugais. Le portail peut aussi Ãªtre consultÃ© en espagnol et en swahili, sans que cela implique une interprÃ©tation dans ces langues.', 'ÙŠØ°ÙƒØ± Ø§Ù„Ù…ÙˆØ¬Ø² Ø§Ù„Ø¹Ø±Ø¨ÙŠØ© ÙˆØ§Ù„Ø¥Ù†Ø¬Ù„ÙŠØ²ÙŠØ© ÙˆØ§Ù„ÙØ±Ù†Ø³ÙŠØ© ÙˆØ§Ù„Ø¨Ø±ØªØºØ§Ù„ÙŠØ©. ÙŠØªÙŠØ­ Ø§Ù„Ù…ÙˆÙ‚Ø¹ Ø£ÙŠØ¶Ø§Ù‹ Ø§Ù„ØªØµÙØ­ Ø¨Ø§Ù„Ø¥Ø³Ø¨Ø§Ù†ÙŠØ© ÙˆØ§Ù„Ø³ÙˆØ§Ø­ÙŠÙ„ÙŠØ©Ø› ÙˆÙ‡Ø°Ø§ Ù„Ø§ ÙŠØ¹Ù†ÙŠ ØªÙˆÙÙŠØ± ØªØ±Ø¬Ù…Ø© ÙÙˆØ±ÙŠØ© Ø¨Ù‡Ø§ØªÙŠÙ† Ø§Ù„Ù„ØºØªÙŠÙ† ÙÙŠ Ø§Ù„ÙØ¹Ø§Ù„ÙŠØ©.', 'A nota indica Ã¡rabe, inglÃªs, francÃªs e portuguÃªs. O portal tambÃ©m pode ser consultado em espanhol e suaÃ­li, o que nÃ£o implica interpretaÃ§Ã£o nesses idiomas durante o evento.', 'La nota indica Ã¡rabe, inglÃ©s, francÃ©s y portuguÃ©s. El portal tambiÃ©n ofrece navegaciÃ³n en espaÃ±ol y suajili, sin que ello implique interpretaciÃ³n en esos idiomas durante el evento.', 'Muhtasari unaorodhesha Kiarabu, Kiingereza, Kifaransa na Kireno. Tovuti pia ina Kihispania na Kiswahili kwa kuvinjari; hii haimaanishi ukalimani wa lugha hizo katika tukio.'),
            ],
            [
                $this->t('How do I confirm participation and travel arrangements?', 'Comment confirmer ma participation et les modalitÃ©s de voyage ?', 'ÙƒÙŠÙ Ø£Ø¤ÙƒØ¯ Ø§Ù„Ù…Ø´Ø§Ø±ÙƒØ© ÙˆØªØ±ØªÙŠØ¨Ø§Øª Ø§Ù„Ø³ÙØ±ØŸ', 'Como confirmar a participaÃ§Ã£o e os preparativos de viagem?', 'Â¿CÃ³mo confirmo la participaciÃ³n y los preparativos de viaje?', 'Ninathibitishaje ushiriki na mipango ya safari?'),
                $this->t('Follow the event convenersâ€™ instructions for delegation confirmation, invitations, registration, travel and accessibility. The supplied brief does not provide a public registration link, fees, accommodation or visa-support arrangements.', 'Suivez les instructions des organisateurs pour les dÃ©lÃ©gations, invitations, inscriptions, voyages et lâ€™accessibilitÃ©. La note fournie ne prÃ©cise pas de lien dâ€™inscription public, de frais, dâ€™hÃ©bergement ni de dispositif dâ€™aide au visa.', 'Ø§ØªØ¨Ø¹ ØªØ¹Ù„ÙŠÙ…Ø§Øª Ø§Ù„Ù…Ù†Ø¸Ù…ÙŠÙ† Ù„ØªØ£ÙƒÙŠØ¯ Ø§Ù„ÙˆÙÙˆØ¯ ÙˆØ§Ù„Ø¯Ø¹ÙˆØ§Øª ÙˆØ§Ù„ØªØ³Ø¬ÙŠÙ„ ÙˆØ§Ù„Ø³ÙØ± ÙˆØ¥Ù…ÙƒØ§Ù†ÙŠØ© Ø§Ù„ÙˆØµÙˆÙ„. Ù„Ø§ ÙŠØªØ¶Ù…Ù† Ø§Ù„Ù…ÙˆØ¬Ø² Ø±Ø§Ø¨Ø· ØªØ³Ø¬ÙŠÙ„ Ø¹Ø§Ù…Ø§Ù‹ Ø£Ùˆ Ø±Ø³ÙˆÙ…Ø§Ù‹ Ø£Ùˆ ØªØ±ØªÙŠØ¨Ø§Øª Ø¥Ù‚Ø§Ù…Ø© Ø£Ùˆ Ø¯Ø¹Ù… Ø§Ù„ØªØ£Ø´ÙŠØ±Ø§Øª.', 'Siga as instruÃ§Ãµes dos organizadores sobre delegaÃ§Ãµes, convites, inscriÃ§Ã£o, viagem e acessibilidade. A nota nÃ£o indica ligaÃ§Ã£o de inscriÃ§Ã£o pÃºblica, custos, alojamento ou apoio para vistos.', 'Siga las instrucciones de los organizadores sobre delegaciones, invitaciones, registro, viaje y accesibilidad. La nota no facilita un enlace pÃºblico de inscripciÃ³n, tarifas, alojamiento ni apoyo para visados.', 'Fuata maelekezo ya waandaaji kuhusu wajumbe, mialiko, usajili, safari na ufikivu. Muhtasari hautoi kiungo cha usajili wa umma, ada, malazi au mipango ya kusaidia visa.'),
            ],
        ];

        foreach ($faqs as $index => [$question, $answer]) {
            $this->saveTranslatedRecord(Faq::class, 'question', [
                'category' => $this->t('Participant information', 'Informations aux participants', 'Ù…Ø¹Ù„ÙˆÙ…Ø§Øª Ø§Ù„Ù…Ø´Ø§Ø±ÙƒÙŠÙ†', 'InformaÃ§Ãµes aos participantes', 'InformaciÃ³n para participantes', 'Taarifa kwa washiriki'),
                'question' => $question,
                'answer' => $answer,
                'sort_order' => $index + 1,
                'is_published' => true,
            ]);
        }

        $this->updateEnglishFaqAnswer(
            'When and where is the CAADP partner event?',
            '15â€“18 September 2026 at Rainbow Towers Hotel and Conference Centre, Pennefather Avenue, Samora Machel Avenue, Harare, Zimbabwe. Daily session times remain subject to confirmation by the conveners.',
        );
        $this->updateEnglishFaqAnswer(
            'How do I confirm participation and travel arrangements?',
            'Follow the convenersâ€™ participation instructions. AUC-sponsored participants are booked at Rainbow Towers and receive arrival and departure airport transfers; self-sponsored participants arrange their own accommodation and local transport. Visa, health, DSA and contact details are in the official information note.',
        );
    }

    private function seedHomeSections(): void
    {
        foreach ([
            'hero' => 'FSRP highlights',
            'events' => 'Upcoming and partner events',
            'sessions' => 'Programme at a glance',
            'programs' => 'FSRP programme themes',
            'resources' => 'Programme and event downloads',
            'media' => 'FSRP in focus',
            'news' => 'Partner event updates',
            'about' => 'About FSRP Events',
            'faq' => 'Participant information',
            'cta' => 'Connect with FSRP',
        ] as $key => $label) {
            HomeSection::query()->updateOrCreate(['key' => $key], [
                'label' => $label,
                'is_active' => true,
                'sort_order' => array_search($key, ['hero', 'events', 'sessions', 'programs', 'resources', 'media', 'news', 'about', 'faq', 'cta'], true) + 1,
            ]);
        }
    }

    private function seedPartnerEvent(): Event
    {
        $event = Event::query()->updateOrCreate(['slug' => self::EVENT_SLUG], [
            'title' => $this->t('22nd CAADP Partnership Platform', '22e Plateforme de partenariat du PDDAA', 'Ù…Ù†ØµØ© Ø´Ø±Ø§ÙƒØ© CAADP Ø§Ù„Ø«Ø§Ù†ÙŠØ© ÙˆØ§Ù„Ø¹Ø´Ø±ÙˆÙ†', '22.Âª Plataforma de Parceria CAADP', '22.Âª Plataforma de AsociaciÃ³n CAADP', 'Jukwaa la 22 la Ushirikiano wa CAADP'),
            'excerpt' => $this->t('Partner event Â· AUC & AUDA-NEPAD Â· From the CAADP Strategy and Action Plan 2026â€“2035 to practical country and regional delivery.', 'Ã‰vÃ©nement partenaire Â· CUA et AUDA-NEPAD Â· De la stratÃ©gie et du plan dâ€™action du PDDAA 2026â€“2035 Ã  une mise en Å“uvre concrÃ¨te aux niveaux national et rÃ©gional.', 'ÙØ¹Ø§Ù„ÙŠØ© Ø´Ø±ÙŠÙƒØ© Â· Ù…ÙÙˆØ¶ÙŠØ© Ø§Ù„Ø§ØªØ­Ø§Ø¯ Ø§Ù„Ø¥ÙØ±ÙŠÙ‚ÙŠ ÙˆAUDA-NEPAD Â· Ù…Ù† Ø§Ø³ØªØ±Ø§ØªÙŠØ¬ÙŠØ© ÙˆØ®Ø·Ø© Ø¹Ù…Ù„ CAADP Ù„Ù„ÙØªØ±Ø© 2026â€“2035 Ø¥Ù„Ù‰ Ø§Ù„ØªÙ†ÙÙŠØ° Ø§Ù„Ø¹Ù…Ù„ÙŠ Ø§Ù„Ù‚Ø·Ø±ÙŠ ÙˆØ§Ù„Ø¥Ù‚Ù„ÙŠÙ…ÙŠ.', 'Evento parceiro Â· CUA e AUDA-NEPAD Â· Da EstratÃ©gia e Plano de AÃ§Ã£o CAADP 2026â€“2035 Ã  implementaÃ§Ã£o prÃ¡tica nacional e regional.', 'Evento de socios Â· CUA y AUDA-NEPAD Â· De la Estrategia y Plan de AcciÃ³n CAADP 2026â€“2035 a la ejecuciÃ³n prÃ¡ctica nacional y regional.', 'Tukio la washirika Â· AUC na AUDA-NEPAD Â· Kutoka Mkakati na Mpango wa Utekelezaji wa CAADP 2026â€“2035 hadi utekelezaji wa vitendo wa nchi na kanda.'),
            'body' => $this->t(
                'PARTNER EVENT. Convened by the African Union Commission (AUC) and AUDA-NEPAD with Member States, regional economic communities, technical institutions and development partners. The four-day Platform links political direction, country and regional readiness, implementation delivery labs, investment alignment and mutual accountability. The meeting takes place at Rainbow Towers Hotel and Conference Centre in Harare from 15â€“18 September 2026. The official information note covers accommodation, airport transfers, immigration, health, DSA, weather, currency, electricity and organizer contacts for AUC-sponsored and self-sponsored participants. Detailed session times remain subject to confirmation by the conveners.',
                'Ã‰VÃ‰NEMENT PARTENAIRE. OrganisÃ© par la Commission de lâ€™Union africaine (CUA) et lâ€™AUDA-NEPAD avec les Ã‰tats membres, les communautÃ©s Ã©conomiques rÃ©gionales, les institutions techniques et les partenaires de dÃ©veloppement. Les quatre journÃ©es relient orientation politique, prÃ©paration nationale et rÃ©gionale, laboratoires de mise en Å“uvre, investissements et redevabilitÃ© mutuelle. Lâ€™interprÃ©tation est annoncÃ©e en arabe, anglais, franÃ§ais et portugais. La note confirme Harare, Zimbabwe, du 15 au 18 septembre 2026 ; les horaires, le lieu prÃ©cis et les modalitÃ©s de participation doivent Ãªtre confirmÃ©s par les organisateurs. TÃ©lÃ©chargez la note originale et lâ€™aperÃ§u du programme.',
                'ÙØ¹Ø§Ù„ÙŠØ© Ø´Ø±ÙŠÙƒØ©. ØªÙ†Ø¸Ù…Ù‡Ø§ Ù…ÙÙˆØ¶ÙŠØ© Ø§Ù„Ø§ØªØ­Ø§Ø¯ Ø§Ù„Ø¥ÙØ±ÙŠÙ‚ÙŠ ÙˆÙˆÙƒØ§Ù„Ø© Ø§Ù„Ø§ØªØ­Ø§Ø¯ Ø§Ù„Ø¥ÙØ±ÙŠÙ‚ÙŠ Ù„Ù„ØªÙ†Ù…ÙŠØ©-Ù†ÙŠØ¨Ø§Ø¯ Ù…Ø¹ Ø§Ù„Ø¯ÙˆÙ„ Ø§Ù„Ø£Ø¹Ø¶Ø§Ø¡ ÙˆØ§Ù„Ù…Ø¬Ù…ÙˆØ¹Ø§Øª Ø§Ù„Ø§Ù‚ØªØµØ§Ø¯ÙŠØ© Ø§Ù„Ø¥Ù‚Ù„ÙŠÙ…ÙŠØ© ÙˆØ§Ù„Ù…Ø¤Ø³Ø³Ø§Øª Ø§Ù„ÙÙ†ÙŠØ© ÙˆØ´Ø±ÙƒØ§Ø¡ Ø§Ù„ØªÙ†Ù…ÙŠØ©. ØªØ±Ø¨Ø· Ø§Ù„Ø£ÙŠØ§Ù… Ø§Ù„Ø£Ø±Ø¨Ø¹Ø© Ø§Ù„ØªÙˆØ¬ÙŠÙ‡ Ø§Ù„Ø³ÙŠØ§Ø³ÙŠ ÙˆØ§Ù„Ø¬Ø§Ù‡Ø²ÙŠØ© Ø§Ù„Ù‚Ø·Ø±ÙŠØ© ÙˆØ§Ù„Ø¥Ù‚Ù„ÙŠÙ…ÙŠØ© ÙˆÙ…Ø®ØªØ¨Ø±Ø§Øª Ø§Ù„ØªÙ†ÙÙŠØ° ÙˆÙ…ÙˆØ§Ø¡Ù…Ø© Ø§Ù„Ø§Ø³ØªØ«Ù…Ø§Ø± ÙˆØ§Ù„Ù…Ø³Ø§Ø¡Ù„Ø© Ø§Ù„Ù…ØªØ¨Ø§Ø¯Ù„Ø©. Ø§Ù„ØªØ±Ø¬Ù…Ø© Ø§Ù„ÙÙˆØ±ÙŠØ© Ù…Ø¯Ø±Ø¬Ø© Ø¨Ø§Ù„Ø¹Ø±Ø¨ÙŠØ© ÙˆØ§Ù„Ø¥Ù†Ø¬Ù„ÙŠØ²ÙŠØ© ÙˆØ§Ù„ÙØ±Ù†Ø³ÙŠØ© ÙˆØ§Ù„Ø¨Ø±ØªØºØ§Ù„ÙŠØ©. ÙŠØ¤ÙƒØ¯ Ø§Ù„Ù…ÙˆØ¬Ø² Ù‡Ø±Ø§Ø±ÙŠØŒ Ø²ÙŠÙ…Ø¨Ø§Ø¨ÙˆÙŠØŒ Ù…Ù† 15 Ø¥Ù„Ù‰ 18 Ø³Ø¨ØªÙ…Ø¨Ø± 2026Ø› ÙˆÙŠØ¬Ø¨ Ø£Ù† ÙŠØ¤ÙƒØ¯ Ø§Ù„Ù…Ù†Ø¸Ù…ÙˆÙ† Ù…ÙˆØ§Ø¹ÙŠØ¯ Ø§Ù„Ø¬Ù„Ø³Ø§Øª ÙˆØ§Ù„Ù…ÙƒØ§Ù† Ø§Ù„Ø¯Ù‚ÙŠÙ‚ ÙˆØªØ±ØªÙŠØ¨Ø§Øª Ø§Ù„Ù…Ø´Ø§Ø±ÙƒØ©. Ù†Ø²Ù‘Ù„ Ø§Ù„Ù…ÙˆØ¬Ø² Ø§Ù„Ø£ØµÙ„ÙŠ ÙˆÙ†Ø¸Ø±Ø© Ø¹Ø§Ù…Ø© Ø¹Ù„Ù‰ Ø§Ù„Ø¨Ø±Ù†Ø§Ù…Ø¬.',
                'EVENTO PARCEIRO. Organizado pela ComissÃ£o da UniÃ£o Africana e pela AUDA-NEPAD com Estados-Membros, comunidades econÃ³micas regionais, instituiÃ§Ãµes tÃ©cnicas e parceiros de desenvolvimento. Os quatro dias ligam orientaÃ§Ã£o polÃ­tica, preparaÃ§Ã£o nacional e regional, laboratÃ³rios de implementaÃ§Ã£o, investimento e responsabilizaÃ§Ã£o mÃºtua. EstÃ¡ indicada interpretaÃ§Ã£o em Ã¡rabe, inglÃªs, francÃªs e portuguÃªs. A nota confirma Harare, ZimbabuÃ©, de 15 a 18 de setembro de 2026; os organizadores devem confirmar horÃ¡rios, local especÃ­fico e participaÃ§Ã£o. Descarregue a nota original e a visÃ£o geral do programa.',
                'EVENTO DE SOCIOS. Convocado por la ComisiÃ³n de la UniÃ³n Africana y AUDA-NEPAD con Estados miembros, comunidades econÃ³micas regionales, instituciones tÃ©cnicas y socios de desarrollo. Los cuatro dÃ­as conectan orientaciÃ³n polÃ­tica, preparaciÃ³n nacional y regional, laboratorios de ejecuciÃ³n, inversiÃ³n y rendiciÃ³n de cuentas mutua. Se indica interpretaciÃ³n en Ã¡rabe, inglÃ©s, francÃ©s y portuguÃ©s. La nota confirma Harare, Zimbabue, del 15 al 18 de septiembre de 2026; los organizadores deben confirmar horarios, lugar especÃ­fico y participaciÃ³n. Descargue la nota original y el resumen del programa.',
                'TUKIO LA WASHIRIKA. Linaandaliwa na Tume ya Umoja wa Afrika na AUDA-NEPAD pamoja na nchi wanachama, jumuiya za kiuchumi za kikanda, taasisi za kiufundi na washirika wa maendeleo. Siku nne zinaunganisha mwelekeo wa kisiasa, utayari wa nchi na kanda, maabara za utekelezaji, uwekezaji na uwajibikaji wa pamoja. Ukalimani umeorodheshwa kwa Kiarabu, Kiingereza, Kifaransa na Kireno. Muhtasari unathibitisha Harare, Zimbabwe, tarehe 15â€“18 Septemba 2026; waandaaji wanapaswa kuthibitisha saa, ukumbi maalumu na ushiriki. Pakua waraka asili na muhtasari wa programu.',
            ),
            'venue' => $this->t('Rainbow Towers Hotel and Conference Centre, Harare, Zimbabwe'),
            'start_at' => '2026-09-15 00:00:00',
            'end_at' => '2026-09-18 23:59:59',
            'mode' => 'in-person',
            'registration_url' => null,
            'image' => '/images/caadp/caadp-partnership-4.jpeg',
            'is_featured' => true,
            'is_published' => true,
        ]);

        $excerpt = $event->excerpt;
        $excerpt['en'] = 'Partner event | AUC and AUDA-NEPAD | From the CAADP Strategy and Action Plan 2026-2035 to practical country and regional delivery.';
        $body = $event->body;
        $body['en'] = 'PARTNER EVENT. Convened by the African Union Commission (AUC) and AUDA-NEPAD with Member States, regional economic communities, technical institutions and development partners. The four-day Platform links political direction, country and regional readiness, implementation delivery labs, investment alignment and mutual accountability. The meeting takes place at Rainbow Towers Hotel and Conference Centre in Harare from 15-18 September 2026. The official information note covers accommodation, airport transfers, immigration, health, DSA, weather, currency, electricity and organizer contacts for AUC-sponsored and self-sponsored participants. Detailed session times remain subject to confirmation by the conveners.';
        $event->update(['excerpt' => $excerpt, 'body' => $body]);

        return $event;
    }

    private function seedDailyOverview(Event $event): void
    {
        $days = [
            [
                'date' => '2026-09-15',
                'title' => $this->t('Day 1 Â· Political direction & CAADP strategy', 'Jour 1 Â· Orientation politique et stratÃ©gie PDDAA', 'Ø§Ù„ÙŠÙˆÙ… Ø§Ù„Ø£ÙˆÙ„ Â· Ø§Ù„ØªÙˆØ¬ÙŠÙ‡ Ø§Ù„Ø³ÙŠØ§Ø³ÙŠ ÙˆØ§Ø³ØªØ±Ø§ØªÙŠØ¬ÙŠØ© CAADP', 'Dia 1 Â· OrientaÃ§Ã£o polÃ­tica e estratÃ©gia CAADP', 'DÃ­a 1 Â· OrientaciÃ³n polÃ­tica y estrategia CAADP', 'Siku ya 1 Â· Mwelekeo wa kisiasa na mkakati wa CAADP'),
                'summary' => $this->t(
                    'Use evidence and the Kampala framework to set direction and agree criteria for selecting priority implementation actions. Daily overview from the brief; session times to be confirmed.',
                    'Sâ€™appuyer sur les donnÃ©es et le cadre de Kampala pour fixer les orientations et convenir de critÃ¨res de sÃ©lection des actions prioritaires de mise en Å“uvre. AperÃ§u de la journÃ©e tirÃ© de la note ; horaires des sessions Ã  confirmer.',
                    'Ø§Ø³ØªØ®Ø¯Ø§Ù… Ø§Ù„Ø£Ø¯Ù„Ø© ÙˆØ¥Ø·Ø§Ø± ÙƒÙ…Ø¨Ø§Ù„Ø§ Ù„ØªØ­Ø¯ÙŠØ¯ Ø§Ù„ØªÙˆØ¬Ù‡Ø§Øª ÙˆØ§Ù„Ø§ØªÙØ§Ù‚ Ø¹Ù„Ù‰ Ù…Ø¹Ø§ÙŠÙŠØ± Ø§Ø®ØªÙŠØ§Ø± Ø¥Ø¬Ø±Ø§Ø¡Ø§Øª Ø§Ù„ØªÙ†ÙÙŠØ° Ø°Ø§Øª Ø§Ù„Ø£ÙˆÙ„ÙˆÙŠØ©. Ù†Ø¸Ø±Ø© Ø¹Ø§Ù…Ø© Ø¹Ù„Ù‰ Ø§Ù„ÙŠÙˆÙ… Ù…Ø³ØªÙ…Ø¯Ø© Ù…Ù† Ø§Ù„Ù…ÙˆØ¬Ø²Ø› Ù…ÙˆØ§Ø¹ÙŠØ¯ Ø§Ù„Ø¬Ù„Ø³Ø§Øª Ù‚ÙŠØ¯ Ø§Ù„ØªØ£ÙƒÙŠØ¯.',
                    'Utilizar as evidÃªncias e o quadro de Kampala para definir orientaÃ§Ãµes e acordar critÃ©rios de seleÃ§Ã£o das aÃ§Ãµes prioritÃ¡rias de implementaÃ§Ã£o. VisÃ£o geral do dia baseada na nota; horÃ¡rios das sessÃµes a confirmar.',
                    'Utilizar la evidencia y el marco de Kampala para establecer la orientaciÃ³n y acordar criterios de selecciÃ³n de acciones prioritarias de ejecuciÃ³n. Resumen del dÃ­a basado en la nota; horarios de las sesiones por confirmar.',
                    'Tumia ushahidi na mfumo wa Kampala kuweka mwelekeo na kukubaliana vigezo vya kuchagua hatua za kipaumbele za utekelezaji. Muhtasari wa siku umetokana na waraka; saa za vipindi bado zitathibitishwa.',
                ),
            ],
            [
                'date' => '2026-09-16',
                'title' => $this->t('Day 2 Â· Country readiness & REC delivery', 'Jour 2 Â· PrÃ©paration nationale et mise en Å“uvre des CER', 'Ø§Ù„ÙŠÙˆÙ… Ø§Ù„Ø«Ø§Ù†ÙŠ Â· Ø¬Ø§Ù‡Ø²ÙŠØ© Ø§Ù„Ø¨Ù„Ø¯Ø§Ù† ÙˆØ§Ù„ØªÙ†ÙÙŠØ° Ø§Ù„Ø¥Ù‚Ù„ÙŠÙ…ÙŠ', 'Dia 2 Â· PreparaÃ§Ã£o nacional e implementaÃ§Ã£o das CER', 'DÃ­a 2 Â· PreparaciÃ³n nacional y ejecuciÃ³n de las CER', 'Siku ya 2 Â· Utayari wa nchi na utekelezaji wa kanda'),
                'summary' => $this->t(
                    'Identify what is ready, blocked and needed. Member States identify 1â€“3 priority actions; RECs identify cross-border and regional priorities. A partner session matches needs to solutions. Daily overview; session times to be confirmed.',
                    'Identifier ce qui est prÃªt, bloquÃ© et nÃ©cessaire. Les Ã‰tats membres dÃ©finissent une Ã  trois actions prioritaires ; les CER identifient les prioritÃ©s transfrontaliÃ¨res et rÃ©gionales. Une session des partenaires met en relation besoins et solutions. AperÃ§u de la journÃ©e ; horaires Ã  confirmer.',
                    'ØªØ­Ø¯ÙŠØ¯ Ù…Ø§ Ù‡Ùˆ Ø¬Ø§Ù‡Ø² ÙˆÙ…Ø§ ÙŠÙˆØ§Ø¬Ù‡ Ø¹ÙˆØ§Ø¦Ù‚ ÙˆÙ…Ø§ Ù‡Ùˆ Ù…Ø·Ù„ÙˆØ¨. ØªØ­Ø¯Ø¯ Ø§Ù„Ø¯ÙˆÙ„ Ø§Ù„Ø£Ø¹Ø¶Ø§Ø¡ Ù…Ù† Ø¥Ø¬Ø±Ø§Ø¡ ÙˆØ§Ø­Ø¯ Ø¥Ù„Ù‰ Ø«Ù„Ø§Ø«Ø© Ø¥Ø¬Ø±Ø§Ø¡Ø§Øª Ø°Ø§Øª Ø£ÙˆÙ„ÙˆÙŠØ©ØŒ ÙˆØªØ­Ø¯Ø¯ Ø§Ù„Ù…Ø¬Ù…ÙˆØ¹Ø§Øª Ø§Ù„Ø§Ù‚ØªØµØ§Ø¯ÙŠØ© Ø§Ù„Ø¥Ù‚Ù„ÙŠÙ…ÙŠØ© Ø§Ù„Ø£ÙˆÙ„ÙˆÙŠØ§Øª Ø§Ù„Ø¹Ø§Ø¨Ø±Ø© Ù„Ù„Ø­Ø¯ÙˆØ¯ ÙˆØ§Ù„Ø¥Ù‚Ù„ÙŠÙ…ÙŠØ©. ØªØ±Ø¨Ø· Ø¬Ù„Ø³Ø© Ù„Ù„Ø´Ø±ÙƒØ§Ø¡ Ø§Ù„Ø§Ø­ØªÙŠØ§Ø¬Ø§Øª Ø¨Ø§Ù„Ø­Ù„ÙˆÙ„. Ù†Ø¸Ø±Ø© Ø¹Ø§Ù…Ø© Ø¹Ù„Ù‰ Ø§Ù„ÙŠÙˆÙ…Ø› Ø§Ù„Ù…ÙˆØ§Ø¹ÙŠØ¯ Ù‚ÙŠØ¯ Ø§Ù„ØªØ£ÙƒÙŠØ¯.',
                    'Identificar o que estÃ¡ pronto, bloqueado e necessÃ¡rio. Os Estados-Membros identificam uma a trÃªs aÃ§Ãµes prioritÃ¡rias; as CER identificam prioridades transfronteiriÃ§as e regionais. Uma sessÃ£o de parceiros liga necessidades a soluÃ§Ãµes. VisÃ£o geral do dia; horÃ¡rios a confirmar.',
                    'Identificar quÃ© estÃ¡ listo, quÃ© estÃ¡ bloqueado y quÃ© se necesita. Los Estados miembros identifican de una a tres acciones prioritarias; las CER identifican prioridades transfronterizas y regionales. Una sesiÃ³n de socios vincula necesidades con soluciones. Resumen del dÃ­a; horarios por confirmar.',
                    'Tambua kilicho tayari, kilichokwama na kinachohitajika. Nchi wanachama zinachagua hatua moja hadi tatu za kipaumbele; jumuiya za kiuchumi za kikanda zinatambua vipaumbele vya kuvuka mipaka na vya kanda. Kikao cha washirika kinaunganisha mahitaji na suluhisho. Muhtasari wa siku; saa zitathibitishwa.',
                ),
            ],
            [
                'date' => '2026-09-17',
                'title' => $this->t('Day 3 Â· Implementation delivery labs', 'Jour 3 Â· Laboratoires de mise en Å“uvre', 'Ø§Ù„ÙŠÙˆÙ… Ø§Ù„Ø«Ø§Ù„Ø« Â· Ù…Ø®ØªØ¨Ø±Ø§Øª Ø§Ù„ØªÙ†ÙÙŠØ°', 'Dia 3 Â· LaboratÃ³rios de implementaÃ§Ã£o', 'DÃ­a 3 Â· Laboratorios de ejecuciÃ³n', 'Siku ya 3 Â· Maabara za utekelezaji'),
                'summary' => $this->t(
                    'Hands-on problem-solving around the six Strategic Objectives. Each lab identifies actions, owners, domestic financing and budget routes, support needs and milestones. Daily overview; session times to be confirmed.',
                    'RÃ©solution pratique des problÃ¨mes autour des six objectifs stratÃ©giques. Chaque laboratoire dÃ©finit les actions, les responsables, les voies de financement national et budgÃ©taire, les besoins dâ€™appui et les Ã©tapes clÃ©s. AperÃ§u de la journÃ©e ; horaires des sessions Ã  confirmer.',
                    'Ø­Ù„ Ø§Ù„Ù…Ø´ÙƒÙ„Ø§Øª Ø¹Ù…Ù„ÙŠØ§Ù‹ Ø­ÙˆÙ„ Ø§Ù„Ø£Ù‡Ø¯Ø§Ù Ø§Ù„Ø§Ø³ØªØ±Ø§ØªÙŠØ¬ÙŠØ© Ø§Ù„Ø³ØªØ©. ÙŠØ­Ø¯Ø¯ ÙƒÙ„ Ù…Ø®ØªØ¨Ø± Ø§Ù„Ø¥Ø¬Ø±Ø§Ø¡Ø§Øª ÙˆØ§Ù„Ø¬Ù‡Ø§Øª Ø§Ù„Ù…Ø³Ø¤ÙˆÙ„Ø© ÙˆÙ…Ø³Ø§Ø±Ø§Øª Ø§Ù„ØªÙ…ÙˆÙŠÙ„ Ø§Ù„Ù…Ø­Ù„ÙŠ ÙˆØ§Ù„Ù…ÙŠØ²Ø§Ù†ÙŠØ© ÙˆØ§Ø­ØªÙŠØ§Ø¬Ø§Øª Ø§Ù„Ø¯Ø¹Ù… ÙˆØ§Ù„Ù…Ø¹Ø§Ù„Ù… Ø§Ù„Ù…Ø±Ø­Ù„ÙŠØ©. Ù†Ø¸Ø±Ø© Ø¹Ø§Ù…Ø© Ø¹Ù„Ù‰ Ø§Ù„ÙŠÙˆÙ…Ø› Ù…ÙˆØ§Ø¹ÙŠØ¯ Ø§Ù„Ø¬Ù„Ø³Ø§Øª Ù‚ÙŠØ¯ Ø§Ù„ØªØ£ÙƒÙŠØ¯.',
                    'ResoluÃ§Ã£o prÃ¡tica de problemas em torno dos seis Objetivos EstratÃ©gicos. Cada laboratÃ³rio identifica aÃ§Ãµes, responsÃ¡veis, vias de financiamento interno e orÃ§amental, necessidades de apoio e marcos. VisÃ£o geral do dia; horÃ¡rios das sessÃµes a confirmar.',
                    'ResoluciÃ³n prÃ¡ctica de problemas en torno a los seis Objetivos EstratÃ©gicos. Cada laboratorio identifica acciones, responsables, vÃ­as de financiaciÃ³n nacional y presupuestaria, necesidades de apoyo e hitos. Resumen del dÃ­a; horarios de las sesiones por confirmar.',
                    'Kutatua changamoto kwa vitendo kuhusu Malengo sita ya Kimkakati. Kila maabara inatambua hatua, wahusika, njia za ufadhili wa ndani na bajeti, mahitaji ya msaada na hatua muhimu. Muhtasari wa siku; saa za vipindi zitathibitishwa.',
                ),
            ],
            [
                'date' => '2026-09-18',
                'title' => $this->t('Day 4 Â· Investment, accountability & follow-up', 'Jour 4 Â· Investissement, redevabilitÃ© et suivi', 'Ø§Ù„ÙŠÙˆÙ… Ø§Ù„Ø±Ø§Ø¨Ø¹ Â· Ø§Ù„Ø§Ø³ØªØ«Ù…Ø§Ø± ÙˆØ§Ù„Ù…Ø³Ø§Ø¡Ù„Ø© ÙˆØ§Ù„Ù…ØªØ§Ø¨Ø¹Ø©', 'Dia 4 Â· Investimento, responsabilizaÃ§Ã£o e acompanhamento', 'DÃ­a 4 Â· InversiÃ³n, rendiciÃ³n de cuentas y seguimiento', 'Siku ya 4 Â· Uwekezaji, uwajibikaji na ufuatiliaji'),
                'summary' => $this->t(
                    'Align investment pipelines and partner support; validate country compacts and confirm 30-, 90- and 180-day milestones through existing accountability systems. Daily overview; session times to be confirmed.',
                    'Aligner les projets dâ€™investissement et lâ€™appui des partenaires ; valider les pactes nationaux et confirmer les Ã©tapes Ã  30, 90 et 180 jours au moyen des dispositifs de redevabilitÃ© existants. AperÃ§u de la journÃ©e ; horaires des sessions Ã  confirmer.',
                    'Ù…ÙˆØ§Ø¡Ù…Ø© Ù…Ø´Ø±ÙˆØ¹Ø§Øª Ø§Ù„Ø§Ø³ØªØ«Ù…Ø§Ø± Ø§Ù„Ù…Ø±ØªÙ‚Ø¨Ø© ÙˆØ¯Ø¹Ù… Ø§Ù„Ø´Ø±ÙƒØ§Ø¡Ø› ÙˆØ§Ø¹ØªÙ…Ø§Ø¯ Ø§Ù„Ù…ÙˆØ§Ø«ÙŠÙ‚ Ø§Ù„Ù‚Ø·Ø±ÙŠØ© ÙˆØªØ£ÙƒÙŠØ¯ Ø§Ù„Ù…Ø¹Ø§Ù„Ù… Ø§Ù„Ù…Ø±Ø­Ù„ÙŠØ© Ø®Ù„Ø§Ù„ 30 Ùˆ90 Ùˆ180 ÙŠÙˆÙ…Ø§Ù‹ Ø¹Ø¨Ø± Ø£Ù†Ø¸Ù…Ø© Ø§Ù„Ù…Ø³Ø§Ø¡Ù„Ø© Ø§Ù„Ù‚Ø§Ø¦Ù…Ø©. Ù†Ø¸Ø±Ø© Ø¹Ø§Ù…Ø© Ø¹Ù„Ù‰ Ø§Ù„ÙŠÙˆÙ…Ø› Ù…ÙˆØ§Ø¹ÙŠØ¯ Ø§Ù„Ø¬Ù„Ø³Ø§Øª Ù‚ÙŠØ¯ Ø§Ù„ØªØ£ÙƒÙŠØ¯.',
                    'Alinhar os projetos de investimento e o apoio dos parceiros; validar os pactos nacionais e confirmar os marcos a 30, 90 e 180 dias atravÃ©s dos sistemas de responsabilizaÃ§Ã£o existentes. VisÃ£o geral do dia; horÃ¡rios das sessÃµes a confirmar.',
                    'Alinear las carteras de inversiÃ³n y el apoyo de los socios; validar los pactos nacionales y confirmar los hitos a 30, 90 y 180 dÃ­as mediante los sistemas de rendiciÃ³n de cuentas existentes. Resumen del dÃ­a; horarios de las sesiones por confirmar.',
                    'Kuoanisha miradi ya uwekezaji na msaada wa washirika; kuthibitisha makubaliano ya nchi na hatua muhimu za siku 30, 90 na 180 kupitia mifumo iliyopo ya uwajibikaji. Muhtasari wa siku; saa za vipindi zitathibitishwa.',
                ),
            ],
        ];

        foreach ($days as $index => $day) {
            Session::query()->updateOrCreate(['event_id' => $event->id, 'start_at' => $day['date'].' 00:00:00'], [
                'title' => $day['title'],
                'summary' => $day['summary'],
                'speaker_name' => $this->t(''),
                'speaker_role' => $this->t(''),
                'location' => $event->venue,
                'track' => $this->t('CAADP programme overview', 'AperÃ§u du programme PDDAA', 'Ù†Ø¸Ø±Ø© Ø¹Ø§Ù…Ø© Ø¹Ù„Ù‰ Ø¨Ø±Ù†Ø§Ù…Ø¬ CAADP', 'VisÃ£o geral do programa CAADP', 'Resumen del programa CAADP', 'Muhtasari wa programu ya CAADP'),
                'end_at' => null,
                'format' => 'plenary',
                'is_all_day' => true,
                'is_published' => true,
                'sort_order' => $index + 1,
            ]);
        }
    }

    private function seedResources(Event $event): void
    {
        $resources = [
            [
                'file_path' => self::PROGRAMME_PATH,
                'title' => $this->t('CAADP four-day programme overview', 'AperÃ§u du programme PDDAA sur quatre jours', 'Ù†Ø¸Ø±Ø© Ø¹Ø§Ù…Ø© Ø¹Ù„Ù‰ Ø¨Ø±Ù†Ø§Ù…Ø¬ CAADP Ù„Ø£Ø±Ø¨Ø¹Ø© Ø£ÙŠØ§Ù…', 'VisÃ£o geral do programa CAADP de quatro dias', 'Resumen del programa CAADP de cuatro dÃ­as', 'Muhtasari wa programu ya CAADP ya siku nne'),
                'description' => $this->t(
                    'English PDF based on the key-information brief. Daily focus, preparation inputs and follow-up; detailed session times to be confirmed by the conveners.',
                    'PDF en anglais fondÃ© sur la note dâ€™information essentielle. ThÃ¨mes quotidiens, contributions prÃ©paratoires et suivi ; horaires dÃ©taillÃ©s Ã  confirmer par les organisateurs.',
                    'Ù…Ù„Ù PDF Ø¨Ø§Ù„Ø¥Ù†Ø¬Ù„ÙŠØ²ÙŠØ© ÙŠØ³ØªÙ†Ø¯ Ø¥Ù„Ù‰ Ù…ÙˆØ¬Ø² Ø§Ù„Ù…Ø¹Ù„ÙˆÙ…Ø§Øª Ø§Ù„Ø£Ø³Ø§Ø³ÙŠØ©. ÙŠØªØ¶Ù…Ù† Ù…Ø­Ø§ÙˆØ± Ø§Ù„Ø£ÙŠØ§Ù… ÙˆÙ…ØªØ·Ù„Ø¨Ø§Øª Ø§Ù„ØªØ­Ø¶ÙŠØ± ÙˆØ§Ù„Ù…ØªØ§Ø¨Ø¹Ø©Ø› ÙˆÙŠØ¤ÙƒØ¯ Ø§Ù„Ù…Ù†Ø¸Ù…ÙˆÙ† Ù…ÙˆØ§Ø¹ÙŠØ¯ Ø§Ù„Ø¬Ù„Ø³Ø§Øª Ø§Ù„ØªÙØµÙŠÙ„ÙŠØ© Ù„Ø§Ø­Ù‚Ø§Ù‹.',
                    'PDF em inglÃªs baseado na nota de informaÃ§Ã£o essencial. Temas diÃ¡rios, contributos preparatÃ³rios e acompanhamento; horÃ¡rios detalhados a confirmar pelos organizadores.',
                    'PDF en inglÃ©s basado en la nota de informaciÃ³n clave. Temas diarios, aportaciones preparatorias y seguimiento; los organizadores confirmarÃ¡n los horarios detallados.',
                    'PDF ya Kiingereza inayotokana na muhtasari wa taarifa muhimu. Ina mada za kila siku, michango ya maandalizi na ufuatiliaji; waandaaji watathibitisha saa za kina za vipindi.',
                ),
                'category' => 'programme',
                'original_filename' => '22nd_CAADP_PP_Programme_Overview.pdf',
                'mime_type' => 'application/pdf',
            ],
            [
                'file_path' => self::BRIEF_PATH,
                'title' => $this->t('CAADP key-information brief', 'Note dâ€™information essentielle du PDDAA', 'Ù…ÙˆØ¬Ø² Ø§Ù„Ù…Ø¹Ù„ÙˆÙ…Ø§Øª Ø§Ù„Ø£Ø³Ø§Ø³ÙŠØ© Ù„ÙØ¹Ø§Ù„ÙŠØ© CAADP', 'Nota de informaÃ§Ã£o essencial do CAADP', 'Nota de informaciÃ³n clave de CAADP', 'Muhtasari wa taarifa muhimu za CAADP'),
                'description' => $this->t(
                    'Official English participant information note dated 9 September 2026, covering the venue, accommodation, transport, visas, health, DSA, weather, currency, electricity and event contacts.',
                    'Document Word original fourni en anglais pour la 22e Plateforme de partenariat du PDDAA. Organisateurs : CUA et AUDA-NEPAD.',
                    'ÙˆØ«ÙŠÙ‚Ø© Word Ø§Ù„Ø£ØµÙ„ÙŠØ© Ø§Ù„Ù…Ù‚Ø¯Ù…Ø© Ø¨Ø§Ù„Ø¥Ù†Ø¬Ù„ÙŠØ²ÙŠØ© Ù„Ù…Ù†ØµØ© Ø´Ø±Ø§ÙƒØ© CAADP Ø§Ù„Ø«Ø§Ù†ÙŠØ© ÙˆØ§Ù„Ø¹Ø´Ø±ÙŠÙ†. Ø§Ù„Ù…Ù†Ø¸Ù…Ø§Ù†: Ù…ÙÙˆØ¶ÙŠØ© Ø§Ù„Ø§ØªØ­Ø§Ø¯ Ø§Ù„Ø¥ÙØ±ÙŠÙ‚ÙŠ ÙˆÙˆÙƒØ§Ù„Ø© Ø§Ù„Ø§ØªØ­Ø§Ø¯ Ø§Ù„Ø¥ÙØ±ÙŠÙ‚ÙŠ Ù„Ù„ØªÙ†Ù…ÙŠØ©-Ù†ÙŠØ¨Ø§Ø¯.',
                    'Documento Word original fornecido em inglÃªs para a 22.Âª Plataforma de Parceria CAADP. Organizadores: CUA e AUDA-NEPAD.',
                    'Documento Word original facilitado en inglÃ©s para la 22.Âª Plataforma de AsociaciÃ³n CAADP. Organizadores: CUA y AUDA-NEPAD.',
                    'Waraka asili wa Word uliotolewa kwa Kiingereza kwa Jukwaa la 22 la Ushirikiano wa CAADP. Waandaaji: Tume ya Umoja wa Afrika na AUDA-NEPAD.',
                ),
                'category' => 'brief',
                'original_filename' => 'Information_Note_22nd_CAADP_PP_Zimbabwe_2026.pdf',
                'mime_type' => 'application/pdf',
            ],
        ];

        foreach ($resources as $index => $resource) {
            EventResource::query()->updateOrCreate(['event_id' => $event->id, 'category' => $resource['category']], array_merge($resource, [
                'language' => 'en',
                'file_size' => Storage::disk('local')->size($resource['file_path']),
                'is_published' => true,
                'sort_order' => $index + 1,
            ]));
        }
    }

    private function seedPartnerUpdate(Event $event): void
    {
        $body = $this->t(
            'PARTNER EVENT UPDATE. The supplied key-information brief identifies the 22nd CAADP Partnership Platform as an in-person convening in Harare, Zimbabwe, on 15â€“18 September 2026. Conveners are the African Union Commission (AUC) and AUDA-NEPAD, with Member States, regional economic communities, technical institutions and development partners. The four-day journey covers political direction and CAADP strategy; country readiness and REC delivery; implementation delivery labs; and investment, accountability and follow-up. Interpretation is listed in Arabic, English, French and Portuguese. The brief does not specify the venue within Harare, detailed session times or a public registration link. Participants should follow the convenersâ€™ instructions. Source: the supplied 22nd_CAADP_PP_BRIEF_Key_Information.docx. This participant-information summary and the downloadable programme overview are based on that brief.',
            'ACTUALITÃ‰ Dâ€™UN Ã‰VÃ‰NEMENT PARTENAIRE. La note dâ€™information fournie annonce la 22e Plateforme de partenariat du PDDAA en prÃ©sentiel Ã  Harare, au Zimbabwe, du 15 au 18 septembre 2026. Elle est organisÃ©e par la Commission de lâ€™Union africaine (CUA) et lâ€™AUDA-NEPAD avec les Ã‰tats membres, les communautÃ©s Ã©conomiques rÃ©gionales, les institutions techniques et les partenaires de dÃ©veloppement. Les quatre journÃ©es portent sur lâ€™orientation politique et la stratÃ©gie PDDAA ; la prÃ©paration nationale et la mise en Å“uvre des CER ; les laboratoires de mise en Å“uvre ; puis lâ€™investissement, la redevabilitÃ© et le suivi. Lâ€™interprÃ©tation est indiquÃ©e en arabe, anglais, franÃ§ais et portugais. La note ne prÃ©cise ni le lieu Ã  Harare, ni les horaires dÃ©taillÃ©s, ni un lien dâ€™inscription public. Les participants doivent suivre les instructions des organisateurs. Source : le document fourni 22nd_CAADP_PP_BRIEF_Key_Information.docx. Ce rÃ©sumÃ© et lâ€™aperÃ§u du programme tÃ©lÃ©chargeable reposent sur cette note.',
            'Ù…Ø³ØªØ¬Ø¯Ø§Øª ÙØ¹Ø§Ù„ÙŠØ© Ø´Ø±ÙŠÙƒØ©. ÙŠØ­Ø¯Ø¯ Ù…ÙˆØ¬Ø² Ø§Ù„Ù…Ø¹Ù„ÙˆÙ…Ø§Øª Ø§Ù„Ù…Ù‚Ø¯Ù… Ù…Ù†ØµØ© Ø´Ø±Ø§ÙƒØ© CAADP Ø§Ù„Ø«Ø§Ù†ÙŠØ© ÙˆØ§Ù„Ø¹Ø´Ø±ÙŠÙ† Ø¨ÙˆØµÙÙ‡Ø§ Ù„Ù‚Ø§Ø¡Ù‹ Ø­Ø¶ÙˆØ±ÙŠØ§Ù‹ ÙÙŠ Ù‡Ø±Ø§Ø±ÙŠØŒ Ø²ÙŠÙ…Ø¨Ø§Ø¨ÙˆÙŠØŒ Ù…Ù† 15 Ø¥Ù„Ù‰ 18 Ø³Ø¨ØªÙ…Ø¨Ø± 2026. ØªÙ†Ø¸Ù…Ù‡Ø§ Ù…ÙÙˆØ¶ÙŠØ© Ø§Ù„Ø§ØªØ­Ø§Ø¯ Ø§Ù„Ø¥ÙØ±ÙŠÙ‚ÙŠ ÙˆÙˆÙƒØ§Ù„Ø© Ø§Ù„Ø§ØªØ­Ø§Ø¯ Ø§Ù„Ø¥ÙØ±ÙŠÙ‚ÙŠ Ù„Ù„ØªÙ†Ù…ÙŠØ©-Ù†ÙŠØ¨Ø§Ø¯ØŒ Ù…Ø¹ Ø§Ù„Ø¯ÙˆÙ„ Ø§Ù„Ø£Ø¹Ø¶Ø§Ø¡ ÙˆØ§Ù„Ù…Ø¬Ù…ÙˆØ¹Ø§Øª Ø§Ù„Ø§Ù‚ØªØµØ§Ø¯ÙŠØ© Ø§Ù„Ø¥Ù‚Ù„ÙŠÙ…ÙŠØ© ÙˆØ§Ù„Ù…Ø¤Ø³Ø³Ø§Øª Ø§Ù„ÙÙ†ÙŠØ© ÙˆØ´Ø±ÙƒØ§Ø¡ Ø§Ù„ØªÙ†Ù…ÙŠØ©. ØªØªÙ†Ø§ÙˆÙ„ Ø§Ù„Ø£ÙŠØ§Ù… Ø§Ù„Ø£Ø±Ø¨Ø¹Ø© Ø§Ù„ØªÙˆØ¬ÙŠÙ‡ Ø§Ù„Ø³ÙŠØ§Ø³ÙŠ ÙˆØ§Ø³ØªØ±Ø§ØªÙŠØ¬ÙŠØ© CAADPØŒ ÙˆØ¬Ø§Ù‡Ø²ÙŠØ© Ø§Ù„Ø¨Ù„Ø¯Ø§Ù† ÙˆØ§Ù„ØªÙ†ÙÙŠØ° Ø§Ù„Ø¥Ù‚Ù„ÙŠÙ…ÙŠØŒ ÙˆÙ…Ø®ØªØ¨Ø±Ø§Øª Ø§Ù„ØªÙ†ÙÙŠØ°ØŒ Ø«Ù… Ø§Ù„Ø§Ø³ØªØ«Ù…Ø§Ø± ÙˆØ§Ù„Ù…Ø³Ø§Ø¡Ù„Ø© ÙˆØ§Ù„Ù…ØªØ§Ø¨Ø¹Ø©. Ø§Ù„ØªØ±Ø¬Ù…Ø© Ø§Ù„ÙÙˆØ±ÙŠØ© Ù…Ø¯Ø±Ø¬Ø© Ø¨Ø§Ù„Ø¹Ø±Ø¨ÙŠØ© ÙˆØ§Ù„Ø¥Ù†Ø¬Ù„ÙŠØ²ÙŠØ© ÙˆØ§Ù„ÙØ±Ù†Ø³ÙŠØ© ÙˆØ§Ù„Ø¨Ø±ØªØºØ§Ù„ÙŠØ©. Ù„Ø§ ÙŠØ­Ø¯Ø¯ Ø§Ù„Ù…ÙˆØ¬Ø² Ø§Ù„Ù…ÙƒØ§Ù† Ø¯Ø§Ø®Ù„ Ù‡Ø±Ø§Ø±ÙŠ Ø£Ùˆ Ù…ÙˆØ§Ø¹ÙŠØ¯ Ø§Ù„Ø¬Ù„Ø³Ø§Øª Ø§Ù„ØªÙØµÙŠÙ„ÙŠØ© Ø£Ùˆ Ø±Ø§Ø¨Ø·Ø§Ù‹ Ø¹Ø§Ù…Ø§Ù‹ Ù„Ù„ØªØ³Ø¬ÙŠÙ„. ÙŠÙ†Ø¨ØºÙŠ Ù„Ù„Ù…Ø´Ø§Ø±ÙƒÙŠÙ† Ø§ØªØ¨Ø§Ø¹ ØªØ¹Ù„ÙŠÙ…Ø§Øª Ø§Ù„Ù…Ù†Ø¸Ù…ÙŠÙ†. Ø§Ù„Ù…ØµØ¯Ø±: Ø§Ù„ÙˆØ«ÙŠÙ‚Ø© Ø§Ù„Ù…Ù‚Ø¯Ù…Ø© 22nd_CAADP_PP_BRIEF_Key_Information.docx. ÙŠØ³ØªÙ†Ø¯ Ù‡Ø°Ø§ Ø§Ù„Ù…Ù„Ø®Øµ Ø§Ù„Ù…ÙˆØ¬Ù‡ Ù„Ù„Ù…Ø´Ø§Ø±ÙƒÙŠÙ† ÙˆÙ†Ø¸Ø±Ø© Ø§Ù„Ø¨Ø±Ù†Ø§Ù…Ø¬ Ø§Ù„Ø¹Ø§Ù…Ø© Ø§Ù„Ù…ØªØ§Ø­Ø© Ù„Ù„ØªÙ†Ø²ÙŠÙ„ Ø¥Ù„Ù‰ ØªÙ„Ùƒ Ø§Ù„ÙˆØ«ÙŠÙ‚Ø©.',
            'ATUALIZAÃ‡ÃƒO DE EVENTO PARCEIRO. A nota de informaÃ§Ã£o fornecida apresenta a 22.Âª Plataforma de Parceria CAADP como um encontro presencial em Harare, ZimbabuÃ©, de 15 a 18 de setembro de 2026. Os organizadores sÃ£o a ComissÃ£o da UniÃ£o Africana e a AUDA-NEPAD, com Estados-Membros, comunidades econÃ³micas regionais, instituiÃ§Ãµes tÃ©cnicas e parceiros de desenvolvimento. Os quatro dias abrangem orientaÃ§Ã£o polÃ­tica e estratÃ©gia CAADP; preparaÃ§Ã£o nacional e implementaÃ§Ã£o das CER; laboratÃ³rios de implementaÃ§Ã£o; e investimento, responsabilizaÃ§Ã£o e acompanhamento. EstÃ¡ indicada interpretaÃ§Ã£o em Ã¡rabe, inglÃªs, francÃªs e portuguÃªs. A nota nÃ£o especifica o local em Harare, horÃ¡rios detalhados ou ligaÃ§Ã£o pÃºblica de inscriÃ§Ã£o. Os participantes devem seguir as instruÃ§Ãµes dos organizadores. Fonte: o documento fornecido 22nd_CAADP_PP_BRIEF_Key_Information.docx. Este resumo para participantes e a visÃ£o geral do programa para descarregamento baseiam-se nessa nota.',
            'ACTUALIZACIÃ“N DE EVENTO DE SOCIOS. La nota facilitada presenta la 22.Âª Plataforma de AsociaciÃ³n CAADP como un encuentro presencial en Harare, Zimbabue, del 15 al 18 de septiembre de 2026. Convocan la ComisiÃ³n de la UniÃ³n Africana y AUDA-NEPAD, con los Estados miembros, comunidades econÃ³micas regionales, instituciones tÃ©cnicas y socios de desarrollo. Los cuatro dÃ­as abarcan orientaciÃ³n polÃ­tica y estrategia CAADP; preparaciÃ³n nacional y ejecuciÃ³n de las CER; laboratorios de ejecuciÃ³n; e inversiÃ³n, rendiciÃ³n de cuentas y seguimiento. Se indica interpretaciÃ³n en Ã¡rabe, inglÃ©s, francÃ©s y portuguÃ©s. La nota no especifica el lugar en Harare, los horarios detallados ni un enlace pÃºblico de inscripciÃ³n. Los participantes deben seguir las instrucciones de los organizadores. Fuente: el documento facilitado 22nd_CAADP_PP_BRIEF_Key_Information.docx. Este resumen para participantes y el programa general descargable se basan en esa nota.',
            'TAARIFA YA TUKIO LA WASHIRIKA. Muhtasari uliotolewa unaeleza Jukwaa la 22 la Ushirikiano wa CAADP kama mkutano wa ana kwa ana huko Harare, Zimbabwe, tarehe 15â€“18 Septemba 2026. Waandaaji ni Tume ya Umoja wa Afrika na AUDA-NEPAD, pamoja na nchi wanachama, jumuiya za kiuchumi za kikanda, taasisi za kiufundi na washirika wa maendeleo. Siku nne zinahusu mwelekeo wa kisiasa na mkakati wa CAADP; utayari wa nchi na utekelezaji wa kanda; maabara za utekelezaji; na uwekezaji, uwajibikaji na ufuatiliaji. Ukalimani umeorodheshwa kwa Kiarabu, Kiingereza, Kifaransa na Kireno. Muhtasari hautaji ukumbi ndani ya Harare, saa za kina au kiungo cha usajili wa umma. Washiriki wanapaswa kufuata maelekezo ya waandaaji. Chanzo: waraka uliotolewa 22nd_CAADP_PP_BRIEF_Key_Information.docx. Muhtasari huu wa taarifa za washiriki na programu ya jumla inayopakuliwa vinatokana na waraka huo.',
        );
        $eventLabels = $this->t('Event information', 'Informations sur lâ€™Ã©vÃ©nement', 'Ù…Ø¹Ù„ÙˆÙ…Ø§Øª Ø§Ù„ÙØ¹Ø§Ù„ÙŠØ©', 'InformaÃ§Ãµes do evento', 'InformaciÃ³n del evento', 'Taarifa za tukio');
        $resourceLabels = $this->t('Programme and brief downloads', 'TÃ©lÃ©chargement du programme et de la note', 'ØªÙ†Ø²ÙŠÙ„ Ø§Ù„Ø¨Ø±Ù†Ø§Ù…Ø¬ ÙˆØ§Ù„Ù…ÙˆØ¬Ø²', 'Descarregar programa e nota', 'Descargar programa y nota', 'Pakua programu na muhtasari');

        foreach ($body as $locale => $text) {
            $body[$locale] = $text."\n\n".$eventLabels[$locale].': '.route('events.show', [$locale, $event->slug], false)
                ."\n".$resourceLabels[$locale].': '.route('resources.index', $locale, false);
        }

        $post = NewsPost::query()->firstOrNew(['slug' => '22nd-caadp-partnership-platform-participant-information']);
        $post->fill([
            'title' => $this->t('22nd CAADP Partnership Platform: participant information', '22e Plateforme de partenariat du PDDAA : informations aux participants', 'Ù…Ù†ØµØ© Ø´Ø±Ø§ÙƒØ© CAADP Ø§Ù„Ø«Ø§Ù†ÙŠØ© ÙˆØ§Ù„Ø¹Ø´Ø±ÙˆÙ†: Ù…Ø¹Ù„ÙˆÙ…Ø§Øª Ø§Ù„Ù…Ø´Ø§Ø±ÙƒÙŠÙ†', '22.Âª Plataforma de Parceria CAADP: informaÃ§Ãµes aos participantes', '22.Âª Plataforma de AsociaciÃ³n CAADP: informaciÃ³n para participantes', 'Jukwaa la 22 la Ushirikiano wa CAADP: taarifa kwa washiriki'),
            'category' => $this->t('Partner event update', 'ActualitÃ© dâ€™un Ã©vÃ©nement partenaire', 'Ù…Ø³ØªØ¬Ø¯Ø§Øª ÙØ¹Ø§Ù„ÙŠØ© Ø´Ø±ÙŠÙƒØ©', 'AtualizaÃ§Ã£o de evento parceiro', 'ActualizaciÃ³n de evento de socios', 'Taarifa ya tukio la washirika'),
            'excerpt' => $this->t('Dates, conveners, interpretation and the four-day delivery journey, drawn from the supplied CAADP key-information brief.', 'Dates, organisateurs, interprÃ©tation et parcours de quatre jours, dâ€™aprÃ¨s la note dâ€™information PDDAA fournie.', 'Ø§Ù„ØªÙˆØ§Ø±ÙŠØ® ÙˆØ§Ù„Ø¬Ù‡Ø§Øª Ø§Ù„Ù…Ù†Ø¸Ù…Ø© ÙˆÙ„ØºØ§Øª Ø§Ù„ØªØ±Ø¬Ù…Ø© ÙˆÙ…Ø³Ø§Ø± Ø§Ù„ØªÙ†ÙÙŠØ° Ù„Ø£Ø±Ø¨Ø¹Ø© Ø£ÙŠØ§Ù…ØŒ Ø§Ø³ØªÙ†Ø§Ø¯Ø§Ù‹ Ø¥Ù„Ù‰ Ù…ÙˆØ¬Ø² Ù…Ø¹Ù„ÙˆÙ…Ø§Øª CAADP Ø§Ù„Ù…Ù‚Ø¯Ù….', 'Datas, organizadores, interpretaÃ§Ã£o e percurso de quatro dias, com base na nota de informaÃ§Ã£o CAADP fornecida.', 'Fechas, organizadores, interpretaciÃ³n y recorrido de cuatro dÃ­as, segÃºn la nota de informaciÃ³n CAADP facilitada.', 'Tarehe, waandaaji, ukalimani na mpango wa siku nne, kutoka muhtasari wa taarifa muhimu za CAADP uliotolewa.'),
            'body' => $body,
            'image' => $event->image,
            'is_featured' => false,
            'is_published' => true,
        ]);
        $post->published_at ??= now();
        $post->save();
    }

    /**
     * @param  class-string<Model>  $modelClass
     * @param  array<string, mixed>  $attributes
     */
    private function saveTranslatedRecord(string $modelClass, string $field, array $attributes): void
    {
        $record = $modelClass::query()->where($field.'->en', $attributes[$field]['en'])->first() ?? new $modelClass;
        $record->fill($attributes)->save();
    }

    private function updateEnglishFaqAnswer(string $question, string $answer): void
    {
        $faq = Faq::query()->where('question->en', $question)->first();

        if ($faq === null) {
            return;
        }

        $answers = $faq->answer;
        $answers['en'] = $answer;
        $faq->update(['answer' => $answers]);
    }

    /**
     * @return array{en: string, fr: string, ar: string, pt: string, es: string, sw: string}
     */
    private function t(string $english, ?string $french = null, ?string $arabic = null, ?string $portuguese = null, ?string $spanish = null, ?string $swahili = null): array
    {
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
