<?php

use App\Support\WordPressSourceRepository;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

beforeEach(function () {
    Http::preventStrayRequests();
    $this->snapshotDirectory = storage_path('app/wordpress-source-'.Str::uuid());
    File::ensureDirectoryExists($this->snapshotDirectory);
});

afterEach(function () {
    File::deleteDirectory($this->snapshotDirectory);
});

test('it reads and caches records from a verified source bundle', function () {
    $records = [['id' => 12, 'slug' => 'race'], ['id' => 13, 'slug' => 'training']];
    writeSourceBundle($this->snapshotDirectory, 'posts', $records);
    $repository = new WordPressSourceRepository;
    $manifest = sourceBundleManifest($this->snapshotDirectory);

    expect($repository->records($manifest, 'posts', 'https://unused.example/posts'))->toBe($records)
        ->and($repository->record($manifest, 'posts', 'https://unused.example/posts', 13))
        ->toBe($records[1])
        ->and($repository->record($manifest, 'posts', 'https://unused.example/posts', 99))
        ->toBe('WordPress bronbundel bevat geen posts-record met ID 99.');

    File::delete($this->snapshotDirectory.'/snapshot.json');

    expect($repository->records($manifest, 'posts', 'https://unused.example/posts'))->toBe($records);
    Http::assertNothingSent();
});

test('it reports invalid source bundle manifests and collections', function (
    Closure $corrupt,
    string $message,
    string $exceptionClass = RuntimeException::class,
) {
    writeSourceBundle($this->snapshotDirectory, 'posts', [['id' => 12]]);
    $corrupt($this->snapshotDirectory);
    $repository = new WordPressSourceRepository;

    expect(fn () => $repository->records(
        sourceBundleManifest($this->snapshotDirectory),
        'posts',
        'https://unused.example/posts',
    ))->toThrow($exceptionClass, $message);
})->with([
    'missing bundle manifest' => [
        fn (string $directory) => File::delete($directory.'/snapshot.json'),
        'WordPress bronbundelmanifest ontbreekt:',
    ],
    'invalid JSON bundle manifest' => [
        fn (string $directory) => File::put($directory.'/snapshot.json', '{'),
        'Syntax error',
        JsonException::class,
    ],
    'unknown bundle schema' => [
        function (string $directory) {
            File::put($directory.'/snapshot.json', json_encode(['schema_version' => 2], JSON_THROW_ON_ERROR));
        },
        'WordPress bronbundel heeft een onbekende schemaversie.',
    ],
    'missing collection metadata' => [
        function (string $directory) {
            File::put($directory.'/snapshot.json', json_encode(['schema_version' => 1], JSON_THROW_ON_ERROR));
        },
        'WordPress bronbundel mist de posts-inventaris.',
    ],
    'unsafe collection path' => [
        fn (string $directory) => replaceSourceBundleCollection($directory, '../posts.json', hash('sha256', '')),
        'WordPress bronbundel bevat een onveilig relatief pad.',
    ],
    'missing collection file' => [
        function (string $directory) {
            File::delete($directory.'/posts.json');
        },
        'WordPress bronbestand ontbreekt: posts.json.',
    ],
    'incorrect collection checksum' => [
        fn (string $directory) => replaceSourceBundleCollection($directory, 'posts.json', str_repeat('0', 64)),
        'Checksum van WordPress posts-inventaris klopt niet.',
    ],
    'invalid collection JSON' => [
        function (string $directory) {
            File::put($directory.'/posts.json', '{');
            replaceSourceBundleCollection($directory, 'posts.json', hash('sha256', '{'));
        },
        'Syntax error',
        JsonException::class,
    ],
    'non-list collection' => [
        function (string $directory) {
            File::put($directory.'/posts.json', '{"id":12}');
            replaceSourceBundleCollection($directory, 'posts.json', hash('sha256', '{"id":12}'));
        },
        'WordPress bronbundel bevat geen geldige posts-inventaris.',
    ],
]);

test('record turns a damaged source bundle into an importer error', function () {
    File::put($this->snapshotDirectory.'/snapshot.json', '{');

    expect((new WordPressSourceRepository)->record(
        sourceBundleManifest($this->snapshotDirectory),
        'posts',
        'https://unused.example/posts',
        12,
    ))->toBe('Syntax error');
});

