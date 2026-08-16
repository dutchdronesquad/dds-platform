<?php

namespace App\Actions;

use App\Enums\ArticleCategory;
use App\Enums\ArticleStatus;
use App\Models\Article;
use App\Models\MediaAsset;
use App\Models\User;
use App\Support\WordPressSourceRepository;
use Carbon\CarbonImmutable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use InvalidArgumentException;
use JsonException;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Throwable;

final class ImportWordPressPosts
{
    public function __construct(private readonly WordPressSourceRepository $sourceRepository) {}

    /**
     * @return array{
     *     items: list<array{wordpress_id: int, status: string, message: string}>,
     *     selected: int,
     *     ready: int,
     *     imported: int,
     *     reused: int,
     *     skipped: int,
     *     failed: int
     * }
     *
     * @throws JsonException
     */
    public function handle(string $manifestPath, bool $dryRun): array
    {
        $manifest = $this->readManifest($manifestPath);
        $postsEndpoint = $this->postsEndpoint($manifest);
        $selections = $this->selections($manifest);
        $report = $this->emptyReport();

        foreach ($selections as $selection) {
            $wordpressId = $selection['wordpress_id'];

            if ($selection['decision'] === 'skip') {
                $report['skipped']++;
                $report['items'][] = [
                    'wordpress_id' => $wordpressId,
                    'status' => 'overgeslagen',
                    'message' => $selection['reason'] ?? 'Expliciet overgeslagen in het manifest.',
                ];

                continue;
            }

            $report['selected']++;
            $existingMapping = Arr::get($manifest, "mappings.posts.{$wordpressId}");

            if ($this->mappedArticle($existingMapping) instanceof Article) {
                $report['reused']++;
                $report['items'][] = [
                    'wordpress_id' => $wordpressId,
                    'status' => 'hergebruikt',
                    'message' => 'Bestaand Article uit de manifestmapping gebruikt; redactionele wijzigingen zijn behouden.',
                ];

                continue;
            }

            $sourceRecord = $this->sourceRepository->record(
                $manifest,
                'posts',
                $postsEndpoint,
                $wordpressId,
            );

            if (is_string($sourceRecord)) {
                $this->addFailure($report, $wordpressId, $sourceRecord);

                continue;
            }

            $source = $this->sourceData($sourceRecord, $wordpressId);

            if (is_string($source)) {
                $this->addFailure($report, $wordpressId, $source);

                continue;
            }

            $coverImage = $this->resolveCoverImage($manifest, $source['featured_media_id']);

            if (is_string($coverImage)) {
                $this->addFailure($report, $wordpressId, $coverImage);

                continue;
            }

            $author = $this->resolveAuthor($manifest, $source['wordpress_author_id']);

            if (is_string($author)) {
                $this->addFailure($report, $wordpressId, $author);

                continue;
            }

            $conflictingArticle = Article::query()
                ->where('slug', $selection['slug'])
                ->first();

            if ($conflictingArticle instanceof Article) {
                $this->addFailure(
                    $report,
                    $wordpressId,
                    "Slugconflict: {$selection['slug']} hoort al bij Article {$conflictingArticle->getKey()}.",
                );

                continue;
            }

            if ($dryRun) {
                $report['ready']++;
                $report['items'][] = [
                    'wordpress_id' => $wordpressId,
                    'status' => 'klaar',
                    'message' => "{$selection['title']} kan als {$selection['category']->value} worden geïmporteerd.",
                ];

                continue;
            }

            $article = DB::transaction(fn (): Article => Article::query()->create([
                'author_id' => $author->getKey(),
                'cover_image_id' => $coverImage?->getKey(),
                'title' => $selection['title'],
                'slug' => $selection['slug'],
                'content' => $source['content'],
                'published_at' => $selection['published_at'],
                'status' => ArticleStatus::Published,
                'category' => $selection['category'],
            ]));

            Arr::set($manifest, "mappings.posts.{$wordpressId}", [
                'article_id' => $article->getKey(),
                'source_url' => $source['source_url'],
                'source_slug' => $source['source_slug'],
                'wordpress_author_id' => $source['wordpress_author_id'],
                'author_id' => $author->getKey(),
                'featured_media_id' => $source['featured_media_id'],
                'category_ids' => $source['category_ids'],
                'tag_ids' => $source['tag_ids'],
                'content_checksum_sha256' => hash('sha256', $source['content']),
                'imported_at' => now()->toIso8601String(),
            ]);
            $this->writeManifest($manifestPath, $manifest);

            $report['imported']++;
            $report['items'][] = [
                'wordpress_id' => $wordpressId,
                'status' => 'geïmporteerd',
                'message' => "Article {$article->getKey()} aangemaakt voor {$selection['slug']}.",
            ];
        }

        return $report;
    }

