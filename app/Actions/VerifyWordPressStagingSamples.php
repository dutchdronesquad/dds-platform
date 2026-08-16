<?php

namespace App\Actions;

use App\Models\Article;
use App\Models\MediaAsset;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use JsonException;

final class VerifyWordPressStagingSamples
{
    /** @var list<string> */
    private const array REQUIRED_SAMPLE_TYPES = [
        'article',
        'media',
        'redirect',
        'location_page',
        'static_page',
    ];

    /**
     * @return list<array{
     *     type: string,
     *     reference: string,
     *     url: string,
     *     expected: string,
     *     actual: string,
     *     passed: bool,
     *     message: string
     * }>
     *
     * @throws JsonException
     */
    public function handle(string $manifestPath, string $baseUrl): array
    {
        $manifest = $this->readManifest($manifestPath);
        $samples = $this->samples($manifest, $baseUrl);
        $results = [];

        foreach (self::REQUIRED_SAMPLE_TYPES as $type) {
            if (! array_any($samples, fn (array $sample): bool => $sample['type'] === $type)) {
                $results[] = [
                    'type' => $type,
                    'reference' => 'missing',
                    'url' => '—',
                    'expected' => 'sample available',
                    'actual' => 'no mapped sample',
                    'passed' => false,
                    'message' => "Geen {$type}-sample beschikbaar voor stagingcontrole.",
                ];
            }
        }

        foreach ($samples as $sample) {
            $results[] = $this->verify($sample);
        }

        return $results;
    }

    /** @return array<string, mixed> */
    private function readManifest(string $manifestPath): array
    {
        if (! File::isFile($manifestPath)) {
            throw new InvalidArgumentException("Manifest niet gevonden: {$manifestPath}");
        }

        $manifest = json_decode(File::get($manifestPath), true, flags: JSON_THROW_ON_ERROR);

        if (! is_array($manifest)) {
            throw new InvalidArgumentException('Het WordPress-manifest moet een JSON-object bevatten.');
        }

        return $manifest;
    }

    /**
     * @param  array<string, mixed>  $manifest
     * @return list<array{type: string, reference: string, url: string, expected: string, redirect_target: string|null}>
     */
    private function samples(array $manifest, string $baseUrl): array
    {
        $samples = [];

        foreach (array_slice((array) Arr::get($manifest, 'mappings.posts', []), 0, 3, true) as $wordpressId => $mapping) {
            $articleId = is_array($mapping) ? Arr::get($mapping, 'article_id') : null;
            $article = is_int($articleId) ? Article::query()->find($articleId) : null;

            if ($article instanceof Article) {
                $samples[] = $this->sample('article', (string) $wordpressId, $baseUrl, '/news/'.$article->slug);
            }
        }

        foreach (array_slice((array) Arr::get($manifest, 'mappings.media', []), 0, 3, true) as $wordpressId => $mapping) {
            $mediaAssetId = is_array($mapping) ? Arr::get($mapping, 'media_asset_id') : null;
            $mediaAsset = is_int($mediaAssetId) ? MediaAsset::query()->find($mediaAssetId) : null;

            if ($mediaAsset instanceof MediaAsset && $mediaAsset->url() !== '') {
                $samples[] = $this->sample('media', (string) $wordpressId, $baseUrl, $mediaAsset->url());
            }
        }

        $pageTypeCounts = ['location_page' => 0, 'static_page' => 0];

        foreach ((array) Arr::get($manifest, 'mappings.pages', []) as $wordpressId => $mapping) {
            $targetType = is_array($mapping) ? Arr::get($mapping, 'target.type') : null;
            $targetPath = is_array($mapping) ? Arr::get($mapping, 'target.path') : null;
            $type = $targetType === 'location' ? 'location_page' : 'static_page';

            if (! in_array($targetType, ['location', 'route', 'manual'], true)
                || ! is_string($targetPath)
                || $pageTypeCounts[$type] >= 3) {
                continue;
            }

            $samples[] = $this->sample($type, (string) $wordpressId, $baseUrl, $targetPath);
            $pageTypeCounts[$type]++;
        }

        foreach (array_slice((array) Arr::get($manifest, 'mappings.redirects', []), 0, 3, true) as $mapping) {
            $sourcePath = is_array($mapping) ? Arr::get($mapping, 'source_path') : null;
            $targetUrl = is_array($mapping) ? Arr::get($mapping, 'target_url') : null;

            if (is_string($sourcePath) && is_string($targetUrl)) {
                $samples[] = [
                    ...$this->sample('redirect', $sourcePath, $baseUrl, $sourcePath),
                    'expected' => '301/302 → '.$targetUrl,
                    'redirect_target' => $targetUrl,
                ];
            }
        }

        return $this->uniqueSamples($samples);
    }

