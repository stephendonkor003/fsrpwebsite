<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Faq;
use App\Models\HomeSection;
use App\Models\NewsPost;
use App\Models\Program;
use App\Models\Session;
use App\Models\Setting;
use App\Models\Slide;
use App\Models\User;
use Database\Seeders\FsrpEventPortalSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class FsrpEventPortalSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seed_is_repeatable_and_preserves_accounts_and_existing_content(): void
    {
        $this->seed();
        $admin = User::factory()->create(['is_admin' => true, 'is_active' => true]);
        $adminBefore = hash('sha256', serialize($admin->fresh()->getRawOriginal()));
        $userCount = User::count();
        $retainedEvent = Event::where('slug', 'pan-african-leadership-summit')->firstOrFail();
        $retainedEvent->update(['title' => ['en' => 'Our retained event']]);
        $this->prepareDocuments();

        $this->seed(FsrpEventPortalSeeder::class);

        $event = Event::where('slug', FsrpEventPortalSeeder::EVENT_SLUG)->firstOrFail();
        $this->assertSame('2026-09-15', $event->start_at->toDateString());
        $this->assertSame('2026-09-18', $event->end_at->toDateString());
        $this->assertNull($event->registration_url);
        $this->assertSame('/images/caadp/caadp-partnership-4.jpeg', $event->image);
        $this->assertSame('Rainbow Towers Hotel and Conference Centre, Harare, Zimbabwe', $event->translate('venue', 'en'));
        $this->assertSame('Partner event | AUC and AUDA-NEPAD | From the CAADP Strategy and Action Plan 2026-2035 to practical country and regional delivery.', $event->translate('excerpt', 'en'));
        $this->assertStringContainsString('AUC', $event->translate('body', 'en'));
        $this->assertStringContainsString('PARTNER EVENT', $event->translate('body', 'en'));
        $this->assertSame(['2026-09-15', '2026-09-16', '2026-09-17', '2026-09-18'],
            $event->sessions()->orderBy('start_at')->get()->map(fn (Session $session): string => $session->start_at->toDateString())->all());
        $this->assertSame(4, $event->sessions()->where('is_all_day', true)->whereNull('end_at')->count());
        $this->assertSame(4, Slide::where('is_active', true)->count());
        $this->assertSame(1, Slide::where('is_active', true)->whereNotNull('video_url')->count());
        $this->assertSame(4, Program::where('is_published', true)->count());
        $this->assertSame(6, Faq::where('is_published', true)->count());
        $this->assertSame('FSRP Events', Setting::where('key', 'site_name')->firstOrFail()->value['en']);
        $this->assertSame('fsrpinfo@africanunion.org', Setting::where('key', 'contact_email')->firstOrFail()->value['value']);
        $this->assertDatabaseHas('home_sections', ['key' => 'resources', 'is_active' => true]);
        $this->assertDatabaseHas('home_sections', ['key' => 'media', 'is_active' => true]);
        $this->assertDatabaseHas('events', ['id' => $retainedEvent->id, 'is_published' => true]);
        $this->assertDatabaseHas('events', ['slug' => 'continental-digital-trade-lab', 'is_published' => false]);
        $this->assertDatabaseCount('events', 6);
        $this->assertDatabaseCount('sessions', 12);
        $this->assertDatabaseCount('slides', 7);
        $this->assertDatabaseCount('programs', 8);
        $this->assertDatabaseCount('faqs', 14);
        $this->assertDatabaseCount('news_posts', 5);
        $partnerUpdate = NewsPost::where('is_published', true)->firstOrFail();
        $this->assertSame('Partner event update', $partnerUpdate->translate('category', 'en'));
        $this->assertStringContainsString('22nd_CAADP_PP_BRIEF_Key_Information.docx', $partnerUpdate->translate('body', 'en'));
        $this->assertStringContainsString('/ar/events/'.FsrpEventPortalSeeder::EVENT_SLUG, $partnerUpdate->translate('body', 'ar'));
        $this->assertDatabaseCount('event_resources', 2);
        $this->assertSame(['programme', 'brief'], $event->resources()->orderBy('sort_order')->pluck('category')->all());
        $this->assertSame('application/pdf', $event->resources()->where('category', 'brief')->firstOrFail()->mime_type);
        $this->assertSame(['en', 'fr', 'ar', 'pt', 'es', 'sw'], array_keys($event->title));

        $this->seed(FsrpEventPortalSeeder::class);

        $this->assertDatabaseCount('events', 6);
        $this->assertDatabaseCount('sessions', 12);
        $this->assertDatabaseCount('slides', 7);
        $this->assertDatabaseCount('programs', 8);
        $this->assertDatabaseCount('faqs', 14);
        $this->assertDatabaseCount('news_posts', 5);
        $this->assertDatabaseCount('event_resources', 2);
        $this->assertDatabaseCount('users', $userCount);
        $this->assertSame($adminBefore, hash('sha256', serialize($admin->fresh()->getRawOriginal())));
        $this->assertSame('Our retained event', $retainedEvent->fresh()->translate('title', 'en'));
        $this->assertSame($event->id, Event::where('slug', FsrpEventPortalSeeder::EVENT_SLUG)->firstOrFail()->id);
        Storage::disk('local')->assertExists([FsrpEventPortalSeeder::BRIEF_PATH, FsrpEventPortalSeeder::PROGRAMME_PATH]);
    }

    public function test_missing_programme_file_prevents_any_content_changes(): void
    {
        $this->seed();
        Storage::fake('local');
        Storage::disk('local')->put(FsrpEventPortalSeeder::BRIEF_PATH, 'original supplied brief');
        $siteName = Setting::where('key', 'site_name')->firstOrFail()->value;
        $activeSlides = Slide::where('is_active', true)->count();
        $sectionCount = HomeSection::count();

        try {
            $this->seed(FsrpEventPortalSeeder::class);
            $this->fail('The missing programme file should prevent publication.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString(FsrpEventPortalSeeder::PROGRAMME_PATH, $exception->getMessage());
        }

        $this->assertDatabaseCount('events', 5);
        $this->assertDatabaseCount('event_resources', 0);
        $this->assertDatabaseCount('home_sections', $sectionCount);
        $this->assertSame($siteName, Setting::where('key', 'site_name')->firstOrFail()->value);
        $this->assertSame($activeSlides, Slide::where('is_active', true)->count());
    }

    private function prepareDocuments(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put(FsrpEventPortalSeeder::BRIEF_PATH, 'original supplied brief');
        Storage::disk('local')->put(FsrpEventPortalSeeder::PROGRAMME_PATH, '%PDF-1.7 programme overview');
    }
}