test('it handles successful and malformed REST records', function () {
    Http::fake([
        'legacy.example/posts/12' => Http::response(['id' => 12, 'slug' => 'race']),
        'legacy.example/posts/13' => Http::response('null'),
        'legacy.example/posts*' => Http::response([['id' => 12], ['id' => 13]]),
    ]);
    $repository = new WordPressSourceRepository;

    expect($repository->record([], 'posts', 'https://legacy.example/posts', 12))
        ->toBe(['id' => 12, 'slug' => 'race'])
        ->and($repository->record([], 'posts', 'https://legacy.example/posts', 13))
        ->toBe('WordPress REST gaf geen geldig posts-record terug.')
        ->and($repository->records([], 'posts', 'https://legacy.example/posts'))
        ->toBe([['id' => 12], ['id' => 13]]);
});

test('it reports REST HTTP and connection failures', function () {
    Http::fake(['legacy.example/posts*' => Http::response([], 503)]);
    $repository = new WordPressSourceRepository;

    expect($repository->record([], 'posts', 'https://legacy.example/posts', 12))
        ->toBe('WordPress REST-aanvraag mislukt met HTTP 503.')
        ->and(fn () => $repository->records([], 'posts', 'https://legacy.example/posts'))
        ->toThrow(RuntimeException::class, 'WordPress REST-aanvraag mislukt met HTTP 503.');

    Http::fake(['legacy.example/posts*' => Http::failedConnection('offline')]);

    expect($repository->record([], 'posts', 'https://legacy.example/posts', 12))
        ->toContain('WordPress REST-verbinding mislukt: offline')
        ->and(fn () => $repository->records([], 'posts', 'https://legacy.example/posts'))
        ->toThrow(RuntimeException::class, 'WordPress REST-verbinding mislukt: offline');
});

test('it rejects a malformed REST inventory', function () {
    Http::fake(['legacy.example/posts*' => Http::response(['id' => 12])]);

    expect(fn () => (new WordPressSourceRepository)->records(
        [],
        'posts',
        'https://legacy.example/posts',
    ))->toThrow(RuntimeException::class, 'WordPress REST gaf geen geldige posts-inventaris terug.');
});

test('it validates downloaded media contents', function () {
    Http::fake([
        'legacy.example/empty.png' => Http::response(''),
        'legacy.example/text.png' => Http::response('not an image'),
        'legacy.example/image.png' => Http::response(sourceRepositoryPng()),
        'legacy.example/failure.png' => Http::response('', 404),
    ]);
    $repository = new WordPressSourceRepository;

    expect($repository->mediaContents([], 1, 'https://legacy.example/empty.png', 'image/png'))
        ->toBe(['error' => 'Download gaf een leeg bestand terug: https://legacy.example/empty.png'])
        ->and(data_get($repository->mediaContents([], 1, 'https://legacy.example/text.png', 'image/png'), 'error'))
        ->toContain('Gedetecteerd MIME-type text/plain wijkt af van image/png')
        ->and($repository->mediaContents([], 1, 'https://legacy.example/image.png', 'image/png'))
        ->toBe(['contents' => sourceRepositoryPng()])
        ->and($repository->mediaContents([], 1, 'https://legacy.example/failure.png', 'image/png'))
        ->toBe(['error' => 'Download mislukt met HTTP 404: https://legacy.example/failure.png']);

    config()->set('media-library.max_file_size', 1);

    expect($repository->mediaContents([], 1, 'https://legacy.example/image.png', 'image/png'))
        ->toBe(['error' => 'Download is groter dan de toegestane 1 bytes: https://legacy.example/image.png']);
});

test('it reports failed media download connections', function () {
    Http::fake(['legacy.example/image.png' => Http::failedConnection('offline')]);

    expect(data_get((new WordPressSourceRepository)->mediaContents(
        [],
        1,
        'https://legacy.example/image.png',
        'image/png',
    ), 'error'))->toContain('Downloadverbinding mislukt: offline');
});

