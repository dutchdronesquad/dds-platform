<?php

use Illuminate\Support\Facades\File;

test('it directly requires the managed queue runtime dependency', function () {
    $composer = json_decode(
        File::get(base_path('composer.json')),
        associative: true,
        flags: JSON_THROW_ON_ERROR,
    );

    expect(data_get($composer, 'require.aws/aws-sdk-php'))->toBe('^3.0')
        ->and(version_compare(app()->version(), '13.19.0', '>='))->toBeTrue();
});

test('it configures media and backups with independent bucket credentials', function () {
    $variables = [
        'MEDIA_AWS_ACCESS_KEY_ID' => 'media-key',
        'MEDIA_AWS_SECRET_ACCESS_KEY' => 'media-secret',
        'MEDIA_AWS_BUCKET' => 'dds-production-media',
        'MEDIA_AWS_ENDPOINT' => 'https://account-id.r2.cloudflarestorage.com',
        'MEDIA_AWS_URL' => 'https://media.dutchdronesquad.nl',
        'BACKUP_AWS_ACCESS_KEY_ID' => 'backup-key',
        'BACKUP_AWS_SECRET_ACCESS_KEY' => 'backup-secret',
        'BACKUP_AWS_BUCKET' => 'dds-production-backups',
        'BACKUP_AWS_ENDPOINT' => 'https://account-id.r2.cloudflarestorage.com',
    ];
    $previousServerValues = [];

    foreach ($variables as $name => $value) {
        $previousServerValues[$name] = [
            'exists' => array_key_exists($name, $_SERVER),
            'value' => $_SERVER[$name] ?? null,
        ];
        $_SERVER[$name] = $value;
    }

    try {
        $filesystems = require config_path('filesystems.php');

        expect(data_get($filesystems, 'disks.media'))
            ->toMatchArray([
                'key' => 'media-key',
                'secret' => 'media-secret',
                'bucket' => 'dds-production-media',
                'url' => 'https://media.dutchdronesquad.nl',
            ])
            ->and(data_get($filesystems, 'disks.backups'))
            ->toMatchArray([
                'key' => 'backup-key',
                'secret' => 'backup-secret',
                'bucket' => 'dds-production-backups',
            ])
            ->not->toHaveKey('url');
    } finally {
        foreach ($previousServerValues as $name => $previousValue) {
            if (! $previousValue['exists']) {
                unset($_SERVER[$name]);

                continue;
            }

            $_SERVER[$name] = $previousValue['value'];
        }
    }
});
