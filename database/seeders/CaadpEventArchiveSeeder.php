<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\EventResource;
use App\Models\Session;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class CaadpEventArchiveSeeder extends Seeder
{
    public const EVENT_SLUG = '22nd-caadp-partnership-platform';

    public const REGISTRATION_URL = 'https://accreditation.au.int/en/registration-form-delegates-attending-22nd-annual-caadp-partnership-platformcaadp-pp';

    public const BRIEF_PATH = 'event-resources/22nd-caadp-pp-information-note.pdf';

    public const PROGRAMME_PATH = 'event-resources/22nd-caadp-pp-programme-overview.pdf';

    /**
     * Preserve CAADP as a published, unfeatured event archive entry.
     */
    public function run(): void
    {
        self::assertRequiredAssetsExist();

        DB::transaction(function (): void {
            $event = $this->seedArchivedEvent();

            $this->seedDailyOverview($event);
            $this->seedResources($event);
        });
    }

    public static function assertRequiredAssetsExist(): void
    {
        foreach ([self::BRIEF_PATH, self::PROGRAMME_PATH] as $path) {
            if (! Storage::disk('local')->exists($path)) {
                throw new RuntimeException('Required event document is missing: '.$path);
            }
        }
    }

    private function seedArchivedEvent(): Event
    {
        $event = Event::query()->firstOrNew(['slug' => self::EVENT_SLUG]);

        if (! $event->exists) {
            $event->fill([
                'title' => $this->t('22nd CAADP Partnership Platform'),
                'excerpt' => $this->t('Partner event | African Union Commission (AUC) | From the CAADP Strategy and Action Plan 2026-2035 to practical country and regional delivery.'),
                'body' => $this->t('PARTNER EVENT. Convened by the African Union Commission (AUC) with Member States, regional economic communities, technical institutions and development partners. The four-day Platform links political direction, country and regional readiness, implementation delivery labs, investment alignment and mutual accountability. The meeting took place at Rainbow Towers Hotel and Conference Centre in Harare from 15-18 September 2026. The official information note covers accommodation, airport transfers, immigration, health, DSA, weather, currency, electricity and organizer contacts for AUC-sponsored and self-sponsored participants.'),
                'venue' => $this->t('Rainbow Towers Hotel and Conference Centre, Harare, Zimbabwe'),
                'image' => '/images/caadp/caadp-partnership-4.jpeg',
            ]);
        }

        $event->fill([
            'start_at' => '2026-09-15 00:00:00',
            'end_at' => '2026-09-18 23:59:59',
            'mode' => 'in-person',
            'registration_url' => self::REGISTRATION_URL,
            'is_featured' => false,
            'is_published' => true,
        ]);
        $event->save();

        return $event;
    }

    private function seedDailyOverview(Event $event): void
    {
        $days = [
            [
                'date' => '2026-09-15',
                'title' => 'Day 1 - Political direction & CAADP strategy',
                'summary' => 'Use evidence and the Kampala framework to set direction and agree criteria for selecting priority implementation actions.',
            ],
            [
                'date' => '2026-09-16',
                'title' => 'Day 2 - Country readiness & REC delivery',
                'summary' => 'Identify what is ready, blocked and needed across Member States and regional economic communities.',
            ],
            [
                'date' => '2026-09-17',
                'title' => 'Day 3 - Implementation delivery labs',
                'summary' => 'Turn priorities into practical delivery pathways, responsibilities and implementation support.',
            ],
            [
                'date' => '2026-09-18',
                'title' => 'Day 4 - Investment, accountability & follow-up',
                'summary' => 'Align investment and accountability around agreed actions and the next implementation milestones.',
            ],
        ];

        foreach ($days as $index => $day) {
            $session = Session::query()->firstOrNew([
                'event_id' => $event->id,
                'start_at' => $day['date'].' 00:00:00',
            ]);

            if (! $session->exists) {
                $session->fill([
                    'title' => $this->t($day['title']),
                    'summary' => $this->t($day['summary']),
                    'speaker_name' => $this->t(''),
                    'speaker_role' => $this->t(''),
                    'location' => $this->t('Rainbow Towers Hotel and Conference Centre, Harare, Zimbabwe'),
                    'track' => $this->t('Daily overview'),
                    'end_at' => null,
                    'format' => 'in-person',
                ]);
            }

            $session->fill([
                'sort_order' => $index + 1,
                'is_published' => true,
                'is_all_day' => true,
            ])->save();
        }
    }

    private function seedResources(Event $event): void
    {
        $resources = [
            [
                'title' => '22nd CAADP Partnership Platform programme overview',
                'description' => 'Programme overview for the four-day CAADP Partnership Platform.',
                'category' => 'programme',
                'file_path' => self::PROGRAMME_PATH,
                'original_filename' => '22nd_CAADP_PP_Programme_Overview.pdf',
            ],
            [
                'title' => '22nd CAADP Partnership Platform participant information note',
                'description' => 'Official participant information covering the venue, accommodation, transport, visas, health, DSA, weather, currency, electricity and event contacts.',
                'category' => 'brief',
                'file_path' => self::BRIEF_PATH,
                'original_filename' => 'Information_Note_22nd_CAADP_PP_Zimbabwe_2026.pdf',
            ],
        ];

        foreach ($resources as $index => $resource) {
            $eventResource = EventResource::query()->firstOrNew([
                'event_id' => $event->id,
                'category' => $resource['category'],
            ]);

            if (! $eventResource->exists) {
                $eventResource->fill([
                    'title' => $this->t($resource['title']),
                    'description' => $this->t($resource['description']),
                    'language' => 'en',
                    'file_path' => $resource['file_path'],
                    'original_filename' => $resource['original_filename'],
                    'mime_type' => 'application/pdf',
                    'file_size' => Storage::disk('local')->size($resource['file_path']),
                ]);
            }

            $eventResource->fill([
                'is_published' => true,
                'sort_order' => $index + 1,
            ])->save();
        }
    }

    /**
     * @return array{en: string, fr: string, ar: string, pt: string, es: string, sw: string}
     */
    private function t(string $english): array
    {
        return array_fill_keys(['en', 'fr', 'ar', 'pt', 'es', 'sw'], $english);
    }
}