    /**
     * @return array<string, mixed>
     *
     * @throws JsonException
     */
    private function readManifest(string $manifestPath): array
    {
        if (! File::isFile($manifestPath)) {
            throw new InvalidArgumentException("Manifest niet gevonden: {$manifestPath}");
        }

        $manifest = json_decode(
            File::get($manifestPath),
            associative: true,
            flags: JSON_THROW_ON_ERROR,
        );

        if (! is_array($manifest)) {
            throw new InvalidArgumentException('Het WordPress-manifest moet een JSON-object bevatten.');
        }

        return $manifest;
    }

    /** @param array<string, mixed> $manifest */
    private function postsEndpoint(array $manifest): string
    {
        $postsEndpoint = Arr::get($manifest, 'source.posts_endpoint');

        if (! $this->isHttpUrl($postsEndpoint)) {
            throw new InvalidArgumentException(
                'Het manifest mist een geldige HTTP(S)-URL in source.posts_endpoint.',
            );
        }

        return rtrim($postsEndpoint, '/');
    }

    /**
     * @param  array<string, mixed>  $manifest
     * @return list<array{
     *     wordpress_id: int,
     *     slug: string,
     *     title: string,
     *     published_at: CarbonImmutable,
     *     category: ArticleCategory,
     *     decision: 'import'|'skip',
     *     reason: string|null
     * }>
     */
    private function selections(array $manifest): array
    {
        $posts = Arr::get($manifest, 'posts');

        if (! is_array($posts) || ! array_is_list($posts)) {
            throw new InvalidArgumentException('Het manifest moet een posts-lijst bevatten.');
        }

        $selections = [];
        $wordpressIds = [];

        foreach ($posts as $index => $selection) {
            if (! is_array($selection)) {
                throw new InvalidArgumentException("Postselectie {$index} moet een JSON-object zijn.");
            }

            $wordpressId = Arr::get($selection, 'wordpress_id');
            $slug = Arr::get($selection, 'slug');
            $title = Arr::get($selection, 'title');
            $publishedAt = Arr::get($selection, 'published_at');
            $category = Arr::get($selection, 'category');
            $decision = Arr::get($selection, 'decision', 'import');
            $reason = Arr::get($selection, 'reason');

            if (! is_int($wordpressId) || $wordpressId < 1) {
                throw new InvalidArgumentException(
                    "Postselectie {$index} mist een geldige wordpress_id.",
                );
            }

            if (isset($wordpressIds[$wordpressId])) {
                throw new InvalidArgumentException("WordPress post-ID {$wordpressId} staat dubbel in het manifest.");
            }

            if (! is_string($slug)
                || $slug === ''
                || Str::length($slug) > 255
                || preg_match('/^[A-Za-z0-9_-]+$/', $slug) !== 1) {
                throw new InvalidArgumentException("Postselectie {$wordpressId} heeft een ongeldige slug.");
            }

            if (! is_string($title) || trim($title) === '' || Str::length($title) > 255) {
                throw new InvalidArgumentException("Postselectie {$wordpressId} heeft een ongeldige title.");
            }

            if (! is_string($publishedAt)) {
                throw new InvalidArgumentException(
                    "Postselectie {$wordpressId} mist een geldige published_at.",
                );
            }

            try {
                $publicationDate = CarbonImmutable::parse($publishedAt, 'UTC');
            } catch (Throwable) {
                throw new InvalidArgumentException(
                    "Postselectie {$wordpressId} heeft een ongeldige published_at.",
                );
            }

            $articleCategory = is_string($category) ? ArticleCategory::tryFrom($category) : null;

            if (! $articleCategory instanceof ArticleCategory) {
                throw new InvalidArgumentException(
                    "Postselectie {$wordpressId} heeft een ongeldige category.",
                );
            }

            if (! in_array($decision, ['import', 'skip'], true)) {
                throw new InvalidArgumentException(
                    "Postselectie {$wordpressId} heeft een ongeldige decision.",
                );
            }

            if ($reason !== null && ! is_string($reason)) {
                throw new InvalidArgumentException(
                    "Postselectie {$wordpressId} heeft een ongeldige reason.",
                );
            }

            $wordpressIds[$wordpressId] = true;
            $selections[] = [
                'wordpress_id' => $wordpressId,
                'slug' => $slug,
                'title' => $this->plainText($title),
                'published_at' => $publicationDate,
                'category' => $articleCategory,
                'decision' => $decision,
                'reason' => $reason,
            ];
        }

        return $selections;
    }

