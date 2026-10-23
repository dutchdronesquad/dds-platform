<?php

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->originalBootstrapPath = app()->bootstrapPath();
    $this->releaseBootstrapPath = storage_path('framework/testing/release-'.Str::uuid());
    File::ensureDirectoryExists($this->releaseBootstrapPath.'/cache');
    app()->useBootstrapPath($this->releaseBootstrapPath);
});

afterEach(function () {
    app()->useBootstrapPath($this->originalBootstrapPath);
    File::deleteDirectory($this->releaseBootstrapPath);
});

test('the build caches its exact release tag in application configuration', function () {
    Process::fake(["'git' 'tag' *" => "v1.4.0\ncodex-cloud-build-probe\nv1.5.0-beta.1\n"]);

    $this->pendingArtisan('dds:cache-release-version')->assertSuccessful();

    $appConfig = require config_path('app.php');
    expect($appConfig['version'])->toBe('v1.4.0');
    Process::assertRan(['git', 'tag', '--points-at', 'HEAD', '--list', 'v*']);
});

test('a build without exactly one stable tag fails without overwriting version metadata', function (string $tags) {
    Process::fake(["'git' 'tag' *" => $tags]);
    File::put(app()->bootstrapPath('cache/release-version.php'), "<?php return 'v1.3.0';\n");

    $this->pendingArtisan('dds:cache-release-version')
        ->expectsOutputToContain('exactly one stable release tag')
        ->assertFailed();

    $appConfig = require config_path('app.php');
    expect($appConfig['version'])->toBe('v1.3.0');
})->with([
    'untagged commit' => '',
    'pre-release only' => "v1.4.0-rc.1\n",
    'invalid version' => "v01.4.0\n",
    'multiple releases on the same commit' => "v1.3.0\nv1.4.0\n",
]);

test('a build without a Git repository fails without creating version metadata', function () {
    Process::fake(["'git' 'tag' *" => Process::result(errorOutput: 'not a git repository', exitCode: 128)]);

    $this->pendingArtisan('dds:cache-release-version')
        ->expectsOutputToContain('Cannot read Git tags')
        ->assertFailed();

    expect(File::exists(app()->bootstrapPath('cache/release-version.php')))->toBeFalse();
});

test('a checkout without build metadata reports a development version', function () {
    $appConfig = require config_path('app.php');

    expect($appConfig['version'])->toBe('development');
});