    /** @return array{type: string, reference: string, url: string, expected: string, redirect_target: null} */
    private function sample(string $type, string $reference, string $baseUrl, string $path): array
    {
        return [
            'type' => $type,
            'reference' => $reference,
            'url' => $this->stagingUrl($baseUrl, $path),
            'expected' => 'HTTP 200',
            'redirect_target' => null,
        ];
    }

    /**
     * @param  array{type: string, reference: string, url: string, expected: string, redirect_target: string|null}  $sample
     * @return array{type: string, reference: string, url: string, expected: string, actual: string, passed: bool, message: string}
     */
    private function verify(array $sample): array
    {
        try {
            $request = Http::accept('*/*')
                ->connectTimeout(5)
                ->timeout(15)
                ->retry([100, 250], throw: false);

            if ($sample['redirect_target'] !== null) {
                $request = $request->withOptions(['allow_redirects' => false]);
            }

            $response = $request->get($sample['url']);
        } catch (ConnectionException $exception) {
            return [
                'type' => $sample['type'],
                'reference' => $sample['reference'],
                'url' => $sample['url'],
                'expected' => $sample['expected'],
                'actual' => 'connection failed',
                'passed' => false,
                'message' => $exception->getMessage(),
            ];
        }

        if ($sample['redirect_target'] !== null) {
            $location = $response->header('Location');
            $passed = in_array($response->status(), [301, 302], true)
                && $this->pathAndQuery($location) === $sample['redirect_target'];

            return [
                'type' => $sample['type'],
                'reference' => $sample['reference'],
                'url' => $sample['url'],
                'expected' => $sample['expected'],
                'actual' => "HTTP {$response->status()} → {$location}",
                'passed' => $passed,
                'message' => $passed ? 'Redirectdoel gecontroleerd.' : 'Redirectstatus of Location-header wijkt af.',
            ];
        }

        return [
            'type' => $sample['type'],
            'reference' => $sample['reference'],
            'url' => $sample['url'],
            'expected' => $sample['expected'],
            'actual' => 'HTTP '.$response->status(),
            'passed' => $response->status() === 200,
            'message' => $response->status() === 200 ? 'Publieke sample bereikbaar.' : 'Publieke sample gaf geen HTTP 200.',
        ];
    }

    private function stagingUrl(string $baseUrl, string $path): string
    {
        $parts = parse_url($path);
        $relativePath = is_array($parts) ? ($parts['path'] ?? '/') : $path;
        $query = is_array($parts) && isset($parts['query']) ? '?'.$parts['query'] : '';

        return rtrim($baseUrl, '/').'/'.ltrim($relativePath, '/').$query;
    }

    private function pathAndQuery(string $url): string
    {
        $parts = parse_url($url);
        $path = is_array($parts) ? ($parts['path'] ?? '/') : $url;
        $query = is_array($parts) && isset($parts['query']) ? '?'.$parts['query'] : '';

        return $path.$query;
    }

    /**
     * @param  list<array{type: string, reference: string, url: string, expected: string, redirect_target: string|null}>  $samples
     * @return list<array{type: string, reference: string, url: string, expected: string, redirect_target: string|null}>
     */
    private function uniqueSamples(array $samples): array
    {
        $unique = [];

        foreach ($samples as $sample) {
            $key = $sample['type'].'|'.$sample['url'];
            $unique[$key] = $sample;
        }

        return array_values($unique);
    }
}