    private function mappedArticle(mixed $mapping): ?Article
    {
        if (! is_array($mapping)) {
            return null;
        }

        $articleId = Arr::get($mapping, 'article_id');

        return is_int($articleId) ? Article::query()->find($articleId) : null;
    }

    /**
     * @param  array<string, mixed>  $sourceRecord
     * @return array{
     *     source_url: string,
     *     source_slug: string,
     *     content: string,
     *     wordpress_author_id: int,
     *     featured_media_id: int,
     *     category_ids: list<int>,
     *     tag_ids: list<int>
     * }|string
     */
    private function sourceData(array $sourceRecord, int $wordpressId): array|string
    {
        if (Arr::get($sourceRecord, 'id') !== $wordpressId) {
            return 'WordPress REST gaf een record met een onverwacht ID terug.';
        }

        if (Arr::get($sourceRecord, 'status') !== 'publish') {
            return 'WordPress REST-record is niet gepubliceerd.';
        }

        $sourceUrl = Arr::get($sourceRecord, 'link');
        $sourceSlug = Arr::get($sourceRecord, 'slug');
        $content = Arr::get($sourceRecord, 'content.rendered');
        $wordpressAuthorId = Arr::get($sourceRecord, 'author');
        $featuredMediaId = Arr::get($sourceRecord, 'featured_media', 0);
        $categoryIds = Arr::get($sourceRecord, 'categories', []);
        $tagIds = Arr::get($sourceRecord, 'tags', []);

        if (! $this->isHttpUrl($sourceUrl)) {
            return 'WordPress REST gaf geen geldige bron-URL terug.';
        }

        if (! is_string($sourceSlug) || $sourceSlug === '') {
            return 'WordPress REST gaf geen geldige bronslug terug.';
        }

        if (! is_string($content) || trim($content) === '') {
            return 'WordPress REST gaf geen artikelinhoud terug.';
        }

        if (Str::length($content) > 50_000) {
            return 'WordPress artikelinhoud is langer dan de ondersteunde 50000 tekens.';
        }

        if (! is_int($wordpressAuthorId) || $wordpressAuthorId < 1) {
            return 'WordPress REST gaf geen geldige auteur-ID terug.';
        }

        if (! is_int($featuredMediaId) || $featuredMediaId < 0) {
            return 'WordPress REST gaf geen geldige featured-media-ID terug.';
        }

        if (! $this->isIntegerList($categoryIds) || ! $this->isIntegerList($tagIds)) {
            return 'WordPress REST gaf ongeldige categorie- of tag-ID’s terug.';
        }

        return [
            'source_url' => $sourceUrl,
            'source_slug' => $sourceSlug,
            'content' => $content,
            'wordpress_author_id' => $wordpressAuthorId,
            'featured_media_id' => $featuredMediaId,
            'category_ids' => $categoryIds,
            'tag_ids' => $tagIds,
        ];
    }

