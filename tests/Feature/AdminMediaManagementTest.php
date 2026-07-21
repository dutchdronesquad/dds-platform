<?php

use App\Actions\Admin\StoreMediaAsset;
use App\Enums\EventRegistrationStatus;
use App\Enums\EventType;
use App\Enums\Role;
use App\Models\Event;
use App\Models\Location;
use App\Models\MediaAsset;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->withoutVite();
    $this->seed(RolesAndPermissionsSeeder::class);
    Storage::fake('public');
});

test('media management requires a management role and media permission', function () {
    $this->get(route('admin.media.index'))->assertRedirect(route('login'));

    $user = User::factory()->create();
    $editor = User::factory()->create();
    $editor->assignRole(Role::Editor->value);
    $mediaAsset = MediaAsset::factory()->create();

    $this->actingAs($user)
        ->get(route('admin.media.index'))
        ->assertForbidden();

    expect($editor->can('view', $mediaAsset))->toBeTrue()
        ->and($editor->can('create', MediaAsset::class))->toBeTrue()
        ->and($editor->can('update', $mediaAsset))->toBeTrue()
        ->and($editor->can('delete', $mediaAsset))->toBeFalse();
});

test('admins can upload images with stable storage identity and localized alt text', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin->value);

    $response = $this->actingAs($admin)->post(route('admin.media.store'), [
        'file' => UploadedFile::fake()->image('Race Poster.jpg', 1600, 900)->size(250),
        'alt_text' => [
            'en' => '  Pilots racing indoors.  ',
            'nl' => 'Piloten racen binnen.',
        ],
    ]);

    $response->assertSessionHasNoErrors();

    $mediaAsset = MediaAsset::query()->sole();

    $response
        ->assertRedirect(route('admin.media.index'))
        ->assertInertiaFlash('toast', [
            'type' => 'success',
            'message' => 'Bestand toegevoegd aan de mediabibliotheek.',
        ]);

    expect($mediaAsset->filename())->toBe('Race Poster.jpg')
        ->and($mediaAsset->mimeType())->toBe('image/jpeg')
        ->and($mediaAsset->width())->toBe(1600)
        ->and($mediaAsset->height())->toBe(900)
        ->and($mediaAsset->archived_at)->toBeNull()
        ->and($mediaAsset->alt_text)->toBe([
            'en' => 'Pilots racing indoors.',
            'nl' => 'Piloten racen binnen.',
        ])
        ->and($mediaAsset->storagePath())
        ->toMatch('#^media-library/\d+/Race-Poster\.jpg$#');

    Storage::disk('public')->assertExists($mediaAsset->storagePath());
});

test('image dimension detection tolerates an unavailable temporary path', function () {
    $file = new class(__FILE__) extends UploadedFile
    {
        public function __construct(string $path)
        {
            parent::__construct($path, 'missing-image.jpg', 'image/jpeg', null, true);
        }

        public function getMimeType(): ?string
        {
            return 'image/jpeg';
        }

        public function getRealPath(): string|false
        {
            return false;
        }
    };
    $method = new ReflectionMethod(StoreMediaAsset::class, 'imageDimensions');

    expect($method->invoke(new StoreMediaAsset, $file))->toBe([null, null]);
});

test('admins can upload pdfs without image alt text or dimensions', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin->value);

    $response = $this->actingAs($admin)->post(route('admin.media.store'), [
        'file' => UploadedFile::fake()->createWithContent(
            'Wedstrijdreglement.pdf',
            "%PDF-1.4\n1 0 obj\n<<>>\nendobj\n%%EOF",
        ),
        'alt_text' => [
            'nl' => 'Deze tekst hoort niet bij een pdf.',
        ],
        'return_to' => 'library',
    ]);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect(route('admin.media.index'));

    $mediaAsset = MediaAsset::query()->sole();

    expect($mediaAsset->mimeType())->toBe('application/pdf')
        ->and($mediaAsset->width())->toBeNull()
        ->and($mediaAsset->height())->toBeNull()
        ->and($mediaAsset->alt_text)->toBeNull()
        ->and($mediaAsset->storagePath())->toEndWith('.pdf');

    Storage::disk('public')->assertExists($mediaAsset->storagePath());
});