test('it verifies media stored in the source bundle', function () {
    $contents = sourceRepositoryPng();
    File::ensureDirectoryExists($this->snapshotDirectory.'/media');
    File::put($this->snapshotDirectory.'/media/12.png', $contents);
    writeSourceBundle($this->snapshotDirectory, 'media', [['id' => 12]], [
        '12' => [
            'path' => 'media/12.png',
            'checksum_sha256' => hash('sha256', $contents),
        ],
    ]);

    expect((new WordPressSourceRepository)->mediaContents(
        sourceBundleManifest($this->snapshotDirectory),
        12,
        'https://legacy.example/image.png',
        'image/png',
    ))->toBe(['contents' => $contents]);
});

test('it reports missing and damaged source bundle media', function (Closure $corrupt, string $message) {
    $contents = sourceRepositoryPng();
    File::ensureDirectoryExists($this->snapshotDirectory.'/media');
    File::put($this->snapshotDirectory.'/media/12.png', $contents);
    writeSourceBundle($this->snapshotDirectory, 'media', [['id' => 12]], [
        '12' => [
            'path' => 'media/12.png',
            'checksum_sha256' => hash('sha256', $contents),
        ],
    ]);
    $corrupt($this->snapshotDirectory);

    expect((new WordPressSourceRepository)->mediaContents(
        sourceBundleManifest($this->snapshotDirectory),
        12,
        'https://legacy.example/image.png',
        'image/png',
    ))->toBe(['error' => $message]);
})->with([
    'missing metadata' => [
        function (string $directory) {
            replaceSourceBundleMedia($directory, []);
        },
        'WordPress bronbundel mist mediabestand 12.',
    ],
    'unsafe path' => [
        function (string $directory) {
            replaceSourceBundleMedia($directory, [
                '12' => ['path' => '../12.png', 'checksum_sha256' => str_repeat('0', 64)],
            ]);
        },
        'WordPress bronbundel bevat een onveilig relatief pad.',
    ],
    'missing file' => [
        fn (string $directory) => File::delete($directory.'/media/12.png'),
        'WordPress bronbundelbestand ontbreekt: media/12.png.',
    ],
    'incorrect checksum' => [
        function (string $directory) {
            replaceSourceBundleMedia($directory, [
                '12' => ['path' => 'media/12.png', 'checksum_sha256' => str_repeat('0', 64)],
            ]);
        },
        'Checksum van WordPress mediabestand 12 klopt niet.',
    ],
]);

/** @return array<string, mixed> */
function sourceBundleManifest(string $directory): array
{
    return ['source' => ['snapshot_directory' => $directory]];
}

/**
 * @param  list<array<string, mixed>>  $records
 * @param  array<int|string, array{path: string, checksum_sha256: string}>  $mediaFiles
 */
function writeSourceBundle(string $directory, string $type, array $records, array $mediaFiles = []): void
{
    $contents = json_encode($records, JSON_THROW_ON_ERROR);
    File::put($directory."/{$type}.json", $contents);
    File::put($directory.'/snapshot.json', json_encode([
        'schema_version' => 1,
        'collections' => [
            $type => [
                'path' => "{$type}.json",
                'checksum_sha256' => hash('sha256', $contents),
            ],
        ],
        'media_files' => $mediaFiles,
    ], JSON_THROW_ON_ERROR));
}

function replaceSourceBundleCollection(string $directory, string $path, string $checksum): void
{
    $snapshot = json_decode(File::get($directory.'/snapshot.json'), true, flags: JSON_THROW_ON_ERROR);
    data_set($snapshot, 'collections.posts.path', $path);
    data_set($snapshot, 'collections.posts.checksum_sha256', $checksum);
    File::put($directory.'/snapshot.json', json_encode($snapshot, JSON_THROW_ON_ERROR));
}

/** @param array<int|string, array{path: string, checksum_sha256: string}> $mediaFiles */
function replaceSourceBundleMedia(string $directory, array $mediaFiles): void
{
    $snapshot = json_decode(File::get($directory.'/snapshot.json'), true, flags: JSON_THROW_ON_ERROR);
    $snapshot['media_files'] = $mediaFiles;
    File::put($directory.'/snapshot.json', json_encode($snapshot, JSON_THROW_ON_ERROR));
}

function sourceRepositoryPng(): string
{
    return (string) base64_decode(
        'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9Wl2nkwAAAAASUVORK5CYII=',
        strict: true,
    );
}