    /** @param array<string, mixed> $manifest */
    private function resolveCoverImage(array $manifest, int $featuredMediaId): MediaAsset|string|null
    {
        if ($featuredMediaId === 0) {
            return null;
        }

        $mediaAssetId = Arr::get(
            $manifest,
            "mappings.media.{$featuredMediaId}.media_asset_id",
        );

        if (! is_int($mediaAssetId)) {
            return "Featured media {$featuredMediaId} mist een MediaAsset-mapping; voer eerst de media-import uit.";
        }

        $mediaAsset = MediaAsset::query()->with('media')->find($mediaAssetId);

        if (! $mediaAsset instanceof MediaAsset
            || ! $mediaAsset->file() instanceof Media
            || ! $mediaAsset->isImage()) {
            return "Featured media {$featuredMediaId} verwijst niet naar een beschikbare afbeelding.";
        }

        return $mediaAsset;
    }

    /** @param array<string, mixed> $manifest */
    private function resolveAuthor(array $manifest, int $wordpressAuthorId): User|string
    {
        $authorId = Arr::get(
            $manifest,
            "mappings.authors.{$wordpressAuthorId}.user_id",
            Arr::get($manifest, 'defaults.author_id'),
        );

        if (! is_int($authorId)) {
            return "WordPress auteur {$wordpressAuthorId} mist een auteursmapping en defaults.author_id.";
        }

        $author = User::query()->find($authorId);

        return $author instanceof User
            ? $author
            : "De gekoppelde Laravel-auteur {$authorId} bestaat niet.";
    }

    private function plainText(string $value): string
    {
        return Str::of(html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8'))
            ->stripTags()
            ->squish()
            ->toString();
    }

    private function isHttpUrl(mixed $value): bool
    {
        return is_string($value)
            && filter_var($value, FILTER_VALIDATE_URL) !== false
            && in_array(parse_url($value, PHP_URL_SCHEME), ['http', 'https'], true);
    }

    private function isIntegerList(mixed $value): bool
    {
        return is_array($value)
            && array_is_list($value)
            && collect($value)->every(fn (mixed $item): bool => is_int($item) && $item > 0);
    }

    /**
     * @param  array<string, mixed>  $manifest
     *
     * @throws JsonException
     */
    private function writeManifest(string $manifestPath, array $manifest): void
    {
        File::replace(
            $manifestPath,
            json_encode(
                $manifest,
                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
            ).PHP_EOL,
        );
    }

    /**
     * @return array{
     *     items: list<array{wordpress_id: int, status: string, message: string}>,
     *     selected: int,
     *     ready: int,
     *     imported: int,
     *     reused: int,
     *     skipped: int,
     *     failed: int
     * }
     */
    private function emptyReport(): array
    {
        return [
            'items' => [],
            'selected' => 0,
            'ready' => 0,
            'imported' => 0,
            'reused' => 0,
            'skipped' => 0,
            'failed' => 0,
        ];
    }

    /**
     * @param array{
     *     items: list<array{wordpress_id: int, status: string, message: string}>,
     *     selected: int,
     *     ready: int,
     *     imported: int,
     *     reused: int,
     *     skipped: int,
     *     failed: int
     * } $report
     */
    private function addFailure(array &$report, int $wordpressId, string $message): void
    {
        $report['failed']++;
        $report['items'][] = [
            'wordpress_id' => $wordpressId,
            'status' => 'mislukt',
            'message' => $message,
        ];
    }
}
