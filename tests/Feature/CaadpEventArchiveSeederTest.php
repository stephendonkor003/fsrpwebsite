<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Faq;
use App\Models\HomeSection;
use App\Models\NewsPost;
use App\Models\Program;
use App\Models\Setting;
use App\Models\Slide;
use App\Models\User;
use Database\Seeders\CaadpEventArchiveSeeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class CaadpEventArchiveSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_is_rerunnable_archive_only_and_preserves_existing_records(): void
    {
        $this->prepareDocuments();

        $event = Event::factory()->create([
            'slug' => CaadpEventArchiveSeeder::EVENT_SLUG,
            'title' => ['en' => 'Existing CAADP archive title'],
            'image' => '/images/caadp/preserved-event-image.jpeg',
            'is_featured' => true,
            'is_published' => false,
        ]);
        $user = User::factory()->create(['is_admin' => true, 'is_active' => true]);
        $globalRecords = $this->createGlobalRecords();
        $globalHashes = $globalRecords->mapWithKeys(
            fn (Model $model): array => [$model::class => $this->recordHash($model->fresh())],
        );
        $userHash = $this->recordHash($user->fresh());

        $this->seed(CaadpEventArchiveSeeder::class);

        $event->refresh();
        $this->assertSame('Existing CAADP archive title', $event->translate('title', 'en'));
        $this->assertSame('/images/caadp/preserved-event-image.jpeg', $event->image);
        $this->assertFalse($event->is_featured);
        $this->assertTrue($event->is_published);
        $this->assertSame('2026-09-15', $event->start_at?->toDateString());
        $this->assertSame('2026-09-18', $event->end_at?->toDateString());
        $this->assertSame(CaadpEventArchiveSeeder::REGISTRATION_URL, $event->registration_url);
        $this->assertSame(4, $event->sessions()->count());
        $this->assertSame(4, $event->sessions()->where('is_all_day', true)->whereNull('end_at')->count());
        $this->assertSame(2, $event->resources()->count());
        $this->assertSame(['programme', 'brief'], $event->resources()->orderBy('sort_order')->pluck('category')->all());
        $this->assertSame('application/pdf', $event->resources()->where('category', 'brief')->firstOrFail()->mime_type);
        $sessionIds = $event->sessions()->orderBy('sort_order')->pluck('id')->all();
        $resourceIds = $event->resources()->orderBy('sort_order')->pluck('id')->all();

        $this->seed(CaadpEventArchiveSeeder::class);

        $this->assertSame($event->id, Event::query()->where('slug', CaadpEventArchiveSeeder::EVENT_SLUG)->sole()->id);
        $this->assertSame($sessionIds, $event->sessions()->orderBy('sort_order')->pluck('id')->all());
        $this->assertSame($resourceIds, $event->resources()->orderBy('sort_order')->pluck('id')->all());
        $this->assertDatabaseCount('events', 1);
        $this->assertDatabaseCount('sessions', 4);
        $this->assertDatabaseCount('event_resources', 2);
        $this->assertDatabaseCount('news_posts', 1);
        $this->assertSame($userHash, $this->recordHash($user->fresh()));

        foreach ($globalRecords as $record) {
            $this->assertSame($globalHashes[$record::class], $this->recordHash($record->fresh()));
        }

        Storage::disk('local')->assertExists([
            CaadpEventArchiveSeeder::BRIEF_PATH,
            CaadpEventArchiveSeeder::PROGRAMME_PATH,
        ]);
    }

    public function test_new_archive_event_uses_canonical_media_and_all_supported_locales(): void
    {
        $this->prepareDocuments();

        $this->seed(CaadpEventArchiveSeeder::class);

        $event = Event::query()->where('slug', CaadpEventArchiveSeeder::EVENT_SLUG)->sole();

        $this->assertSame('/images/caadp/caadp-partnership-4.jpeg', $event->image);
        $this->assertSame('22nd CAADP Partnership Platform', $event->translate('title', 'en'));
        $this->assertSame(['en', 'fr', 'ar', 'pt', 'es', 'sw'], array_keys($event->title));
        $this->assertFalse($event->is_featured);
        $this->assertTrue($event->is_published);
    }

    public function test_missing_programme_file_prevents_any_archive_changes(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put(CaadpEventArchiveSeeder::BRIEF_PATH, 'original supplied brief');
        $existingEvent = Event::factory()->create();

        try {
            $this->seed(CaadpEventArchiveSeeder::class);
            $this->fail('The missing programme file should prevent archive publication.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString(CaadpEventArchiveSeeder::PROGRAMME_PATH, $exception->getMessage());
        }

        $this->assertDatabaseCount('events', 1);
        $this->assertDatabaseHas('events', ['id' => $existingEvent->id]);
        $this->assertDatabaseCount('sessions', 0);
        $this->assertDatabaseCount('event_resources', 0);
    }

    /**
     * @return Collection<int, Model>
     */
    private function createGlobalRecords(): Collection
    {
        return collect([
            Setting::query()->create(['key' => 'site_name', 'value' => ['en' => 'Custom event platform'], 'group' => 'general']),
            Slide::query()->create(['title' => ['en' => 'Custom slide'], 'image' => '/custom-slide.jpg', 'is_active' => true]),
            Program::query()->create([
                'slug' => 'custom-programme',
                'title' => ['en' => 'Custom programme'],
                'excerpt' => ['en' => 'Custom excerpt'],
                'body' => ['en' => 'Custom body'],
                'is_published' => true,
            ]),
            Faq::query()->create([
                'category' => ['en' => 'General'],
                'question' => ['en' => 'Custom question?'],
                'answer' => ['en' => 'Custom answer.'],
                'is_published' => true,
            ]),
            NewsPost::query()->create([
                'slug' => 'custom-update',
                'title' => ['en' => 'Custom update'],
                'excerpt' => ['en' => 'Custom excerpt'],
                'body' => ['en' => 'Custom body'],
                'category' => ['en' => 'Updates'],
                'is_published' => true,
            ]),
            HomeSection::query()->create([
                'key' => 'custom-section',
                'label' => 'Custom section',
                'is_active' => true,
                'sort_order' => 1,
            ]),
        ]);
    }

    private function recordHash(Model $model): string
    {
        return hash('sha256', serialize($model->getRawOriginal()));
    }

    private function prepareDocuments(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put(CaadpEventArchiveSeeder::BRIEF_PATH, 'original supplied brief');
        Storage::disk('local')->put(CaadpEventArchiveSeeder::PROGRAMME_PATH, '%PDF-1.7 programme overview');
    }
}