test('uploads reject unsupported and oversized files', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin->value);

    $this->actingAs($admin)
        ->post(route('admin.media.store'), [
            'file' => UploadedFile::fake()->create('script.svg', 10, 'image/svg+xml'),
        ])
        ->assertSessionHasErrors('file');

    $this->post(route('admin.media.store'), [
        'file' => UploadedFile::fake()->create('huge.pdf', 20_481, 'application/pdf'),
    ])->assertSessionHasErrors('file');

    expect(MediaAsset::query()->count())->toBe(0);
});

test('media tables start in their final package-native shape', function () {
    expect(Schema::getColumnListing('media_assets'))
        ->toContain('id', 'alt_text', 'archived_at', 'created_at', 'updated_at')
        ->not->toContain(
            'disk',
            'path',
            'original_filename',
            'mime_type',
            'size_bytes',
            'width',
            'height',
        )
        ->and(Schema::hasTable('media'))->toBeTrue();
});

test('the media index searches and filters by type usage and archive state', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin->value);
    $usedImage = MediaAsset::factory()->named('winter-race.jpg')->create();
    Event::factory()->create(['cover_image_id' => $usedImage->id]);
    MediaAsset::factory()->named('unused-training.jpg')->create();
    MediaAsset::factory()->pdf('reglement.pdf')->create();
    MediaAsset::factory()->archived()->named('old-race.jpg')->create();

    $this->actingAs($admin)
        ->get(route('admin.media.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/media/index')
            ->where('summary.total', 3)
            ->where('summary.images', 2)
            ->where('summary.documents', 1)
            ->where('summary.unused', 2)
            ->where('summary.archived', 1)
            ->where('filters.status', 'active')
            ->has('locales', 2)
            ->has('mediaAssets.data', 3)
            ->where('canCreate', true),
        );

    $this->get(route('admin.media.index', [
        'search' => 'WINTER',
        'category' => 'image',
        'usage' => 'used',
        'status' => 'all',
    ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('filters.search', 'WINTER')
            ->where('filters.category', 'image')
            ->where('filters.usage', 'used')
            ->where('filters.status', 'all')
            ->has('mediaAssets.data', 1)
            ->where('mediaAssets.data.0.id', $usedImage->id)
            ->where('mediaAssets.data.0.usageCount', 1)
            ->where('mediaAssets.data.0.capabilities.delete', false),
        );

    $this->get(route('admin.media.index', ['status' => 'archived']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('mediaAssets.data', 1)
            ->where('mediaAssets.data.0.filename', 'old-race.jpg'),
        );
});

test('admins can inspect media details and edit alt text without leaving the index', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin->value);
    $mediaAsset = MediaAsset::factory()->create(['alt_text' => null]);
    $event = Event::factory()->create([
        'title' => 'Indoor race',
        'cover_image_id' => $mediaAsset->id,
    ]);

    $this->actingAs($admin)
        ->getJson(route('admin.media.show', $mediaAsset))
        ->assertOk()
        ->assertJsonPath('data.id', $mediaAsset->id)
        ->assertJsonPath('data.usageCount', 1)
        ->assertJsonPath('data.usage.0.type', 'Event')
        ->assertJsonPath('data.usage.0.label', 'Indoor race')
        ->assertJsonPath('data.usage.0.href', route('admin.events.edit', $event))
        ->assertJsonPath('data.capabilities.delete', false);

    $this->from(route('admin.media.index'))
        ->put(route('admin.media.update', $mediaAsset), [
            'alt_text' => ['nl' => 'Een indoor FPV-race.'],
        ])->assertRedirect(route('admin.media.index'));

    expect($mediaAsset->refresh()->alt_text)->toBe([
        'nl' => 'Een indoor FPV-race.',
    ]);

    $this->patch(route('admin.media.archive', $mediaAsset))
        ->assertRedirect();

    expect($mediaAsset->refresh()->archived_at)->not->toBeNull()
        ->and($event->refresh()->cover_image_id)->toBe($mediaAsset->id);

    $this->patch(route('admin.media.restore', $mediaAsset))
        ->assertRedirect();

    expect($mediaAsset->refresh()->archived_at)->toBeNull();
});

