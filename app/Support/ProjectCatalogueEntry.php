<?php

namespace App\Support;

use App\Enums\ProjectType;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * @phpstan-type ProjectLink array{label: string, url: string}
 * @phpstan-type ProjectMedium array{path: string, dark_path?: string, alt: string}
 */
final readonly class ProjectCatalogueEntry
{
    /**
     * @param  ProjectLink|null  $primaryLink
     * @param  list<ProjectLink>  $supportingLinks
     * @param  list<string>  $credits
     * @param  list<ProjectMedium>  $media
     */
    private function __construct(
        public string $slug,
        public string $title,
        public string $summary,
        public ProjectType $type,
        public ?array $primaryLink,
        public array $supportingLinks,
        public array $credits,
        public string $audience,
        public array $media,
        public bool $featured,
        public ?string $videoUrl,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public static function fromArray(array $attributes): self
    {
        $slug = self::requiredString($attributes, 'slug');

        if (Str::slug($slug) !== $slug) {
            throw new InvalidArgumentException("Project slug [{$slug}] must be a lowercase URL slug.");
        }

        $typeValue = self::requiredString($attributes, 'type');
        $type = ProjectType::tryFrom($typeValue);

        if (! $type instanceof ProjectType) {
            throw new InvalidArgumentException("Project [{$slug}] has an unsupported type [{$typeValue}].");
        }

        return new self(
            slug: $slug,
            title: self::requiredString($attributes, 'title'),
            summary: self::requiredString($attributes, 'summary'),
            type: $type,
            primaryLink: self::optionalLink($attributes['primary_link'] ?? null, $slug, 'primary_link'),
            supportingLinks: self::links($attributes['supporting_links'] ?? [], $slug),
            credits: self::credits($attributes['credits'] ?? null, $slug),
            audience: self::requiredString($attributes, 'audience'),
            media: self::media($attributes['media'] ?? [], $slug),
            featured: self::featured($attributes['featured'] ?? false, $slug),
            videoUrl: self::videoUrl($attributes['video_url'] ?? null, $slug),
        );
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private static function requiredString(array $attributes, string $key): string
    {
        $value = $attributes[$key] ?? null;

        if (! is_string($value) || Str::of($value)->trim()->isEmpty()) {
            throw new InvalidArgumentException("Project field [{$key}] must be a non-empty string.");
        }

        return Str::of($value)->trim()->toString();
    }

    /**
     * @return ProjectLink
     */
    private static function link(mixed $value, string $slug, string $field): array
    {
        if (! is_array($value)) {
            throw new InvalidArgumentException("Project [{$slug}] field [{$field}] must be a link.");
        }

        $label = $value['label'] ?? null;
        $url = $value['url'] ?? null;

        if (! is_string($label) || Str::of($label)->trim()->isEmpty()) {
            throw new InvalidArgumentException("Project [{$slug}] field [{$field}] must have a label.");
        }

        if (! is_string($url) || ! self::isSafeExternalUrl($url)) {
            throw new InvalidArgumentException("Project [{$slug}] field [{$field}] must use a safe HTTPS URL.");
        }

        return [
            'label' => Str::of($label)->trim()->toString(),
            'url' => $url,
        ];
    }

    /**
     * @return ProjectLink|null
     */
    private static function optionalLink(mixed $value, string $slug, string $field): ?array
    {
        if ($value === null) {
            return null;
        }

        return self::link($value, $slug, $field);
    }

    /**
     * @return list<ProjectLink>
     */
    private static function links(mixed $value, string $slug): array
    {
        if (! is_array($value) || ! array_is_list($value)) {
            throw new InvalidArgumentException("Project [{$slug}] supporting_links must be a list.");
        }

        return array_map(
            static fn (mixed $link): array => self::link($link, $slug, 'supporting_links'),
            $value,
        );
    }

    /**
     * @return list<string>
     */
    private static function credits(mixed $value, string $slug): array
    {
        if (! is_array($value) || $value === [] || ! array_is_list($value)) {
            throw new InvalidArgumentException("Project [{$slug}] must credit at least one contributor or owner.");
        }

        return array_map(static function (mixed $credit) use ($slug): string {
            if (! is_string($credit) || Str::of($credit)->trim()->isEmpty()) {
                throw new InvalidArgumentException("Project [{$slug}] credits must be non-empty strings.");
            }

            return Str::of($credit)->trim()->toString();
        }, $value);
    }

    /**
     * @return list<ProjectMedium>
     */
    private static function media(mixed $value, string $slug): array
    {
        if (! is_array($value) || ! array_is_list($value)) {
            throw new InvalidArgumentException("Project [{$slug}] media must be a list.");
        }

        return array_map(
            static fn (mixed $medium): array => self::medium($medium, $slug),
            $value,
        );
    }

    /**
     * @return ProjectMedium
     */
    private static function medium(mixed $medium, string $slug): array
    {
        if (! is_array($medium)) {
            throw new InvalidArgumentException("Project [{$slug}] media entries must contain a path and alt text.");
        }

        $path = $medium['path'] ?? null;
        $darkPath = $medium['dark_path'] ?? null;
        $alt = $medium['alt'] ?? null;

        if (! is_string($path) || ! self::isValidMediaPath($path)) {
            throw new InvalidArgumentException("Project [{$slug}] media must reference a versioned project image path.");
        }

        if ($darkPath !== null && (! is_string($darkPath) || ! self::isValidMediaPath($darkPath))) {
            throw new InvalidArgumentException("Project [{$slug}] dark media must reference a versioned project image path.");
        }

        if (! is_string($alt) || Str::of($alt)->trim()->isEmpty()) {
            throw new InvalidArgumentException("Project [{$slug}] media must include useful alt text.");
        }

        $trimmedAlt = Str::of($alt)->trim()->toString();

        if ($darkPath === null) {
            return [
                'path' => $path,
                'alt' => $trimmedAlt,
            ];
        }

        return [
            'path' => $path,
            'dark_path' => $darkPath,
            'alt' => $trimmedAlt,
        ];
    }

    private static function isValidMediaPath(string $path): bool
    {
        return Str::startsWith($path, '/images/')
            && ! Str::contains($path, ['..', '?', '#'])
            && Str::endsWith(Str::lower($path), ['.avif', '.jpg', '.jpeg', '.png', '.svg', '.webp']);
    }

    private static function featured(mixed $value, string $slug): bool
    {
        if (! is_bool($value)) {
            throw new InvalidArgumentException("Project [{$slug}] field [featured] must be a boolean.");
        }

        return $value;
    }

    private static function videoUrl(mixed $value, string $slug): ?string
    {
        if ($value === null) {
            return null;
        }

        if (
            ! is_string($value)
            || ! self::isSafeExternalUrl($value)
            || ! Str::endsWith(Str::lower($value), ['.webm', '.mp4'])
        ) {
            throw new InvalidArgumentException("Project [{$slug}] field [video_url] must be a safe HTTPS link to a .webm or .mp4 video.");
        }

        return $value;
    }

    private static function isSafeExternalUrl(string $url): bool
    {
        $parts = parse_url($url);

        return filter_var($url, FILTER_VALIDATE_URL) !== false
            && is_array($parts)
            && ($parts['scheme'] ?? null) === 'https'
            && isset($parts['host'])
            && ! isset($parts['user'])
            && ! isset($parts['pass']);
    }
}
