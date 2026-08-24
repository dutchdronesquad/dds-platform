<?php

use App\Actions\Admin\StoreMediaAsset;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
    Storage::fake('media');
});

test('it copies media to the target disk and updates the disk record', function () {
    $mediaAsset = (new StoreMediaAsset)->handle(UploadedFile::fake()->image('photo.jpg', 100, 80), null);
    $path = $mediaAsset->storagePath();

    Storage::disk('public')->assertExists($path);

    $this->pendingArtisan('dds:migrate-media-disk')->assertSuccessful();

    Storage::disk('media')->assertExists($path);
    Storage::disk('public')->assertExists($path);

    $media = $mediaAsset->file()?->fresh();

    expect($media?->disk)->toBe('media')
        ->and($media?->conversions_disk)->toBe('media');
});

test('it removes the source files when --delete-source is passed', function () {
    $mediaAsset = (new StoreMediaAsset)->handle(UploadedFile::fake()->image('photo.jpg', 100, 80), null);
    $path = $mediaAsset->storagePath();

    $this->pendingArtisan('dds:migrate-media-disk --delete-source')->assertSuccessful();

    Storage::disk('media')->assertExists($path);
    Storage::disk('public')->assertMissing($path);
});

test('it does nothing when there is no media on the source disk', function () {
    $this->pendingArtisan('dds:migrate-media-disk')
        ->expectsOutput('No media found on disk [public].')
        ->assertSuccessful();
});

test('it rejects identical from and to disks', function () {
    $this->pendingArtisan('dds:migrate-media-disk --to=public')
        ->expectsOutput('--from and --to must be different disks.')
        ->assertFailed();
});

test('the migration refuses to run in production without --force', function () {
    (new StoreMediaAsset)->handle(UploadedFile::fake()->image('photo.jpg', 100, 80), null);

    $originalEnvironment = app()->environment();
    app()->detectEnvironment(fn (): string => 'production');

    try {
        $this->pendingArtisan('dds:migrate-media-disk')
            ->expectsOutput('This migration does not run in production without --force.')
            ->assertFailed();
    } finally {
        app()->detectEnvironment(fn (): string => $originalEnvironment);
    }
});
