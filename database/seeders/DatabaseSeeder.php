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
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->seedAdmin();
        $this->seedSlides();
        $this->seedPrograms();

        $events = $this->seedEvents();

        $this->seedSessions($events);
        $this->seedNews();
        $this->seedFaqs();
        $this->seedAboutPage();
        $this->seedHomeSections();
        $this->seedSettings();
    }

    private function seedAdmin(): void
    {
        $name = env('ADMIN_NAME');
        $email = env('ADMIN_EMAIL');
        $password = env('ADMIN_PASSWORD');

        if (! is_string($name) || trim($name) === '' ||
            ! is_string($email) || trim($email) === '' ||
            ! is_string($password) || trim($password) === '') {
            return;
        }

        User::query()->updateOrCreate(
            ['email' => Str::lower(trim($email))],
            [
                'name' => trim($name),
                'password' => $password,
                'email_verified_at' => now(),
                'is_admin' => true,
                'is_active' => true,
            ],
        );
    }

    private function seedSlides(): void
    {
        $slides = [
            [
                'eyebrow' => $this->translations(
                    'Pan-African events and programmes',
                    'Événements et programmes panafricains',
                    'الفعاليات والبرامج الإفريقية',
                    'Eventos e programas pan-africanos',
                    'Eventos y programas panafricanos',
                    'Matukio na programu za Afrika',
                ),
                'title' => $this->translations(
                    'Ideas that move a continent forward',
                    'Des idées qui font avancer un continent',
                    'أفكار تدفع القارة إلى الأمام',
                    'Ideias que fazem um continente avançar',
                    'Ideas que impulsan a un continente',
                    'Mawazo yanayoisukuma bara mbele',
                ),
                'subtitle' => $this->translations(
                    'Meet the leaders, practitioners and communities turning shared ambition into practical action.',
                    'Rencontrez les dirigeants, praticiens et communautés qui transforment une ambition commune en actions concrètes.',
                    'التقِ بالقادة والممارسين والمجتمعات الذين يحولون الطموح المشترك إلى عمل ملموس.',
                    'Conheça líderes, profissionais e comunidades que transformam ambição partilhada em ação concreta.',
                    'Conozca a líderes, profesionales y comunidades que convierten la ambición compartida en acción concreta.',
                    'Kutana na viongozi, wataalamu na jamii wanaogeuza azma ya pamoja kuwa hatua halisi.',
                ),
                'button_text' => $this->translations(
                    'Explore upcoming events',
                    'Découvrir les prochains événements',
                    'استكشف الفعاليات القادمة',
                    'Explorar próximos eventos',
                    'Explorar próximos eventos',
                    'Tazama matukio yajayo',
                ),
                'button_url' => '/events',
                'image' => '/images/hero-summit.png',
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'eyebrow' => $this->translations(
                    '2026 flagship gathering',
                    'Rencontre phare 2026',
                    'الملتقى الرئيسي لعام 2026',
                    'Encontro principal de 2026',
                    'Encuentro principal de 2026',
                    'Mkutano mkuu wa 2026',
                ),
                'title' => $this->translations(
                    'Build partnerships that outlast the programme',
                    'Créer des partenariats qui dépassent le programme',
                    'ابنِ شراكات تدوم بعد انتهاء البرنامج',
                    'Criar parcerias que perduram além do programa',
                    'Crear alianzas que perduren más allá del programa',
                    'Jenga ushirikiano unaodumu zaidi ya programu',
                ),
                'subtitle' => $this->translations(
                    'Two days of candid dialogue, practical workshops and new continental collaborations.',
                    'Deux jours de dialogue franc, d’ateliers pratiques et de nouvelles collaborations continentales.',
                    'يومان من الحوار الصريح وورش العمل العملية والتعاون القاري الجديد.',
                    'Dois dias de diálogo aberto, oficinas práticas e novas colaborações continentais.',
                    'Dos días de diálogo franco, talleres prácticos y nuevas colaboraciones continentales.',
                    'Siku mbili za mazungumzo ya wazi, warsha za vitendo na ushirikiano mpya wa bara.',
                ),
                'button_text' => $this->translations(
                    'View the programme',
                    'Voir le programme',
                    'عرض البرنامج',
                    'Ver o programa',
                    'Ver el programa',
                    'Tazama programu',
                ),
                'button_url' => '/program-outline',
                'image' => '/images/hero-plenary.png',
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'eyebrow' => $this->translations(
                    'Knowledge in action',
                    'Le savoir en action',
                    'المعرفة في الميدان',
                    'Conhecimento em ação',
                    'Conocimiento en acción',
                    'Maarifa kwa vitendo',
                ),
                'title' => $this->translations(
                    'Turn every session into shared progress',
                    'Transformer chaque session en progrès partagé',
                    'حوّل كل جلسة إلى تقدم مشترك',
                    'Transformar cada sessão em progresso partilhado',
                    'Convertir cada sesión en progreso compartido',
                    'Geuza kila kikao kuwa maendeleo ya pamoja',
                ),
                'subtitle' => $this->translations(
                    'Follow live conversations, revisit key insights and connect with the people behind the work.',
                    'Suivez les échanges en direct, retrouvez les idées clés et échangez avec celles et ceux qui font avancer le travail.',
                    'تابع الحوارات المباشرة، واستعد أهم الأفكار، وتواصل مع القائمين على العمل.',
                    'Acompanhe conversas ao vivo, reveja ideias-chave e ligue-se às pessoas que fazem o trabalho acontecer.',
                    'Siga conversaciones en directo, recupere ideas clave y conecte con quienes impulsan el trabajo.',
                    'Fuatilia mazungumzo mubashara, rejea hoja muhimu na ungana na watu wanaoendesha kazi.',
                ),
                'button_text' => $this->translations(
                    'Browse sessions',
                    'Parcourir les sessions',
                    'تصفح الجلسات',
                    'Explorar sessões',
                    'Explorar sesiones',
                    'Vinjari vikao',
                ),
                'button_url' => '/program-outline#sessions',
                'image' => '/images/hero-innovation.png',
                'is_active' => true,
                'sort_order' => 3,
            ],
        ];

        foreach ($slides as $slide) {
            Slide::query()->updateOrCreate(
                ['sort_order' => $slide['sort_order']],
                $slide,
            );
        }
    }

    private function seedPrograms(): void
    {
        $programs = [
            [
                'slug' => 'leadership-governance',
                'title' => $this->translations(
                    'Leadership & Governance',
                    'Leadership et gouvernance',
                    'القيادة والحوكمة',
                    'Liderança e governação',
                    'Liderazgo y gobernanza',
                    'Uongozi na utawala',
                ),
                'excerpt' => $this->translations(
                    'Stronger institutions built around trust, delivery and public value.',
                    'Des institutions plus fortes fondées sur la confiance, les résultats et la valeur publique.',
                    'مؤسسات أقوى تقوم على الثقة والإنجاز والقيمة العامة.',
                    'Instituições mais fortes, assentes na confiança, execução e valor público.',
                    'Instituciones más sólidas basadas en la confianza, la ejecución y el valor público.',
                    'Taasisi imara zinazojengwa juu ya uaminifu, utekelezaji na thamani kwa umma.',
                ),
                'body' => $this->translations(
                    'Exchange practical approaches to accountable leadership, capable public institutions and policies designed with citizens.',
                    'Échangez des approches pratiques pour un leadership responsable, des institutions publiques compétentes et des politiques conçues avec les citoyens.',
                    'تبادل نهج عملية للقيادة الخاضعة للمساءلة والمؤسسات العامة الفاعلة والسياسات المصممة بمشاركة المواطنين.',
                    'Partilhe abordagens práticas para liderança responsável, instituições públicas capazes e políticas concebidas com os cidadãos.',
                    'Comparta enfoques prácticos para un liderazgo responsable, instituciones públicas capaces y políticas diseñadas con la ciudadanía.',
                    'Badilishana mbinu za uongozi wenye uwajibikaji, taasisi za umma zenye uwezo na sera zinazobuniwa pamoja na wananchi.',
                ),
                'icon' => 'compass',
                'sort_order' => 1,
                'is_published' => true,
            ],
            [
                'slug' => 'peace-resilience',
                'title' => $this->translations(
                    'Peace & Resilience',
                    'Paix et résilience',
                    'السلام والقدرة على الصمود',
                    'Paz e resiliência',
                    'Paz y resiliencia',
                    'Amani na ustahimilivu',
                ),
                'excerpt' => $this->translations(
                    'Locally led pathways to prevention, recovery and lasting peace.',
                    'Des voies portées localement vers la prévention, le relèvement et une paix durable.',
                    'مسارات تقودها المجتمعات المحلية للوقاية والتعافي والسلام الدائم.',
                    'Caminhos liderados localmente para a prevenção, recuperação e paz duradoura.',
                    'Vías lideradas localmente hacia la prevención, la recuperación y una paz duradera.',
                    'Njia zinazoongozwa na wenyeji za kuzuia migogoro, kupona na kujenga amani ya kudumu.',
                ),
                'body' => $this->translations(
                    'Connect mediators, civic leaders and institutions around early action, inclusive dialogue and community resilience.',
                    'Réunissez médiateurs, responsables civiques et institutions autour de l’action précoce, du dialogue inclusif et de la résilience communautaire.',
                    'اربط الوسطاء والقادة المدنيين والمؤسسات حول العمل المبكر والحوار الشامل وصمود المجتمعات.',
                    'Ligue mediadores, líderes cívicos e instituições em torno da ação precoce, diálogo inclusivo e resiliência comunitária.',
                    'Conecte a mediadores, líderes cívicos e instituciones en torno a la acción temprana, el diálogo inclusivo y la resiliencia comunitaria.',
                    'Unganisha wapatanishi, viongozi wa kiraia na taasisi kuhusu hatua za mapema, mazungumzo jumuishi na ustahimilivu wa jamii.',
                ),
                'icon' => 'bridge',
                'sort_order' => 2,
                'is_published' => true,
            ],
            [
                'slug' => 'trade-innovation',
                'title' => $this->translations(
                    'Trade & Innovation',
                    'Commerce et innovation',
                    'التجارة والابتكار',
                    'Comércio e inovação',
                    'Comercio e innovación',
                    'Biashara na uvumbuzi',
                ),
                'excerpt' => $this->translations(
                    'Connected markets and African solutions ready to scale.',
                    'Des marchés connectés et des solutions africaines prêtes à changer d’échelle.',
                    'أسواق مترابطة وحلول إفريقية جاهزة للتوسع.',
                    'Mercados ligados e soluções africanas prontas para ganhar escala.',
                    'Mercados conectados y soluciones africanas listas para crecer.',
                    'Masoko yaliyounganishwa na suluhisho za Kiafrika zilizo tayari kupanuka.',
                ),
                'body' => $this->translations(
                    'Explore digital trade, cross-border infrastructure and investment partnerships that help African enterprises grow.',
                    'Explorez le commerce numérique, les infrastructures transfrontalières et les partenariats d’investissement qui font grandir les entreprises africaines.',
                    'استكشف التجارة الرقمية والبنية التحتية العابرة للحدود وشراكات الاستثمار التي تساعد المؤسسات الإفريقية على النمو.',
                    'Explore o comércio digital, infraestruturas transfronteiriças e parcerias de investimento que ajudam empresas africanas a crescer.',
                    'Explore el comercio digital, la infraestructura transfronteriza y las alianzas de inversión que ayudan a crecer a las empresas africanas.',
                    'Chunguza biashara ya kidijitali, miundombinu ya kuvuka mipaka na ushirikiano wa uwekezaji unaosaidia biashara za Afrika kukua.',
                ),
                'icon' => 'spark',
                'sort_order' => 3,
                'is_published' => true,
            ],
            [
                'slug' => 'youth-climate',
                'title' => $this->translations(
                    'Youth & Climate Action',
                    'Jeunesse et action climatique',
                    'الشباب والعمل المناخي',
                    'Juventude e ação climática',
                    'Juventud y acción climática',
                    'Vijana na hatua za tabianchi',
                ),
                'excerpt' => $this->translations(
                    'A platform for the generation designing Africa’s resilient future.',
                    'Une plateforme pour la génération qui conçoit l’avenir résilient de l’Afrique.',
                    'منصة للجيل الذي يصمم مستقبل إفريقيا القادر على الصمود.',
                    'Uma plataforma para a geração que desenha o futuro resiliente de África.',
                    'Una plataforma para la generación que diseña el futuro resiliente de África.',
                    'Jukwaa la kizazi kinachobuni mustakabali wa Afrika wenye ustahimilivu.',
                ),
                'body' => $this->translations(
                    'Back youth leadership, climate entrepreneurship and practical solutions rooted in local knowledge.',
                    'Soutenez le leadership des jeunes, l’entrepreneuriat climatique et des solutions concrètes ancrées dans les savoirs locaux.',
                    'ادعم قيادة الشباب وريادة الأعمال المناخية والحلول العملية المتجذرة في المعرفة المحلية.',
                    'Apoie a liderança jovem, o empreendedorismo climático e soluções práticas enraizadas no conhecimento local.',
                    'Impulse el liderazgo juvenil, el emprendimiento climático y soluciones prácticas basadas en el conocimiento local.',
                    'Saidia uongozi wa vijana, ujasiriamali wa tabianchi na suluhisho za vitendo zinazotokana na maarifa ya wenyeji.',
                ),
                'icon' => 'leaf',
                'sort_order' => 4,
                'is_published' => true,
            ],
        ];

        foreach ($programs as $program) {
            Program::query()->updateOrCreate(
                ['slug' => $program['slug']],
                $program,
            );
        }
    }

    /**
     * @return array<string, Event>
     */
    private function seedEvents(): array
    {
        $anchor = CarbonImmutable::now()->startOfDay();
        $events = [
            [
                'slug' => 'pan-african-leadership-summit',
                'title' => $this->translations(
                    'Pan-African Leadership Summit',
                    'Sommet panafricain du leadership',
                    'قمة القيادة الإفريقية',
                    'Cimeira Pan-Africana de Liderança',
                    'Cumbre Panafricana de Liderazgo',
                    'Mkutano wa Uongozi wa Afrika',
                ),
                'excerpt' => $this->translations(
                    'A two-day gathering for leaders turning continental commitments into measurable public value.',
                    'Une rencontre de deux jours pour les dirigeants qui transforment les engagements continentaux en valeur publique mesurable.',
                    'ملتقى ليومين للقادة الذين يحولون الالتزامات القارية إلى قيمة عامة قابلة للقياس.',
                    'Um encontro de dois dias para líderes que transformam compromissos continentais em valor público mensurável.',
                    'Un encuentro de dos días para líderes que convierten compromisos continentales en valor público medible.',
                    'Mkutano wa siku mbili kwa viongozi wanaogeuza ahadi za bara kuwa thamani ya umma inayopimika.',
                ),
                'body' => $this->translations(
                    'Senior public leaders, civil society and delivery partners will work through practical cases on trust, implementation and cross-border cooperation. Participants leave with peer connections and a focused ninety-day action plan.',
                    'De hauts responsables publics, la société civile et les partenaires de mise en œuvre travailleront sur des cas concrets de confiance, d’exécution et de coopération transfrontalière. Chaque participant repartira avec un réseau de pairs et un plan d’action ciblé sur quatre-vingt-dix jours.',
                    'سيعمل كبار القادة العموميين والمجتمع المدني وشركاء التنفيذ على حالات عملية تتعلق بالثقة والتنفيذ والتعاون عبر الحدود. ويغادر المشاركون بروابط مهنية وخطة عمل مركزة لمدة تسعين يوماً.',
                    'Altos dirigentes públicos, sociedade civil e parceiros de execução irão trabalhar casos práticos sobre confiança, implementação e cooperação transfronteiriça. Os participantes sairão com ligações entre pares e um plano de ação focado para noventa dias.',
                    'Altos responsables públicos, sociedad civil y socios de ejecución trabajarán casos prácticos sobre confianza, implementación y cooperación transfronteriza. Los participantes saldrán con conexiones entre pares y un plan de acción de noventa días.',
                    'Viongozi wakuu wa umma, asasi za kiraia na washirika wa utekelezaji watachambua mifano ya vitendo kuhusu uaminifu, utekelezaji na ushirikiano wa mipakani. Washiriki wataondoka na mitandao ya wenzao na mpango mahususi wa siku tisini.',
                ),
                'venue' => $this->translations(
                    'African Union Conference Centre, Addis Ababa',
                    'Centre de conférences de l’Union africaine, Addis-Abeba',
                    'مركز مؤتمرات الاتحاد الإفريقي، أديس أبابا',
                    'Centro de Conferências da União Africana, Adis Abeba',
                    'Centro de Conferencias de la Unión Africana, Adís Abeba',
                    'Kituo cha Mikutano cha Umoja wa Afrika, Addis Ababa',
                ),
                'start_at' => $anchor->addDays(21)->setTime(8, 30),
                'end_at' => $anchor->addDays(22)->setTime(17, 30),
                'mode' => 'hybrid',
                'registration_url' => '/events/pan-african-leadership-summit#register',
                'image' => '/images/hero-summit.png',
                'is_featured' => true,
                'is_published' => true,
            ],
            [
                'slug' => 'women-in-public-leadership-forum',
                'title' => $this->translations(
                    'Women in Public Leadership Forum',
                    'Forum des femmes dans le leadership public',
                    'منتدى المرأة في القيادة العامة',
                    'Fórum de Mulheres na Liderança Pública',
                    'Foro de Mujeres en el Liderazgo Público',
                    'Jukwaa la Wanawake katika Uongozi wa Umma',
                ),
                'excerpt' => $this->translations(
                    'From representation to influence: advancing women’s leadership across public institutions.',
                    'De la représentation à l’influence : faire progresser le leadership des femmes dans les institutions publiques.',
                    'من التمثيل إلى التأثير: تعزيز قيادة المرأة في المؤسسات العامة.',
                    'Da representação à influência: promover a liderança das mulheres nas instituições públicas.',
                    'De la representación a la influencia: impulsar el liderazgo de las mujeres en las instituciones públicas.',
                    'Kutoka uwakilishi hadi ushawishi: kuendeleza uongozi wa wanawake katika taasisi za umma.',
                ),
                'body' => $this->translations(
                    'This focused forum pairs candid intergenerational dialogue with mentoring, institutional reform clinics and a practical network for women leading public change.',
                    'Ce forum ciblé associe un dialogue intergénérationnel franc à du mentorat, des cliniques de réforme institutionnelle et un réseau pratique pour les femmes qui conduisent le changement public.',
                    'يجمع هذا المنتدى المتخصص بين حوار صريح بين الأجيال والإرشاد وعيادات الإصلاح المؤسسي وشبكة عملية للنساء اللواتي يقدن التغيير العام.',
                    'Este fórum combina diálogo intergeracional aberto com mentoria, clínicas de reforma institucional e uma rede prática para mulheres que lideram a mudança pública.',
                    'Este foro combina diálogo intergeneracional franco con mentoría, clínicas de reforma institucional y una red práctica para mujeres que lideran el cambio público.',
                    'Jukwaa hili linaunganisha mazungumzo ya wazi kati ya vizazi, ushauri, kliniki za mageuzi ya taasisi na mtandao wa vitendo kwa wanawake wanaoongoza mabadiliko ya umma.',
                ),
                'venue' => $this->translations(
                    'Kigali Convention Centre, Kigali',
                    'Kigali Convention Centre, Kigali',
                    'مركز كيغالي للمؤتمرات، كيغالي',
                    'Centro de Convenções de Kigali, Kigali',
                    'Centro de Convenciones de Kigali, Kigali',
                    'Kituo cha Mikutano cha Kigali, Kigali',
                ),
                'start_at' => $anchor->addDays(35)->setTime(9, 0),
                'end_at' => $anchor->addDays(35)->setTime(17, 0),
                'mode' => 'in-person',
                'registration_url' => '/events/women-in-public-leadership-forum#register',
                'image' => '/images/hero-plenary.png',
                'is_featured' => false,
                'is_published' => true,
            ],
            [
                'slug' => 'continental-digital-trade-lab',
                'title' => $this->translations(
                    'Continental Digital Trade Lab',
                    'Laboratoire continental du commerce numérique',
                    'مختبر التجارة الرقمية القاري',
                    'Laboratório Continental de Comércio Digital',
                    'Laboratorio Continental de Comercio Digital',
                    'Maabara ya Biashara ya Kidijitali ya Bara',
                ),
                'excerpt' => $this->translations(
                    'A working lab for teams building simpler, safer cross-border digital trade.',
                    'Un laboratoire de travail pour les équipes qui bâtissent un commerce numérique transfrontalier plus simple et plus sûr.',
                    'مختبر عملي للفرق التي تبني تجارة رقمية عابرة للحدود أكثر سهولة وأماناً.',
                    'Um laboratório de trabalho para equipas que constroem um comércio digital transfronteiriço mais simples e seguro.',
                    'Un laboratorio de trabajo para equipos que construyen un comercio digital transfronterizo más simple y seguro.',
                    'Maabara ya vitendo kwa timu zinazojenga biashara ya kidijitali ya mipakani iliyo rahisi na salama zaidi.',
                ),
                'body' => $this->translations(
                    'Regulators, founders, logistics experts and investors will prototype interoperable services, compare live market constraints and identify partnerships ready for implementation.',
                    'Régulateurs, fondateurs, experts de la logistique et investisseurs prototyperont des services interopérables, compareront les contraintes réelles du marché et identifieront des partenariats prêts à être mis en œuvre.',
                    'سيصمم المنظمون والمؤسسون وخبراء اللوجستيات والمستثمرون نماذج لخدمات قابلة للتشغيل البيني، ويقارنون قيود السوق الفعلية، ويحددون شراكات جاهزة للتنفيذ.',
                    'Reguladores, fundadores, especialistas em logística e investidores irão prototipar serviços interoperáveis, comparar restrições reais de mercado e identificar parcerias prontas para implementação.',
                    'Reguladores, fundadores, expertos en logística e inversores crearán prototipos de servicios interoperables, compararán restricciones reales del mercado e identificarán alianzas listas para implementar.',
                    'Wasimamizi, waanzilishi, wataalamu wa usafirishaji na wawekezaji watatengeneza mifano ya huduma zinazoshirikiana, kulinganisha vikwazo halisi vya soko na kubaini ushirikiano ulio tayari kutekelezwa.',
                ),
                'venue' => $this->translations(
                    'Virtual continental studio',
                    'Studio continental virtuel',
                    'الاستوديو القاري الافتراضي',
                    'Estúdio continental virtual',
                    'Estudio continental virtual',
                    'Studio pepe ya bara',
                ),
                'start_at' => $anchor->addDays(52)->setTime(10, 0),
                'end_at' => $anchor->addDays(52)->setTime(16, 30),
                'mode' => 'online',
                'registration_url' => '/events/continental-digital-trade-lab#register',
                'image' => '/images/hero-innovation.png',
                'is_featured' => true,
                'is_published' => true,
            ],
            [
                'slug' => 'youth-climate-innovation-assembly',
                'title' => $this->translations(
                    'Youth Climate Innovation Assembly',
                    'Assemblée des jeunes pour l’innovation climatique',
                    'جمعية الشباب للابتكار المناخي',
                    'Assembleia Juvenil de Inovação Climática',
                    'Asamblea Juvenil de Innovación Climática',
                    'Mkutano wa Vijana wa Ubunifu wa Tabianchi',
                ),
                'excerpt' => $this->translations(
                    'Young innovators, local knowledge and capital aligned around climate solutions that can scale.',
                    'Jeunes innovateurs, savoirs locaux et capitaux réunis autour de solutions climatiques capables de changer d’échelle.',
                    'مبتكرون شباب ومعرفة محلية ورأس مال تتضافر حول حلول مناخية قابلة للتوسع.',
                    'Jovens inovadores, conhecimento local e capital alinhados em torno de soluções climáticas escaláveis.',
                    'Jóvenes innovadores, conocimiento local y capital alineados en torno a soluciones climáticas escalables.',
                    'Wabunifu vijana, maarifa ya wenyeji na mtaji vikiunganishwa kuendeleza suluhisho za tabianchi zinazoweza kupanuka.',
                ),
                'body' => $this->translations(
                    'The assembly combines a solutions studio, community evidence sessions and an investor exchange designed to move promising youth-led climate ventures toward their next milestone.',
                    'L’assemblée réunit un studio de solutions, des sessions de preuves communautaires et un échange avec des investisseurs pour faire progresser des initiatives climatiques prometteuses portées par des jeunes.',
                    'تجمع الجمعية بين استوديو للحلول وجلسات للأدلة المجتمعية وتبادل مع المستثمرين لدفع المشاريع المناخية الواعدة بقيادة الشباب نحو محطتها التالية.',
                    'A assembleia combina um estúdio de soluções, sessões de evidências comunitárias e um intercâmbio com investidores para levar iniciativas climáticas juvenis promissoras ao próximo marco.',
                    'La asamblea combina un estudio de soluciones, sesiones de evidencia comunitaria y un intercambio con inversores para llevar iniciativas climáticas juveniles prometedoras a su siguiente hito.',
                    'Mkutano unaunganisha studio ya suluhisho, vikao vya ushahidi wa jamii na majadiliano na wawekezaji ili kusogeza mbele miradi bora ya tabianchi inayoongozwa na vijana.',
                ),
                'venue' => $this->translations(
                    'Kenyatta International Convention Centre, Nairobi',
                    'Centre international de conférences Kenyatta, Nairobi',
                    'مركز كينياتا الدولي للمؤتمرات، نيروبي',
                    'Centro Internacional de Convenções Kenyatta, Nairobi',
                    'Centro Internacional de Convenciones Kenyatta, Nairobi',
                    'Kituo cha Kimataifa cha Mikutano cha Kenyatta, Nairobi',
                ),
                'start_at' => $anchor->addDays(70)->setTime(8, 30),
                'end_at' => $anchor->addDays(71)->setTime(16, 30),
                'mode' => 'hybrid',
                'registration_url' => '/events/youth-climate-innovation-assembly#register',
                'image' => '/images/hero-innovation.png',
                'is_featured' => false,
                'is_published' => true,
            ],
            [
                'slug' => 'regional-peacebuilders-roundtable',
                'title' => $this->translations(
                    'Regional Peacebuilders Roundtable',
                    'Table ronde régionale des artisans de la paix',
                    'المائدة المستديرة الإقليمية لبناة السلام',
                    'Mesa-Redonda Regional de Construtores da Paz',
                    'Mesa Redonda Regional de Constructores de Paz',
                    'Meza ya Duara ya Kikanda ya Wajenzi wa Amani',
                ),
                'excerpt' => $this->translations(
                    'A practitioner exchange on early action, inclusive mediation and community resilience.',
                    'Un échange de praticiens sur l’action précoce, la médiation inclusive et la résilience communautaire.',
                    'تبادل للخبرات العملية حول العمل المبكر والوساطة الشاملة وصمود المجتمعات.',
                    'Um intercâmbio de profissionais sobre ação precoce, mediação inclusiva e resiliência comunitária.',
                    'Un intercambio profesional sobre acción temprana, mediación inclusiva y resiliencia comunitaria.',
                    'Mabadilishano ya wataalamu kuhusu hatua za mapema, upatanishi jumuishi na ustahimilivu wa jamii.',
                ),
                'body' => $this->translations(
                    'Peacebuilders from across the region will test shared scenarios, document what is working locally and strengthen rapid peer-support channels for moments of emerging risk.',
                    'Des artisans de la paix de toute la région testeront des scénarios communs, documenteront les pratiques locales efficaces et renforceront les mécanismes rapides de soutien entre pairs face aux risques émergents.',
                    'سيختبر بناة السلام من مختلف أنحاء المنطقة سيناريوهات مشتركة، ويوثقون ما ينجح محلياً، ويعززون قنوات الدعم السريع بين الأقران عند ظهور المخاطر.',
                    'Construtores da paz de toda a região irão testar cenários partilhados, documentar práticas locais eficazes e reforçar canais rápidos de apoio entre pares em momentos de risco emergente.',
                    'Constructores de paz de toda la región probarán escenarios compartidos, documentarán prácticas locales eficaces y reforzarán canales rápidos de apoyo entre pares ante riesgos emergentes.',
                    'Wajenzi wa amani kutoka kote kanda watajaribu hali za pamoja, kuandika yanayofanya kazi katika jamii na kuimarisha njia za haraka za kusaidiana hatari zinapojitokeza.',
                ),
                'venue' => $this->translations(
                    'Julius Nyerere International Convention Centre, Dar es Salaam',
                    'Centre international de conférences Julius Nyerere, Dar es Salaam',
                    'مركز جوليوس نيريري الدولي للمؤتمرات، دار السلام',
                    'Centro Internacional de Convenções Julius Nyerere, Dar es Salaam',
                    'Centro Internacional de Convenciones Julius Nyerere, Dar es Salaam',
                    'Kituo cha Kimataifa cha Mikutano cha Julius Nyerere, Dar es Salaam',
                ),
                'start_at' => $anchor->addDays(92)->setTime(9, 0),
                'end_at' => $anchor->addDays(92)->setTime(17, 30),
                'mode' => 'in-person',
                'registration_url' => '/events/regional-peacebuilders-roundtable#register',
                'image' => '/images/hero-summit.png',
                'is_featured' => false,
                'is_published' => true,
            ],
        ];

        $seededEvents = [];

        foreach ($events as $event) {
            $seededEvents[$event['slug']] = Event::query()->updateOrCreate(
                ['slug' => $event['slug']],
                $event,
            );
        }

        return $seededEvents;
    }

    /**
     * @param  array<string, Event>  $events
     */
    private function seedSessions(array $events): void
    {
        $leadershipDate = $events['pan-african-leadership-summit']->start_at->toImmutable()->startOfDay();
        $womenDate = $events['women-in-public-leadership-forum']->start_at->toImmutable()->startOfDay();
        $tradeDate = $events['continental-digital-trade-lab']->start_at->toImmutable()->startOfDay();
        $climateDate = $events['youth-climate-innovation-assembly']->start_at->toImmutable()->startOfDay();

        $sessions = [
            [
                'event_id' => $events['pan-african-leadership-summit']->id,
                'title' => $this->translations('Opening plenary: Africa’s next chapter', 'Plénière d’ouverture : le prochain chapitre de l’Afrique', 'الجلسة الافتتاحية: الفصل القادم لإفريقيا', 'Plenária de abertura: o próximo capítulo de África', 'Plenaria inaugural: el próximo capítulo de África', 'Kikao cha ufunguzi: sura inayofuata ya Afrika'),
                'summary' => $this->translations('A shared reading of the opportunities that demand coordinated leadership now.', 'Une lecture commune des opportunités qui exigent aujourd’hui un leadership coordonné.', 'قراءة مشتركة للفرص التي تتطلب قيادة منسقة الآن.', 'Uma leitura comum das oportunidades que exigem agora liderança coordenada.', 'Una lectura compartida de las oportunidades que exigen ahora un liderazgo coordinado.', 'Mtazamo wa pamoja kuhusu fursa zinazohitaji uongozi ulioratibiwa sasa.'),
                'speaker_name' => $this->allLocales('Dr. Amina Diallo'),
                'speaker_role' => $this->translations('Commissioner for Institutional Development', 'Commissaire au développement institutionnel', 'مفوضة التنمية المؤسسية', 'Comissária para o Desenvolvimento Institucional', 'Comisionada de Desarrollo Institucional', 'Kamishna wa Maendeleo ya Taasisi'),
                'location' => $this->translations('Nelson Mandela Hall', 'Salle Nelson Mandela', 'قاعة نيلسون مانديلا', 'Sala Nelson Mandela', 'Sala Nelson Mandela', 'Ukumbi wa Nelson Mandela'),
                'track' => $this->translations('Leadership', 'Leadership', 'القيادة', 'Liderança', 'Liderazgo', 'Uongozi'),
                'start_at' => $leadershipDate->setTime(9, 0),
                'end_at' => $leadershipDate->setTime(10, 0),
                'format' => 'plenary',
                'sort_order' => 1,
                'is_published' => true,
            ],
            [
                'event_id' => $events['pan-african-leadership-summit']->id,
                'title' => $this->translations('Designing institutions people trust', 'Concevoir des institutions qui inspirent confiance', 'تصميم مؤسسات تحظى بثقة الناس', 'Conceber instituições em que as pessoas confiam', 'Diseñar instituciones en las que la gente confíe', 'Kubuni taasisi zinazoaminiwa na watu'),
                'summary' => $this->translations('Public leaders compare practical reforms that make services more responsive and accountable.', 'Des responsables publics comparent des réformes concrètes qui rendent les services plus réactifs et responsables.', 'يقارن القادة العموميون إصلاحات عملية تجعل الخدمات أكثر استجابة ومساءلة.', 'Dirigentes públicos comparam reformas práticas que tornam os serviços mais responsivos e responsáveis.', 'Responsables públicos comparan reformas prácticas que hacen los servicios más ágiles y responsables.', 'Viongozi wa umma wanalinganisha mageuzi ya vitendo yanayofanya huduma ziwe sikivu na zenye uwajibikaji.'),
                'speaker_name' => $this->allLocales('Prof. Kwame Mensah'),
                'speaker_role' => $this->translations('Director, Centre for Public Value', 'Directeur, Centre pour la valeur publique', 'مدير مركز القيمة العامة', 'Diretor, Centro para o Valor Público', 'Director, Centro para el Valor Público', 'Mkurugenzi, Kituo cha Thamani kwa Umma'),
                'location' => $this->translations('Wangari Maathai Room', 'Salle Wangari Maathai', 'قاعة وانغاري ماثاي', 'Sala Wangari Maathai', 'Sala Wangari Maathai', 'Chumba cha Wangari Maathai'),
                'track' => $this->translations('Governance', 'Gouvernance', 'الحوكمة', 'Governação', 'Gobernanza', 'Utawala'),
                'start_at' => $leadershipDate->setTime(10, 30),
                'end_at' => $leadershipDate->setTime(11, 45),
                'format' => 'panel',
                'sort_order' => 2,
                'is_published' => true,
            ],
            [
                'event_id' => $events['pan-african-leadership-summit']->id,
                'title' => $this->translations('Financing implementation and partnerships', 'Financer la mise en œuvre et les partenariats', 'تمويل التنفيذ والشراكات', 'Financiar a implementação e as parcerias', 'Financiar la implementación y las alianzas', 'Kufadhili utekelezaji na ushirikiano'),
                'summary' => $this->translations('A deal-room conversation on aligning public priorities, development finance and responsible capital.', 'Un échange concret sur l’alignement des priorités publiques, du financement du développement et des capitaux responsables.', 'حوار عملي حول مواءمة الأولويات العامة وتمويل التنمية ورأس المال المسؤول.', 'Uma conversa prática sobre o alinhamento de prioridades públicas, financiamento do desenvolvimento e capital responsável.', 'Una conversación práctica sobre cómo alinear prioridades públicas, financiación del desarrollo y capital responsable.', 'Mazungumzo ya vitendo kuhusu kuoanisha vipaumbele vya umma, fedha za maendeleo na mtaji unaowajibika.'),
                'speaker_name' => $this->allLocales('Lúcia Fernandes'),
                'speaker_role' => $this->translations('Managing Partner, Ubuntu Impact Fund', 'Associée gérante, Ubuntu Impact Fund', 'الشريكة المديرة، صندوق أوبونتو للأثر', 'Sócia-gerente, Ubuntu Impact Fund', 'Socia directora, Ubuntu Impact Fund', 'Mshirika Mkuu, Ubuntu Impact Fund'),
                'location' => $this->translations('Julius Nyerere Studio', 'Studio Julius Nyerere', 'استوديو جوليوس نيريري', 'Estúdio Julius Nyerere', 'Estudio Julius Nyerere', 'Studio ya Julius Nyerere'),
                'track' => $this->translations('Delivery', 'Mise en œuvre', 'التنفيذ', 'Execução', 'Ejecución', 'Utekelezaji'),
                'start_at' => $leadershipDate->setTime(14, 0),
                'end_at' => $leadershipDate->setTime(15, 15),
                'format' => 'roundtable',
                'sort_order' => 3,
                'is_published' => true,
            ],
            [
                'event_id' => $events['women-in-public-leadership-forum']->id,
                'title' => $this->translations('From representation to influence', 'De la représentation à l’influence', 'من التمثيل إلى التأثير', 'Da representação à influência', 'De la representación a la influencia', 'Kutoka uwakilishi hadi ushawishi'),
                'summary' => $this->translations('An intergenerational dialogue on power, voice and institutional change.', 'Un dialogue intergénérationnel sur le pouvoir, la voix et le changement institutionnel.', 'حوار بين الأجيال حول السلطة والصوت والتغيير المؤسسي.', 'Um diálogo intergeracional sobre poder, voz e mudança institucional.', 'Un diálogo intergeneracional sobre poder, voz y cambio institucional.', 'Mazungumzo kati ya vizazi kuhusu mamlaka, sauti na mabadiliko ya taasisi.'),
                'speaker_name' => $this->allLocales('Hon. Thandiwe Moyo'),
                'speaker_role' => $this->translations('Former Minister and Governance Advocate', 'Ancienne ministre et défenseure de la gouvernance', 'وزيرة سابقة ومدافعة عن الحوكمة', 'Antiga ministra e defensora da governação', 'Exministra y defensora de la gobernanza', 'Waziri wa zamani na mtetezi wa utawala'),
                'location' => $this->translations('Main Auditorium', 'Auditorium principal', 'القاعة الرئيسية', 'Auditório principal', 'Auditorio principal', 'Ukumbi Mkuu'),
                'track' => $this->translations('Women’s leadership', 'Leadership des femmes', 'قيادة المرأة', 'Liderança das mulheres', 'Liderazgo de las mujeres', 'Uongozi wa wanawake'),
                'start_at' => $womenDate->setTime(9, 30),
                'end_at' => $womenDate->setTime(10, 45),
                'format' => 'fireside_chat',
                'sort_order' => 1,
                'is_published' => true,
            ],
            [
                'event_id' => $events['women-in-public-leadership-forum']->id,
                'title' => $this->translations('Mentoring clinic: leading through transition', 'Clinique de mentorat : diriger pendant la transition', 'عيادة الإرشاد: القيادة خلال مرحلة الانتقال', 'Clínica de mentoria: liderar durante a transição', 'Clínica de mentoría: liderar durante la transición', 'Kliniki ya ushauri: kuongoza wakati wa mpito'),
                'summary' => $this->translations('Small-group coaching on coalition building, first one hundred days and resilient leadership.', 'Un accompagnement en petits groupes sur la création de coalitions, les cent premiers jours et un leadership résilient.', 'تدريب في مجموعات صغيرة حول بناء التحالفات والمئة يوم الأولى والقيادة المرنة.', 'Acompanhamento em pequenos grupos sobre criação de coligações, primeiros cem dias e liderança resiliente.', 'Acompañamiento en grupos pequeños sobre coaliciones, primeros cien días y liderazgo resiliente.', 'Mafunzo ya vikundi vidogo kuhusu kujenga miungano, siku mia za kwanza na uongozi wenye ustahimilivu.'),
                'speaker_name' => $this->allLocales('Nadia El-Sayed'),
                'speaker_role' => $this->translations('Executive Coach and Public Leadership Fellow', 'Coach exécutive et chercheuse en leadership public', 'مدربة تنفيذية وزميلة في القيادة العامة', 'Coach executiva e investigadora de liderança pública', 'Coach ejecutiva e investigadora de liderazgo público', 'Mkufunzi wa viongozi na mtafiti wa uongozi wa umma'),
                'location' => $this->translations('Umuganda Workshop Room', 'Salle d’atelier Umuganda', 'قاعة ورشة أوموغاندا', 'Sala de oficina Umuganda', 'Sala de taller Umuganda', 'Chumba cha Warsha cha Umuganda'),
                'track' => $this->translations('Professional growth', 'Développement professionnel', 'التطور المهني', 'Desenvolvimento profissional', 'Desarrollo profesional', 'Ukuaji wa kitaaluma'),
                'start_at' => $womenDate->setTime(11, 15),
                'end_at' => $womenDate->setTime(12, 30),
                'format' => 'workshop',
                'sort_order' => 2,
                'is_published' => true,
            ],
            [
                'event_id' => $events['continental-digital-trade-lab']->id,
                'title' => $this->translations('Interoperable markets by design', 'Des marchés interopérables dès la conception', 'أسواق قابلة للتشغيل البيني منذ التصميم', 'Mercados interoperáveis desde a conceção', 'Mercados interoperables desde el diseño', 'Masoko yanayoshirikiana kwa usanifu'),
                'summary' => $this->translations('A practical architecture session on identity, payments, trust and data exchange.', 'Une session d’architecture pratique sur l’identité, les paiements, la confiance et l’échange de données.', 'جلسة عملية حول هندسة الهوية والمدفوعات والثقة وتبادل البيانات.', 'Uma sessão prática de arquitetura sobre identidade, pagamentos, confiança e intercâmbio de dados.', 'Una sesión práctica de arquitectura sobre identidad, pagos, confianza e intercambio de datos.', 'Kikao cha usanifu wa vitendo kuhusu utambulisho, malipo, uaminifu na ubadilishanaji wa data.'),
                'speaker_name' => $this->allLocales('Eng. David Okonkwo'),
                'speaker_role' => $this->translations('Digital Public Infrastructure Lead', 'Responsable des infrastructures publiques numériques', 'رئيس البنية التحتية العامة الرقمية', 'Responsável de Infraestruturas Públicas Digitais', 'Responsable de Infraestructura Pública Digital', 'Kiongozi wa Miundombinu ya Umma ya Kidijitali'),
                'location' => $this->translations('Online Lab A', 'Laboratoire en ligne A', 'المختبر الافتراضي أ', 'Laboratório online A', 'Laboratorio en línea A', 'Maabara ya Mtandaoni A'),
                'track' => $this->translations('Digital infrastructure', 'Infrastructure numérique', 'البنية التحتية الرقمية', 'Infraestrutura digital', 'Infraestructura digital', 'Miundombinu ya kidijitali'),
                'start_at' => $tradeDate->setTime(10, 0),
                'end_at' => $tradeDate->setTime(11, 30),
                'format' => 'technical_lab',
                'sort_order' => 1,
                'is_published' => true,
            ],
            [
                'event_id' => $events['continental-digital-trade-lab']->id,
                'title' => $this->translations('African startup solutions showcase', 'Vitrine des solutions de start-up africaines', 'عرض حلول الشركات الناشئة الإفريقية', 'Mostra de soluções de startups africanas', 'Muestra de soluciones de empresas emergentes africanas', 'Maonyesho ya suluhisho za kampuni changa za Afrika'),
                'summary' => $this->translations('Six founders demonstrate tools reducing friction for traders and small enterprises.', 'Six fondateurs présentent des outils qui réduisent les obstacles pour les commerçants et les petites entreprises.', 'يقدم ستة مؤسسين أدوات تقلل العوائق أمام التجار والمؤسسات الصغيرة.', 'Seis fundadores apresentam ferramentas que reduzem obstáculos para comerciantes e pequenas empresas.', 'Seis fundadores presentan herramientas que reducen barreras para comerciantes y pequeñas empresas.', 'Waanzilishi sita wanaonyesha zana zinazopunguza vikwazo kwa wafanyabiashara na biashara ndogo.'),
                'speaker_name' => $this->allLocales('Mariam Ben Youssef'),
                'speaker_role' => $this->translations('Founder and Venture Studio Director', 'Fondatrice et directrice de studio d’entreprises', 'مؤسسة ومديرة استوديو مشاريع', 'Fundadora e diretora de estúdio de empreendimentos', 'Fundadora y directora de estudio de empresas', 'Mwanzilishi na Mkurugenzi wa Studio ya Biashara'),
                'location' => $this->translations('Online Showcase Stage', 'Scène virtuelle des démonstrations', 'منصة العرض الافتراضية', 'Palco virtual de demonstrações', 'Escenario virtual de demostraciones', 'Jukwaa la Maonyesho Mtandaoni'),
                'track' => $this->translations('Innovation', 'Innovation', 'الابتكار', 'Inovação', 'Innovación', 'Ubunifu'),
                'start_at' => $tradeDate->setTime(13, 0),
                'end_at' => $tradeDate->setTime(14, 15),
                'format' => 'showcase',
                'sort_order' => 2,
                'is_published' => true,
            ],
            [
                'event_id' => $events['youth-climate-innovation-assembly']->id,
                'title' => $this->translations('Climate solutions studio', 'Studio de solutions climatiques', 'استوديو الحلول المناخية', 'Estúdio de soluções climáticas', 'Estudio de soluciones climáticas', 'Studio ya suluhisho za tabianchi'),
                'summary' => $this->translations('Teams refine community-rooted ideas with technical advisers and climate investors.', 'Les équipes affinent des idées ancrées dans les communautés avec des conseillers techniques et des investisseurs climatiques.', 'تطور الفرق أفكاراً متجذرة في المجتمعات بمشاركة مستشارين تقنيين ومستثمرين في المناخ.', 'As equipas aperfeiçoam ideias enraizadas nas comunidades com consultores técnicos e investidores climáticos.', 'Los equipos perfeccionan ideas arraigadas en las comunidades con asesores técnicos e inversores climáticos.', 'Timu zinaboresha mawazo yanayotokana na jamii pamoja na washauri wa kiufundi na wawekezaji wa tabianchi.'),
                'speaker_name' => $this->allLocales('Neema Wanjiku'),
                'speaker_role' => $this->translations('Climate Entrepreneur and Community Designer', 'Entrepreneure climatique et conceptrice communautaire', 'رائدة أعمال مناخية ومصممة مجتمعية', 'Empreendedora climática e designer comunitária', 'Emprendedora climática y diseñadora comunitaria', 'Mjasiriamali wa tabianchi na mbunifu wa jamii'),
                'location' => $this->translations('Karura Innovation Hall', 'Salle d’innovation Karura', 'قاعة كارورا للابتكار', 'Sala de Inovação Karura', 'Sala de Innovación Karura', 'Ukumbi wa Ubunifu wa Karura'),
                'track' => $this->translations('Climate action', 'Action climatique', 'العمل المناخي', 'Ação climática', 'Acción climática', 'Hatua za tabianchi'),
                'start_at' => $climateDate->setTime(9, 0),
                'end_at' => $climateDate->setTime(11, 0),
                'format' => 'workshop',
                'sort_order' => 1,
                'is_published' => true,
            ],
        ];

        foreach ($sessions as $session) {
            Session::query()->updateOrCreate(
                [
                    'event_id' => $session['event_id'],
                    'sort_order' => $session['sort_order'],
                ],
                $session,
            );
        }
    }

    private function seedNews(): void
    {
        $anchor = CarbonImmutable::now();
        $posts = [
            [
                'slug' => 'registration-opens-for-leadership-summit',
                'title' => $this->translations(
                    'Registration opens for the Pan-African Leadership Summit',
                    'Ouverture des inscriptions au Sommet panafricain du leadership',
                    'فتح التسجيل في قمة القيادة الإفريقية',
                    'Abertas as inscrições para a Cimeira Pan-Africana de Liderança',
                    'Abiertas las inscripciones para la Cumbre Panafricana de Liderazgo',
                    'Usajili wafunguliwa kwa Mkutano wa Uongozi wa Afrika',
                ),
                'excerpt' => $this->translations(
                    'Applications are now open for public leaders, civic practitioners and implementation partners.',
                    'Les candidatures sont ouvertes aux responsables publics, praticiens civiques et partenaires de mise en œuvre.',
                    'باب التقديم مفتوح الآن للقادة العموميين والممارسين المدنيين وشركاء التنفيذ.',
                    'As candidaturas estão abertas para dirigentes públicos, profissionais cívicos e parceiros de execução.',
                    'Las candidaturas están abiertas para responsables públicos, profesionales cívicos y socios de ejecución.',
                    'Maombi sasa yako wazi kwa viongozi wa umma, wataalamu wa kiraia na washirika wa utekelezaji.',
                ),
                'body' => $this->translations(
                    'The flagship summit will convene a carefully balanced cohort from across the continent. Early applications are encouraged, and a limited number of travel-support awards will be available to eligible participants.',
                    'Le sommet phare réunira un groupe soigneusement équilibré venu de tout le continent. Les candidatures anticipées sont encouragées et un nombre limité de bourses de voyage sera proposé aux participants éligibles.',
                    'ستجمع القمة الرئيسية مجموعة متوازنة بعناية من مختلف أنحاء القارة. يُشجَّع التقديم المبكر، وستتاح منح محدودة لدعم السفر للمشاركين المؤهلين.',
                    'A cimeira principal reunirá um grupo cuidadosamente equilibrado de todo o continente. Recomenda-se a candidatura antecipada e haverá um número limitado de apoios de viagem para participantes elegíveis.',
                    'La cumbre principal reunirá un grupo cuidadosamente equilibrado de todo el continente. Se recomienda presentar la solicitud pronto y habrá un número limitado de ayudas de viaje para participantes elegibles.',
                    'Mkutano mkuu utawakutanisha washiriki walioteuliwa kwa uwiano kutoka kote barani. Maombi ya mapema yanahimizwa, na misaada michache ya usafiri itapatikana kwa washiriki wanaostahili.',
                ),
                'category' => $this->translations('Announcements', 'Annonces', 'الإعلانات', 'Anúncios', 'Anuncios', 'Matangazo'),
                'image' => '/images/hero-summit.png',
                'published_at' => $anchor->subDays(2),
                'is_featured' => true,
                'is_published' => true,
            ],
            [
                'slug' => 'first-speakers-announced-for-2026-programme',
                'title' => $this->translations(
                    'First speakers announced for the 2026 programme',
                    'Premiers intervenants annoncés pour le programme 2026',
                    'الإعلان عن أول المتحدثين في برنامج 2026',
                    'Anunciados os primeiros oradores do programa de 2026',
                    'Anunciados los primeros ponentes del programa de 2026',
                    'Wasemaji wa kwanza watangazwa kwa programu ya 2026',
                ),
                'excerpt' => $this->translations(
                    'A new generation of public leaders, founders and community practitioners joins the stage.',
                    'Une nouvelle génération de responsables publics, fondateurs et praticiens communautaires monte sur scène.',
                    'جيل جديد من القادة العموميين والمؤسسين والممارسين المجتمعيين ينضم إلى المنصة.',
                    'Uma nova geração de dirigentes públicos, fundadores e profissionais comunitários sobe ao palco.',
                    'Una nueva generación de responsables públicos, fundadores y profesionales comunitarios sube al escenario.',
                    'Kizazi kipya cha viongozi wa umma, waanzilishi na wataalamu wa jamii kinajiunga na jukwaa.',
                ),
                'body' => $this->translations(
                    'The opening group reflects the programme’s commitment to practical experience and diverse regional voices. Additional speakers and facilitators will be announced as sessions are confirmed.',
                    'Le premier groupe reflète l’engagement du programme en faveur de l’expérience pratique et de voix régionales diverses. D’autres intervenants et facilitateurs seront annoncés au fil de la confirmation des sessions.',
                    'تعكس المجموعة الأولى التزام البرنامج بالخبرة العملية وتنوع الأصوات الإقليمية. وسيتم الإعلان عن متحدثين وميسرين إضافيين مع تأكيد الجلسات.',
                    'O primeiro grupo reflete o compromisso do programa com a experiência prática e vozes regionais diversas. Outros oradores e facilitadores serão anunciados à medida que as sessões forem confirmadas.',
                    'El primer grupo refleja el compromiso del programa con la experiencia práctica y las diversas voces regionales. Se anunciarán más ponentes y facilitadores a medida que se confirmen las sesiones.',
                    'Kundi la kwanza linaonyesha dhamira ya programu kwa uzoefu wa vitendo na sauti mbalimbali za kikanda. Wasemaji na wawezeshaji zaidi watatangazwa vikao vinapothibitishwa.',
                ),
                'category' => $this->translations('Programme', 'Programme', 'البرنامج', 'Programa', 'Programa', 'Programu'),
                'image' => '/images/hero-plenary.png',
                'published_at' => $anchor->subDays(6),
                'is_featured' => false,
                'is_published' => true,
            ],
            [
                'slug' => 'continental-knowledge-library-launches',
                'title' => $this->translations(
                    'Continental knowledge library launches',
                    'Lancement de la bibliothèque continentale de connaissances',
                    'إطلاق مكتبة المعرفة القارية',
                    'Lançada a biblioteca continental de conhecimento',
                    'Lanzamiento de la biblioteca continental de conocimiento',
                    'Maktaba ya maarifa ya bara yazinduliwa',
                ),
                'excerpt' => $this->translations(
                    'Session briefs, implementation tools and recordings now have one shared home.',
                    'Les notes de session, outils de mise en œuvre et enregistrements disposent désormais d’un espace commun.',
                    'أصبح لملخصات الجلسات وأدوات التنفيذ والتسجيلات مكان مشترك واحد.',
                    'Resumos de sessões, ferramentas de implementação e gravações passam a ter um espaço comum.',
                    'Los resúmenes de sesiones, herramientas de implementación y grabaciones tienen ahora un espacio común.',
                    'Muhtasari wa vikao, zana za utekelezaji na rekodi sasa vina makao ya pamoja.',
                ),
                'body' => $this->translations(
                    'The library is designed for use after the applause ends. Resources are organised by programme theme and will be updated with concise, accessible outputs from every major gathering.',
                    'La bibliothèque est conçue pour être utile après les applaudissements. Les ressources sont organisées par thème et seront enrichies de résultats concis et accessibles issus de chaque grande rencontre.',
                    'صُممت المكتبة للاستخدام بعد انتهاء التصفيق. نُظمت الموارد حسب موضوع البرنامج وستُحدّث بمخرجات موجزة ومتاحة من كل ملتقى رئيسي.',
                    'A biblioteca foi concebida para ser útil depois dos aplausos. Os recursos estão organizados por tema e serão atualizados com resultados concisos e acessíveis de cada grande encontro.',
                    'La biblioteca está diseñada para ser útil después de los aplausos. Los recursos se organizan por tema y se actualizarán con resultados concisos y accesibles de cada gran encuentro.',
                    'Maktaba imeundwa kutumika hata baada ya makofi kuisha. Rasilimali zimepangwa kwa mada na zitasasishwa kwa matokeo mafupi na yanayofikika kutoka kila mkutano mkuu.',
                ),
                'category' => $this->translations('Resources', 'Ressources', 'الموارد', 'Recursos', 'Recursos', 'Rasilimali'),
                'image' => '/images/hero-innovation.png',
                'published_at' => $anchor->subDays(11),
                'is_featured' => false,
                'is_published' => true,
            ],
            [
                'slug' => 'addis-ababa-host-city-guide',
                'title' => $this->translations(
                    'Your host-city guide to Addis Ababa',
                    'Votre guide de la ville hôte : Addis-Abeba',
                    'دليلك إلى المدينة المضيفة: أديس أبابا',
                    'O seu guia da cidade anfitriã: Adis Abeba',
                    'Su guía de la ciudad anfitriona: Adís Abeba',
                    'Mwongozo wako wa mji mwenyeji: Addis Ababa',
                ),
                'excerpt' => $this->translations(
                    'Essential planning notes for summit delegates travelling to Ethiopia.',
                    'Les informations essentielles pour les délégués du sommet qui se rendent en Éthiopie.',
                    'معلومات أساسية لمندوبي القمة المسافرين إلى إثيوبيا.',
                    'Informações essenciais para os delegados da cimeira que viajam para a Etiópia.',
                    'Información esencial para delegados de la cumbre que viajan a Etiopía.',
                    'Taarifa muhimu kwa wajumbe wa mkutano wanaosafiri kwenda Ethiopia.',
                ),
                'body' => $this->translations(
                    'The guide brings together arrival advice, local transport, weather, accessibility and cultural notes. Registered delegates will receive detailed visa-support and accommodation information by email.',
                    'Le guide rassemble des conseils d’arrivée, des informations sur les transports locaux, la météo, l’accessibilité et la culture. Les délégués inscrits recevront par courriel des informations détaillées sur les visas et l’hébergement.',
                    'يجمع الدليل نصائح الوصول والنقل المحلي والطقس وإمكانية الوصول وملاحظات ثقافية. وسيتلقى المندوبون المسجلون معلومات مفصلة عن دعم التأشيرات والإقامة عبر البريد الإلكتروني.',
                    'O guia reúne conselhos de chegada, transporte local, clima, acessibilidade e notas culturais. Os delegados inscritos receberão por correio eletrónico informações detalhadas sobre vistos e alojamento.',
                    'La guía reúne consejos de llegada, transporte local, clima, accesibilidad y notas culturales. Los delegados inscritos recibirán por correo información detallada sobre visados y alojamiento.',
                    'Mwongozo unakusanya ushauri wa kuwasili, usafiri wa ndani, hali ya hewa, ufikivu na taarifa za utamaduni. Wajumbe waliosajiliwa watapokea maelezo ya visa na malazi kwa barua pepe.',
                ),
                'category' => $this->translations('Travel', 'Voyage', 'السفر', 'Viagem', 'Viaje', 'Safari'),
                'image' => '/images/hero-summit.png',
                'published_at' => $anchor->subDays(16),
                'is_featured' => false,
                'is_published' => true,
            ],
        ];

        foreach ($posts as $post) {
            NewsPost::query()->updateOrCreate(
                ['slug' => $post['slug']],
                $post,
            );
        }
    }

    private function seedFaqs(): void
    {
        $registration = $this->translations('Registration', 'Inscription', 'التسجيل', 'Inscrição', 'Inscripción', 'Usajili');
        $participation = $this->translations('Participation', 'Participation', 'المشاركة', 'Participação', 'Participación', 'Ushiriki');
        $access = $this->translations('Access & support', 'Accès et assistance', 'الوصول والدعم', 'Acesso e apoio', 'Acceso y asistencia', 'Ufikivu na usaidizi');

        $faqs = [
            [
                'question' => $this->translations('How do I register for an event?', 'Comment m’inscrire à un événement ?', 'كيف أسجل في فعالية؟', 'Como me inscrevo num evento?', '¿Cómo me inscribo en un evento?', 'Ninajisajilije kwa tukio?'),
                'answer' => $this->translations('Open the event page and select Register. Complete the short application and watch for a confirmation email. Some invitation-only sessions use a separate access link.', 'Ouvrez la page de l’événement et sélectionnez « S’inscrire ». Remplissez le court formulaire et surveillez votre courriel de confirmation. Certaines sessions sur invitation utilisent un lien distinct.', 'افتح صفحة الفعالية واختر «التسجيل». أكمل الطلب المختصر وانتظر رسالة التأكيد عبر البريد الإلكتروني. تستخدم بعض الجلسات المخصصة للمدعوين رابطاً منفصلاً.', 'Abra a página do evento e selecione Inscrever. Preencha a candidatura breve e aguarde o correio de confirmação. Algumas sessões apenas por convite utilizam uma ligação separada.', 'Abra la página del evento y seleccione Inscribirse. Complete la breve solicitud y espere el correo de confirmación. Algunas sesiones solo por invitación utilizan un enlace aparte.', 'Fungua ukurasa wa tukio na uchague Jisajili. Jaza ombi fupi na usubiri barua pepe ya uthibitisho. Baadhi ya vikao vya mwaliko hutumia kiungo tofauti.'),
                'category' => $registration,
                'sort_order' => 1,
                'is_published' => true,
            ],
            [
                'question' => $this->translations('Is there a fee to participate?', 'La participation est-elle payante ?', 'هل توجد رسوم للمشاركة؟', 'Existe uma taxa de participação?', '¿Hay que pagar para participar?', 'Je, kuna ada ya kushiriki?'),
                'answer' => $this->translations('Most public and online sessions are free. Any fee for a specialist workshop will be displayed clearly before registration, together with available waivers.', 'La plupart des sessions publiques et en ligne sont gratuites. Les éventuels frais d’un atelier spécialisé seront clairement indiqués avant l’inscription, avec les possibilités d’exonération.', 'معظم الجلسات العامة وعبر الإنترنت مجانية. وستُعرض بوضوح أي رسوم لورشة متخصصة قبل التسجيل، إلى جانب الإعفاءات المتاحة.', 'A maioria das sessões públicas e online é gratuita. Qualquer taxa de uma oficina especializada será indicada claramente antes da inscrição, juntamente com as isenções disponíveis.', 'La mayoría de las sesiones públicas y en línea son gratuitas. Cualquier tarifa de un taller especializado se mostrará claramente antes de la inscripción, junto con las exenciones disponibles.', 'Vikao vingi vya umma na mtandaoni ni bure. Ada yoyote ya warsha maalumu itaonyeshwa wazi kabla ya usajili pamoja na misamaha inayopatikana.'),
                'category' => $registration,
                'sort_order' => 2,
                'is_published' => true,
            ],
            [
                'question' => $this->translations('Which languages are available?', 'Quelles langues sont disponibles ?', 'ما اللغات المتاحة؟', 'Que idiomas estão disponíveis?', '¿Qué idiomas están disponibles?', 'Ni lugha zipi zinapatikana?'),
                'answer' => $this->translations('The website supports Arabic, English, French, Portuguese, Spanish and Kiswahili. Interpretation offered at each event is listed on its event page.', 'Le site est disponible en anglais, arabe, espagnol, français, kiswahili et portugais. Les services d’interprétation de chaque événement sont indiqués sur sa page.', 'يدعم الموقع العربية والإنجليزية والفرنسية والبرتغالية والإسبانية والسواحيلية. وتُذكر خدمات الترجمة الفورية المتاحة لكل فعالية في صفحتها.', 'O site está disponível em árabe, espanhol, francês, inglês, português e suaíli. A interpretação oferecida em cada evento está indicada na respetiva página.', 'El sitio está disponible en árabe, español, francés, inglés, portugués y suajili. La interpretación de cada evento figura en su página.', 'Tovuti inapatikana kwa Kiarabu, Kiingereza, Kifaransa, Kireno, Kihispania na Kiswahili. Ukalimani wa kila tukio umeorodheshwa kwenye ukurasa wake.'),
                'category' => $participation,
                'sort_order' => 3,
                'is_published' => true,
            ],
            [
                'question' => $this->translations('Can I join sessions online?', 'Puis-je suivre les sessions en ligne ?', 'هل يمكنني الانضمام إلى الجلسات عبر الإنترنت؟', 'Posso participar nas sessões online?', '¿Puedo participar en las sesiones en línea?', 'Naweza kujiunga na vikao mtandaoni?'),
                'answer' => $this->translations('Events marked Hybrid or Online include a secure viewing link. Registered participants receive access and time-zone details before the event.', 'Les événements indiqués comme hybrides ou en ligne comprennent un lien de diffusion sécurisé. Les participants inscrits reçoivent les accès et les informations de fuseau horaire avant l’événement.', 'تتضمن الفعاليات المحددة بأنها هجينة أو عبر الإنترنت رابط مشاهدة آمناً. ويتلقى المشاركون المسجلون تفاصيل الوصول والمنطقة الزمنية قبل الفعالية.', 'Os eventos marcados como Híbridos ou Online incluem uma ligação segura. Os participantes inscritos recebem os dados de acesso e fuso horário antes do evento.', 'Los eventos marcados como híbridos o en línea incluyen un enlace seguro. Los participantes inscritos reciben los datos de acceso y zona horaria antes del evento.', 'Matukio yaliyowekwa alama ya Mseto au Mtandaoni yana kiungo salama cha kutazama. Washiriki waliosajiliwa hupokea maelezo ya kuingia na saa za eneo kabla ya tukio.'),
                'category' => $participation,
                'sort_order' => 4,
                'is_published' => true,
            ],
            [
                'question' => $this->translations('Are venues accessible?', 'Les lieux sont-ils accessibles ?', 'هل أماكن الفعاليات مهيأة لذوي الإعاقة؟', 'Os locais são acessíveis?', '¿Los espacios son accesibles?', 'Maeneo ya matukio yanafikika?'),
                'answer' => $this->translations('We select step-free venues and provide reserved seating, accessible facilities and support on request. Share your requirements in the registration form so the team can prepare.', 'Nous choisissons des lieux sans obstacle et proposons des places réservées, des installations accessibles et une assistance sur demande. Indiquez vos besoins dans le formulaire d’inscription afin que l’équipe puisse se préparer.', 'نختار أماكن خالية من العوائق ونوفر مقاعد محجوزة ومرافق مهيأة ودعماً عند الطلب. اذكر احتياجاتك في نموذج التسجيل ليتمكن الفريق من الاستعداد.', 'Selecionamos locais sem barreiras e disponibilizamos lugares reservados, instalações acessíveis e apoio mediante pedido. Indique as suas necessidades no formulário de inscrição para a equipa se preparar.', 'Seleccionamos espacios sin barreras y ofrecemos asientos reservados, instalaciones accesibles y apoyo previa solicitud. Indique sus necesidades en el formulario para que el equipo pueda prepararse.', 'Tunachagua maeneo yasiyo na vizuizi na kutoa viti vilivyohifadhiwa, vifaa vinavyofikika na usaidizi unapoombwa. Eleza mahitaji yako katika fomu ya usajili ili timu ijiandae.'),
                'category' => $access,
                'sort_order' => 5,
                'is_published' => true,
            ],
            [
                'question' => $this->translations('Do you provide visa or travel support?', 'Proposez-vous une aide pour les visas ou les voyages ?', 'هل تقدمون دعماً للتأشيرة أو السفر؟', 'Disponibilizam apoio para vistos ou viagens?', '¿Ofrecen apoyo para visados o viajes?', 'Mnatoa msaada wa visa au safari?'),
                'answer' => $this->translations('Confirmed international delegates receive an invitation letter and practical visa guidance. Travel funding is limited and is communicated within each event’s application process.', 'Les délégués internationaux confirmés reçoivent une lettre d’invitation et des conseils pratiques pour le visa. Les financements de voyage sont limités et précisés dans la procédure de candidature de chaque événement.', 'يتلقى المندوبون الدوليون المؤكدون خطاب دعوة وإرشادات عملية للتأشيرة. وتمويل السفر محدود ويُوضح ضمن عملية التقديم لكل فعالية.', 'Os delegados internacionais confirmados recebem uma carta-convite e orientações práticas sobre vistos. O financiamento de viagens é limitado e comunicado no processo de candidatura de cada evento.', 'Los delegados internacionales confirmados reciben una carta de invitación y orientación práctica sobre visados. La financiación de viajes es limitada y se comunica en la solicitud de cada evento.', 'Wajumbe wa kimataifa waliothibitishwa hupokea barua ya mwaliko na mwongozo wa visa. Ufadhili wa safari ni mdogo na hufafanuliwa katika mchakato wa maombi wa kila tukio.'),
                'category' => $access,
                'sort_order' => 6,
                'is_published' => true,
            ],
            [
                'question' => $this->translations('Will session recordings be available?', 'Les enregistrements des sessions seront-ils disponibles ?', 'هل ستتوفر تسجيلات الجلسات؟', 'As gravações das sessões estarão disponíveis?', '¿Estarán disponibles las grabaciones?', 'Rekodi za vikao zitapatikana?'),
                'answer' => $this->translations('Recordings and concise session briefs are published in the knowledge library when speaker consent and content rights allow. Registered participants are notified when materials are ready.', 'Les enregistrements et notes de synthèse sont publiés dans la bibliothèque de connaissances lorsque l’accord des intervenants et les droits le permettent. Les participants inscrits sont informés dès leur mise en ligne.', 'تُنشر التسجيلات وملخصات الجلسات في مكتبة المعرفة عندما تسمح موافقة المتحدثين وحقوق المحتوى. ويتم إشعار المشاركين المسجلين عند جاهزية المواد.', 'As gravações e os resumos são publicados na biblioteca quando o consentimento dos oradores e os direitos de conteúdo o permitem. Os participantes inscritos são avisados quando os materiais ficam disponíveis.', 'Las grabaciones y los resúmenes se publican en la biblioteca cuando lo permiten el consentimiento de los ponentes y los derechos. Los participantes inscritos reciben un aviso cuando están listos.', 'Rekodi na muhtasari wa vikao huchapishwa katika maktaba pale idhini ya wasemaji na haki za maudhui zinaporuhusu. Washiriki waliosajiliwa hujulishwa nyenzo zinapokuwa tayari.'),
                'category' => $participation,
                'sort_order' => 7,
                'is_published' => true,
            ],
            [
                'question' => $this->translations('Can I receive a certificate of participation?', 'Puis-je recevoir un certificat de participation ?', 'هل يمكنني الحصول على شهادة مشاركة؟', 'Posso receber um certificado de participação?', '¿Puedo recibir un certificado de participación?', 'Naweza kupata cheti cha ushiriki?'),
                'answer' => $this->translations('Eligible participants receive a digital certificate after attendance is verified. The event page indicates whether certificates are offered and any completion requirements.', 'Les participants éligibles reçoivent un certificat numérique après vérification de leur présence. La page de l’événement précise si un certificat est proposé et les conditions à remplir.', 'يتلقى المشاركون المؤهلون شهادة رقمية بعد التحقق من الحضور. وتوضح صفحة الفعالية ما إذا كانت الشهادات متاحة ومتطلبات إتمامها.', 'Os participantes elegíveis recebem um certificado digital após verificação da presença. A página do evento indica se há certificados e quais os requisitos de conclusão.', 'Los participantes elegibles reciben un certificado digital tras verificar la asistencia. La página del evento indica si se ofrecen certificados y los requisitos.', 'Washiriki wanaostahili hupokea cheti cha kidijitali baada ya mahudhurio kuthibitishwa. Ukurasa wa tukio unaonyesha ikiwa vyeti vinatolewa na masharti yake.'),
                'category' => $participation,
                'sort_order' => 8,
                'is_published' => true,
            ],
        ];

        foreach ($faqs as $faq) {
            Faq::query()->updateOrCreate(
                ['sort_order' => $faq['sort_order']],
                $faq,
            );
        }
    }

    private function seedAboutPage(): void
    {
        Page::query()->updateOrCreate(
            ['key' => 'about'],
            [
                'eyebrow' => $this->translations(
                    'One continent, shared momentum',
                    'Un continent, un élan partagé',
                    'قارة واحدة، زخم مشترك',
                    'Um continente, um impulso partilhado',
                    'Un continente, un impulso compartido',
                    'Bara moja, msukumo wa pamoja',
                ),
                'title' => $this->translations(
                    'Gathering people who turn dialogue into delivery',
                    'Réunir celles et ceux qui transforment le dialogue en résultats',
                    'نجمع من يحولون الحوار إلى إنجاز',
                    'Reunir pessoas que transformam diálogo em resultados',
                    'Reunir a quienes convierten el diálogo en resultados',
                    'Kuwakutanisha wanaogeuza mazungumzo kuwa utekelezaji',
                ),
                'body' => $this->translations(
                    'The Continental Events & Programmes Forum is a shared platform for purposeful gatherings across Africa. We connect public institutions, communities, researchers, entrepreneurs and implementation partners around the work that matters now. Every programme is designed to produce useful relationships, practical knowledge and clear next steps—not simply another conversation.',
                    'Le Forum continental des événements et programmes est une plateforme commune pour des rencontres porteuses de sens à travers l’Afrique. Nous réunissons institutions publiques, communautés, chercheurs, entrepreneurs et partenaires de mise en œuvre autour des enjeux qui comptent aujourd’hui. Chaque programme vise à créer des relations utiles, des connaissances pratiques et des prochaines étapes claires — pas simplement une conversation de plus.',
                    'منتدى الفعاليات والبرامج القارية منصة مشتركة للملتقيات الهادفة في أنحاء إفريقيا. نربط المؤسسات العامة والمجتمعات والباحثين ورواد الأعمال وشركاء التنفيذ حول العمل الأكثر أهمية اليوم. صُمم كل برنامج لبناء علاقات مفيدة ومعرفة عملية وخطوات تالية واضحة، وليس لمجرد إضافة حوار آخر.',
                    'O Fórum Continental de Eventos e Programas é uma plataforma comum para encontros com propósito em toda a África. Ligamos instituições públicas, comunidades, investigadores, empreendedores e parceiros de execução em torno do trabalho que importa agora. Cada programa é concebido para gerar relações úteis, conhecimento prático e próximos passos claros — não apenas mais uma conversa.',
                    'El Foro Continental de Eventos y Programas es una plataforma común para encuentros con propósito en toda África. Conectamos instituciones públicas, comunidades, investigadores, emprendedores y socios de ejecución en torno al trabajo que importa ahora. Cada programa busca generar relaciones útiles, conocimiento práctico y próximos pasos claros, no simplemente otra conversación.',
                    'Jukwaa la Bara la Matukio na Programu ni sehemu ya pamoja kwa mikutano yenye kusudi kote Afrika. Tunaunganisha taasisi za umma, jamii, watafiti, wajasiriamali na washirika wa utekelezaji kuhusu kazi muhimu ya sasa. Kila programu imeundwa kuzalisha mahusiano yenye manufaa, maarifa ya vitendo na hatua zinazofuata zilizo wazi—si mazungumzo mengine tu.',
                ),
                'image' => '/images/hero-innovation.png',
                'is_published' => true,
            ],
        );
    }

    private function seedHomeSections(): void
    {
        $sections = [
            ['key' => 'hero', 'label' => 'Hero slider', 'is_active' => true, 'sort_order' => 1],
            ['key' => 'sessions', 'label' => 'Upcoming sessions', 'is_active' => true, 'sort_order' => 2],
            ['key' => 'programs', 'label' => 'Programme themes', 'is_active' => true, 'sort_order' => 3],
            ['key' => 'events', 'label' => 'Featured events', 'is_active' => true, 'sort_order' => 4],
            ['key' => 'news', 'label' => 'Latest news', 'is_active' => true, 'sort_order' => 5],
            ['key' => 'about', 'label' => 'About the forum', 'is_active' => true, 'sort_order' => 6],
            ['key' => 'faq', 'label' => 'Frequently asked questions', 'is_active' => true, 'sort_order' => 7],
            ['key' => 'cta', 'label' => 'Closing call to action', 'is_active' => true, 'sort_order' => 8],
        ];

        foreach ($sections as $section) {
            HomeSection::query()->updateOrCreate(
                ['key' => $section['key']],
                $section,
            );
        }
    }

    private function seedSettings(): void
    {
        $year = (string) CarbonImmutable::now()->year;
        $settings = [
            [
                'key' => 'site_name',
                'value' => $this->translations('Continental Events & Programmes Forum', 'Forum continental des événements et programmes', 'منتدى الفعاليات والبرامج القارية', 'Fórum Continental de Eventos e Programas', 'Foro Continental de Eventos y Programas', 'Jukwaa la Bara la Matukio na Programu'),
                'group' => 'general',
            ],
            [
                'key' => 'tagline',
                'value' => $this->translations('Convening ideas. Building partnerships. Delivering progress.', 'Rassembler les idées. Créer des partenariats. Produire des résultats.', 'نجمع الأفكار. نبني الشراكات. نحقق التقدم.', 'Reunir ideias. Criar parcerias. Gerar progresso.', 'Reunir ideas. Crear alianzas. Lograr progreso.', 'Kukutanisha mawazo. Kujenga ushirikiano. Kuleta maendeleo.'),
                'group' => 'general',
            ],
            [
                'key' => 'logo',
                'value' => ['value' => '/images/brand-mark.svg'],
                'group' => 'general',
            ],
            [
                'key' => 'address',
                'value' => $this->translations('Roosevelt Street, Addis Ababa, Ethiopia', 'Roosevelt Street, Addis-Abeba, Éthiopie', 'شارع روزفلت، أديس أبابا، إثيوبيا', 'Roosevelt Street, Adis Abeba, Etiópia', 'Roosevelt Street, Adís Abeba, Etiopía', 'Roosevelt Street, Addis Ababa, Ethiopia'),
                'group' => 'general',
            ],
            [
                'key' => 'contact_email',
                'value' => ['value' => 'hello@continentalforum.africa'],
                'group' => 'contact',
            ],
            [
                'key' => 'contact_phone',
                'value' => ['value' => '+251 11 551 77 00'],
                'group' => 'contact',
            ],
            [
                'key' => 'footer_blurb',
                'value' => $this->translations('A shared home for events, programmes and practical partnerships advancing Africa’s priorities.', 'Un espace commun pour les événements, programmes et partenariats concrets qui font progresser les priorités de l’Afrique.', 'منصة مشتركة للفعاليات والبرامج والشراكات العملية التي تدفع أولويات إفريقيا إلى الأمام.', 'Um espaço comum para eventos, programas e parcerias práticas que promovem as prioridades de África.', 'Un espacio común para eventos, programas y alianzas prácticas que impulsan las prioridades de África.', 'Makao ya pamoja kwa matukio, programu na ushirikiano wa vitendo unaoendeleza vipaumbele vya Afrika.'),
                'group' => 'footer',
            ],
            [
                'key' => 'copyright',
                'value' => $this->translations("© {$year} Continental Events & Programmes Forum. All rights reserved.", "© {$year} Forum continental des événements et programmes. Tous droits réservés.", "© {$year} منتدى الفعاليات والبرامج القارية. جميع الحقوق محفوظة.", "© {$year} Fórum Continental de Eventos e Programas. Todos os direitos reservados.", "© {$year} Foro Continental de Eventos y Programas. Todos los derechos reservados.", "© {$year} Jukwaa la Bara la Matukio na Programu. Haki zote zimehifadhiwa."),
                'group' => 'footer',
            ],
            ['key' => 'facebook_url', 'value' => ['value' => ''], 'group' => 'social'],
            ['key' => 'linkedin_url', 'value' => ['value' => ''], 'group' => 'social'],
            ['key' => 'youtube_url', 'value' => ['value' => ''], 'group' => 'social'],
        ];

        foreach ($settings as $setting) {
            Setting::query()->updateOrCreate(
                ['key' => $setting['key']],
                $setting,
            );
        }
    }

    /**
     * @return array<string, string>
     */
    private function allLocales(string $value): array
    {
        return array_fill_keys(['en', 'fr', 'ar', 'pt', 'es', 'sw'], $value);
    }

    /**
     * @return array<string, string>
     */
    private function translations(
        string $english,
        string $french,
        string $arabic,
        string $portuguese,
        string $spanish,
        string $swahili,
    ): array {
        return [
            'en' => $english,
            'fr' => $french,
            'ar' => $arabic,
            'pt' => $portuguese,
            'es' => $spanish,
            'sw' => $swahili,
        ];
    }
}