test('definitive deletion removes only unused records and stored files', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin->value);
    $usedAsset = MediaAsset::factory()->create();
    $unusedAsset = MediaAsset::factory()->create();
    Event::factory()->create(['cover_image_id' => $usedAsset->id]);
    Storage::disk('public')->put($usedAsset->storagePath(), 'used');
    Storage::disk('public')->put($unusedAsset->storagePath(), 'unused');

    $this->actingAs($admin)
        ->delete(route('admin.media.destroy', $usedAsset))
        ->assertSessionHasErrors('mediaAsset');

    $this->assertModelExists($usedAsset);
    Storage::disk('public')->assertExists($usedAsset->storagePath());

    $this->delete(route('admin.media.destroy', $unusedAsset))
        ->assertRedirect(route('admin.media.index'));

    $this->assertModelMissing($unusedAsset);
    Storage::disk('public')->assertMissing($unusedAsset->storagePath());
});

test('the reusable picker searches active images and excludes documents and archived media', function () {
    $editor = User::factory()->create();
    $editor->assignRole(Role::Editor->value);
    $image = MediaAsset::factory()->named('community-race.jpg')->create([
        'alt_text' => ['nl' => 'Piloten op de startgrid'],
    ]);
    MediaAsset::factory()->pdf('community-race.pdf')->create();
    MediaAsset::factory()->archived()->named('community-race-old.jpg')->create();

    $this->actingAs($editor)
        ->getJson(route('admin.media.picker', ['search' => 'community']))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $image->id)
        ->assertJsonPath('data.0.filename', 'community-race.jpg');

    $this->getJson(route('admin.media.picker', ['search' => 'startgrid']))
        ->assertOk()
        ->assertJsonPath('data.0.id', $image->id);
});

test('event forms select reusable active media and retain an already archived cover', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin->value);
    $location = Location::factory()->create();
    $coverImage = MediaAsset::factory()->named('event-cover.jpg')->create();

    $this->actingAs($admin)
        ->post(route('admin.events.store'), eventPayload($location, [
            'cover_image_id' => $coverImage->id,
        ]))
        ->assertRedirect();

    $event = Event::query()->where('slug', 'media-event')->firstOrFail();

    expect($event->cover_image_id)->toBe($coverImage->id);

    $forceMediaLibraryLazyLoading = config('media-library.force_lazy_loading');

    config()->set('media-library.force_lazy_loading', false);
    Model::preventLazyLoading();

    try {
        $response = $this->get(route('admin.events.edit', $event));
    } finally {
        Model::preventLazyLoading(false);
        config()->set('media-library.force_lazy_loading', $forceMediaLibraryLazyLoading);
    }

    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('event.coverImageId', $coverImage->id)
            ->where('event.coverImage.id', $coverImage->id)
            ->where('event.coverImage.filename', 'event-cover.jpg'),
        );

    $coverImage->update(['archived_at' => now()]);

    $this->put(route('admin.events.update', $event), eventPayload($location, [
        'cover_image_id' => $coverImage->id,
        'title' => 'Media event bijgewerkt',
    ]))->assertRedirect(route('admin.events.edit', $event));

    expect($event->refresh()->cover_image_id)->toBe($coverImage->id);

    $otherArchivedImage = MediaAsset::factory()->archived()->create();

    $this->put(route('admin.events.update', $event), eventPayload($location, [
        'cover_image_id' => $otherArchivedImage->id,
    ]))->assertSessionHasErrors('cover_image_id');

    $pdf = MediaAsset::factory()->pdf()->create();

    $this->put(route('admin.events.update', $event), eventPayload($location, [
        'cover_image_id' => $pdf->id,
    ]))->assertSessionHasErrors('cover_image_id');
});

/** @return array<string, mixed> */
function eventPayload(Location $location, array $overrides = []): array
{
    return [
        'location_id' => $location->id,
        'title' => 'Media event',
        'slug' => 'media-event',
        'starts_at' => now()->addDay()->toIso8601String(),
        'type' => EventType::Training->value,
        'registration_status' => EventRegistrationStatus::Closed->value,
        ...$overrides,
    ];
}
