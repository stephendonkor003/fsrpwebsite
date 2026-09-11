<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventResource;
use App\Models\Session;
use App\Models\Slide;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\TestWith;
use RuntimeException;
use Tests\TestCase;

class EventResourcesTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_administrator_can_upload_a_private_resource_with_translated_metadata(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $admin = User::factory()->create(['is_admin' => true, 'is_active' => true]);
        $event = Event::factory()->create(['is_published' => false]);

        $this->actingAs($admin)->post(route('admin.content.store', 'resources'), array_merge($this->payload(), [
            'event_id' => $event->id,
            'document' => UploadedFile::fake()->createWithContent('programme.pdf', "%PDF-1.4\nProgramme outline"),
            'file_path' => 'event-resources/forged.pdf',
            'file_size' => 999999,
        ]))->assertSessionHasNoErrors()->assertRedirect(route('admin.content.index', 'resources'));

        $resource = EventResource::firstOrFail();
        $this->assertSame('Programme outline', $resource->translate('title', 'en'));
        $this->assertSame('Programme français', $resource->translate('title', 'fr'));
        $this->assertSame('programme.pdf', $resource->original_filename);
        $this->assertSame('application/pdf', $resource->mime_type);
        $this->assertSame(26, $resource->file_size);
        $this->assertNotSame('event-resources/forged.pdf', $resource->file_path);
        Storage::disk('local')->assertExists($resource->file_path);
        Storage::disk('public')->assertMissing($resource->file_path);
        $this->get(route('resources.download', ['locale' => 'en', 'resource' => $resource]))->assertNotFound();
    }

    #[TestWith(['docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'])]
    #[TestWith(['xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])]
    public function test_office_documents_are_accepted(string $extension, string $mimeType): void
    {
        Storage::fake('local');
        $admin = User::factory()->create(['is_admin' => true, 'is_active' => true]);

        $this->actingAs($admin)->post(route('admin.content.store', 'resources'), array_merge($this->payload(), [
            'document' => UploadedFile::fake()->create('document.'.$extension, 20, $mimeType),
        ]))->assertSessionHasNoErrors();

        $this->assertDatabaseHas('event_resources', ['original_filename' => 'document.'.$extension, 'mime_type' => $mimeType]);
        Storage::disk('local')->assertCount('event-resources', 1);
    }

    public function test_editing_metadata_preserves_the_current_document(): void
    {
        Storage::fake('local');
        $admin = User::factory()->create(['is_admin' => true, 'is_active' => true]);
        $resource = EventResource::factory()->create();
        Storage::disk('local')->put($resource->file_path, 'Existing programme');

        $this->actingAs($admin)->put(route('admin.content.update', ['resources', $resource]), $this->payload())
            ->assertSessionHasNoErrors()->assertRedirect(route('admin.content.index', 'resources'));

        $this->assertSame($resource->file_path, $resource->fresh()->file_path);
        $this->assertSame('Programme outline', $resource->fresh()->translate('title', 'en'));
        $this->assertSame('Existing programme', Storage::disk('local')->get($resource->file_path));
    }

    public function test_replacing_and_deleting_a_resource_removes_the_obsolete_private_files(): void
    {
        Storage::fake('local');
        $admin = User::factory()->create(['is_admin' => true, 'is_active' => true]);
        $resource = EventResource::factory()->create();
        $previousPath = $resource->file_path;
        Storage::disk('local')->put($previousPath, 'Original document');

        $this->actingAs($admin)->put(route('admin.content.update', ['resources', $resource]), array_merge($this->payload(), [
            'document' => UploadedFile::fake()->createWithContent('updated.pdf', "%PDF-1.4\nRevised programme"),
        ]))->assertSessionHasNoErrors();

        $resource->refresh();
        $this->assertSame('updated.pdf', $resource->original_filename);
        Storage::disk('local')->assertMissing($previousPath);
        Storage::disk('local')->assertExists($resource->file_path);

        $this->delete(route('admin.content.destroy', ['resources', $resource]))->assertRedirect(route('admin.content.index', 'resources'));

        $this->assertModelMissing($resource);
        Storage::disk('local')->assertMissing($resource->file_path);
    }

    public function test_a_failed_replacement_preserves_the_current_document_and_removes_the_new_upload(): void
    {
        Storage::fake('local');
        $admin = User::factory()->create(['is_admin' => true, 'is_active' => true]);
        $resource = EventResource::factory()->create();
        Storage::disk('local')->put($resource->file_path, 'Original document');
        EventResource::saving(function (): void {
            throw new RuntimeException('Unable to persist the resource.');
        });

        try {
            $this->actingAs($admin)->put(route('admin.content.update', ['resources', $resource]), array_merge($this->payload(), [
                'document' => UploadedFile::fake()->createWithContent('replacement.pdf', "%PDF-1.4\nNew document"),
            ]))->assertServerError();
        } finally {
            EventResource::flushEventListeners();
        }

        $this->assertSame($resource->file_path, $resource->fresh()->file_path);
        $this->assertSame('Original document', Storage::disk('local')->get($resource->file_path));
        Storage::disk('local')->assertCount('event-resources', 1);
    }

    #[TestWith(['malicious.pdf', 'text/html', 1])]
    #[TestWith(['programme.exe', 'application/pdf', 1])]
    #[TestWith(['oversized.pdf', 'application/pdf', 20481])]
    public function test_invalid_uploads_are_rejected_without_storing_files(string $filename, string $mimeType, int $size): void
    {
        Storage::fake('local');
        $admin = User::factory()->create(['is_admin' => true, 'is_active' => true]);

        $this->actingAs($admin)->post(route('admin.content.store', 'resources'), array_merge($this->payload(), [
            'document' => UploadedFile::fake()->create($filename, $size, $mimeType),
        ]))->assertSessionHasErrors('document');

        $this->assertDatabaseCount('event_resources', 0);
        Storage::disk('local')->assertDirectoryEmpty('event-resources');
    }

    public function test_required_document_and_metadata_are_validated(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'is_active' => true]);

        $this->actingAs($admin)->post(route('admin.content.store', 'resources'), [
            'category' => 'executable', 'language' => 'invalid', 'event_id' => 12345,
        ])->assertSessionHasErrors(['translations.en.title', 'document', 'category', 'language', 'event_id']);

        $this->assertDatabaseCount('event_resources', 0);
    }

    public function test_visitors_can_download_published_resources_as_attachments(): void
    {
        Storage::fake('local');
        $resource = EventResource::factory()->for(Event::factory())->create();
        Storage::disk('local')->put($resource->file_path, 'Public programme');

        $this->get(route('resources.download', ['locale' => 'fr', 'resource' => $resource]))
            ->assertDownload('programme-outline.pdf')
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertStreamedContent('Public programme');
    }

    public function test_draft_resources_unpublished_events_and_missing_files_return_not_found(): void
    {
        Storage::fake('local');
        $draft = EventResource::factory()->create(['is_published' => false]);
        $hiddenEvent = EventResource::factory()->for(Event::factory()->state(['is_published' => false]))->create();
        $missingFile = EventResource::factory()->create();
        Storage::disk('local')->put($draft->file_path, 'Draft');
        Storage::disk('local')->put($hiddenEvent->file_path, 'Hidden event');

        foreach ([$draft->id, $hiddenEvent->id, $missingFile->id, '9223372036854775808', 'invalid'] as $resource) {
            $this->get('/en/resources/'.$resource.'/download')->assertNotFound();
        }
    }

    public function test_only_active_administrators_can_manage_and_preview_draft_resources(): void
    {
        Storage::fake('local');
        $resource = EventResource::factory()->create(['is_published' => false]);
        Storage::disk('local')->put($resource->file_path, 'Draft programme');
        $download = route('admin.resources.download', $resource);

        $this->get($download)->assertRedirect(route('login'));
        $this->post(route('admin.content.store', 'resources'), [])->assertRedirect(route('login'));

        foreach ([[false, true], [true, false]] as [$isAdmin, $isActive]) {
            $user = User::factory()->create(['is_admin' => $isAdmin, 'is_active' => $isActive]);
            $this->actingAs($user)->get($download)->assertForbidden();
            $this->post(route('admin.content.store', 'resources'), [])->assertForbidden();
            $this->delete(route('admin.content.destroy', ['resources', $resource]))->assertForbidden();
        }

        $admin = User::factory()->create(['is_admin' => true, 'is_active' => true]);
        $this->actingAs($admin)->get($download)->assertDownload('programme-outline.pdf')->assertStreamedContent('Draft programme');
        $this->get(route('admin.content.edit', ['resources', $resource]))->assertSee('programme-outline.pdf');
        $this->assertModelExists($resource);
    }

    public function test_deleting_an_event_removes_its_resource_downloads_and_hides_its_sessions(): void
    {
        Storage::fake('local');
        $admin = User::factory()->create(['is_admin' => true, 'is_active' => true]);
        $event = Event::factory()->create();
        $resource = EventResource::factory()->for($event)->create();
        $session = $event->sessions()->create(['title' => ['en' => 'Event agenda'], 'format' => 'plenary', 'is_published' => true]);
        Storage::disk('local')->put($resource->file_path, 'Event programme');

        $this->actingAs($admin)->delete(route('admin.content.destroy', ['events', $event]))->assertRedirect(route('admin.content.index', 'events'));

        $this->assertModelMissing($event);
        $this->assertModelMissing($resource);
        Storage::disk('local')->assertMissing($resource->file_path);
        $this->assertFalse($session->fresh()->is_published);
    }

    public function test_resource_search_and_filters_exclude_drafts_and_match_translations(): void
    {
        $event = Event::factory()->create();
        $match = EventResource::factory()->for($event)->create([
            'title' => ['en' => 'Event brief', 'fr' => 'Récolte durable'], 'language' => 'fr', 'category' => 'brief',
        ]);
        EventResource::factory()->create(['title' => ['fr' => 'Récolte durable'], 'language' => 'en', 'category' => 'brief']);
        EventResource::factory()->for($event)->create(['title' => ['fr' => 'Récolte durable'], 'language' => 'fr', 'category' => 'brief', 'is_published' => false]);
        EventResource::factory()->for(Event::factory()->state(['is_published' => false]))->create(['title' => ['fr' => 'Récolte durable'], 'language' => 'fr', 'category' => 'brief']);

        $this->get('/fr/resources?'.http_build_query(['q' => 'Récolte', 'category' => 'brief', 'language' => 'fr', 'event' => $event->id]))
            ->assertViewHas('resources', fn ($resources): bool => $resources->modelKeys() === [$match->id])
            ->assertDontSee($match->file_path);

        $this->get('/en/resources')->assertViewHas('resources', fn ($resources): bool => $resources->total() === 2);
    }

    public function test_array_resource_filters_return_validation_errors(): void
    {
        $this->getJson('/en/resources?q[]=invalid&category[]=brief&language[]=en&event[]=1')
            ->assertUnprocessable()->assertJsonValidationErrors(['q', 'category', 'language', 'event']);
    }

    public function test_day_programmes_can_be_saved_and_draft_event_sessions_are_hidden(): void
    {
        $this->freezeTime();
        $admin = User::factory()->create(['is_admin' => true, 'is_active' => true]);
        $event = Event::factory()->create(['is_published' => false]);

        $this->actingAs($admin)->post(route('admin.content.store', 'sessions'), [
            'event_id' => $event->id, 'translations' => ['en' => ['title' => 'Technical meetings']],
            'start_at' => now()->addDay()->startOfDay()->toDateTimeString(), 'format' => 'plenary', 'is_all_day' => '1', 'is_published' => '1',
        ])->assertSessionHasNoErrors();

        $this->assertTrue(Session::firstOrFail()->is_all_day);
        $this->get('/en')->assertViewHas('sessions', fn ($sessions): bool => $sessions->isEmpty());
        $this->get('/en/program-outline')->assertViewHas('sessions', fn ($sessions): bool => $sessions->isEmpty());
    }

    #[TestWith(['javascript:alert(1)'])]
    #[TestWith(['data:text/html,test'])]
    #[TestWith(['/videos/fsrp/../private.mp4'])]
    #[TestWith([['invalid']])]
    public function test_unsafe_video_urls_are_rejected(mixed $videoUrl): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'is_active' => true]);

        $this->actingAs($admin)->post(route('admin.content.store', 'slides'), [
            'translations' => ['en' => ['title' => 'Regional food security']], 'video_url' => $videoUrl,
        ])->assertSessionHasErrors('video_url');

        $this->assertDatabaseCount('slides', 0);
    }

    #[TestWith(['https://fsrp.africa/video.mp4'])]
    #[TestWith(['/videos/fsrp/food-security.mp4'])]
    public function test_background_video_urls_can_be_saved(string $videoUrl): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'is_active' => true]);

        $this->actingAs($admin)->post(route('admin.content.store', 'slides'), [
            'translations' => ['en' => ['title' => 'Regional food security']], 'video_url' => $videoUrl,
        ])->assertSessionHasNoErrors();

        $this->assertSame($videoUrl, Slide::firstOrFail()->video_url);
    }

    #[TestWith(['javascript:alert(1)'])]
    #[TestWith(['//example.com/events'])]
    #[TestWith(['/\\example.com/events'])]
    #[TestWith(['/%2fexample.com/events'])]
    #[TestWith(['/%5cexample.com/events'])]
    #[TestWith(['data:text/html,test'])]
    public function test_unsafe_slide_buttons_are_rejected_and_legacy_values_are_suppressed(string $buttonUrl): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'is_active' => true]);

        $this->actingAs($admin)->post(route('admin.content.store', 'slides'), [
            'translations' => ['en' => ['title' => 'Regional food security']], 'button_url' => $buttonUrl,
        ])->assertSessionHasErrors('button_url');

        $this->assertDatabaseCount('slides', 0);
        $this->assertNull((new Slide(['button_url' => $buttonUrl]))->buttonUrlForLocale('fr'));
    }

    #[TestWith(['/events', '/fr/events'])]
    #[TestWith(['/en/events?period=past#events', '/fr/events?period=past#events'])]
    #[TestWith(['/fr/events', '/fr/events'])]
    #[TestWith(['/', '/fr'])]
    #[TestWith(['/en', '/fr'])]
    #[TestWith(['/resources?q=food%20security', '/fr/resources?q=food%20security'])]
    #[TestWith(['https://fsrp.africa/events', 'https://fsrp.africa/events'])]
    #[TestWith(['http://example.test/events', 'http://example.test/events'])]
    public function test_slide_buttons_are_saved_and_follow_the_visitor_language(string $buttonUrl, string $expectedUrl): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'is_active' => true]);

        $this->actingAs($admin)->post(route('admin.content.store', 'slides'), [
            'translations' => ['en' => ['title' => 'Regional food security']], 'button_url' => $buttonUrl,
        ])->assertSessionHasNoErrors();

        $this->assertSame($expectedUrl, Slide::firstOrFail()->buttonUrlForLocale('fr'));
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(): array
    {
        return [
            'translations' => ['en' => ['title' => 'Programme outline'], 'fr' => ['title' => 'Programme français']],
            'category' => 'programme', 'language' => 'en', 'sort_order' => 0, 'is_published' => '1',
        ];
    }
}
