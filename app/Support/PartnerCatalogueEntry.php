<?php

namespace App\Support;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use InvalidArgumentException;

final readonly class PartnerCatalogueEntry
{
    private const array PUBLIC_FIELDS = [
        'key',
        'name',
        'website_url',
        'logo_path',
        'logo_alt',
        'description',
        'sort_order',
        'show_on_homepage',
    ];

    private function __construct(
        public string $key,
        public string $name,
        public string $websiteUrl,
        public string $logoPath,
        public string $logoAlt,
        public ?string $description,
        public int $sortOrder,
        public bool $showOnHomepage,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public static function fromArray(array $attributes): self
    {
        $unsupportedFields = Arr::except($attributes, self::PUBLIC_FIELDS);

        if ($unsupportedFields !== []) {
            $field = array_key_first($unsupportedFields);

            throw new InvalidArgumentException("Partner catalogue field [{$field}] is not public and is not supported.");
        }

        $key = self::requiredString($attributes, 'key');

        if (Str::slug($key) !== $key) {
            throw new InvalidArgumentException("Partner key [{$key}] must be a lowercase URL-safe key.");
        }

        $websiteUrl = self::requiredString($attributes, 'website_url');

        if (! self::isSafeExternalUrl($websiteUrl)) {
            throw new InvalidArgumentException("Partner [{$key}] field [website_url] must use a safe HTTPS URL.");
        }

        $logoPath = self::requiredString($attributes, 'logo_path');

        if (! self::isValidLogoPath($logoPath)) {
            throw new InvalidArgumentException("Partner [{$key}] field [logo_path] must reference a versioned partner image.");
        }

        $description = $attributes['description'] ?? null;

        if (
            $description !== null
            && (! is_string($description) || Str::of($description)->trim()->isEmpty())
        ) {
            throw new InvalidArgumentException("Partner [{$key}] field [description] must be a non-empty string or null.");
        }

        $sortOrder = $attributes['sort_order'] ?? null;

        if (! is_int($sortOrder) || $sortOrder < 0) {
            throw new InvalidArgumentException("Partner [{$key}] field [sort_order] must be a non-negative integer.");
        }

        $showOnHomepage = $attributes['show_on_homepage'] ?? null;

        if (! is_bool($showOnHomepage)) {
            throw new InvalidArgumentException("Partner [{$key}] field [show_on_homepage] must be a boolean.");
        }

        return new self(
            key: $key,
            name: self::requiredString($attributes, 'name'),
            websiteUrl: $websiteUrl,
            logoPath: $logoPath,
            logoAlt: self::requiredString($attributes, 'logo_alt'),
            description: is_string($description)
                ? Str::of($description)->trim()->toString()
                : null,
            sortOrder: $sortOrder,
            showOnHomepage: $showOnHomepage,
        );
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private static function requiredString(array $attributes, string $key): string
    {
        $value = $attributes[$key] ?? null;

        if (! is_string($value) || Str::of($value)->trim()->isEmpty()) {
            throw new InvalidArgumentException("Partner field [{$key}] must be a non-empty string.");
        }

        return Str::of($value)->trim()->toString();
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

    private static function isValidLogoPath(string $path): bool
    {
        return Str::startsWith($path, '/images/dds/partners/')
            && ! Str::contains($path, ['..', '?', '#'])
            && Str::endsWith(Str::lower($path), ['.avif', '.jpg', '.jpeg', '.png', '.svg', '.webp']);
    }
}
