<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\MediaAssetFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * @property int $id
 * @property array<string, string>|null $alt_text
 * @property CarbonImmutable|null $archived_at
 */
final class MediaAsset extends Model implements HasMedia
{
    /** @use HasFactory<MediaAssetFactory> */
    use HasFactory;

    use InteractsWithMedia;

    public const string COLLECTION = 'asset';

    /** @var list<string> */
    public const array ACCEPTED_MIME_TYPES = [
        'image/jpeg',
        'image/png',
        'image/webp',
        'image/gif',
        'application/pdf',
    ];

    /** @var list<string> */
    protected $fillable = [
        'alt_text',
        'archived_at',
    ];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::COLLECTION)
            ->acceptsMimeTypes(self::ACCEPTED_MIME_TYPES)
            ->singleFile();
    }

    /** @return HasMany<Event, $this> */
    public function coverEvents(): HasMany
    {
        return $this->hasMany(Event::class, 'cover_image_id');
    }

    /** @return HasMany<Article, $this> */
    public function coverArticles(): HasMany
    {
        return $this->hasMany(Article::class, 'cover_image_id');
    }

    /** @return HasMany<Location, $this> */
    public function coverLocations(): HasMany
    {
        return $this->hasMany(Location::class, 'cover_image_id');
    }

    /**
     * @param  Builder<MediaAsset>  $query
     * @return Builder<MediaAsset>
     */
    public function scopeAvailable(Builder $query): Builder
    {
        return $query->whereNull('archived_at');
    }

    /**
     * @param  Builder<MediaAsset>  $query
     * @return Builder<MediaAsset>
     */
    public function scopeWithMimeType(Builder $query, string $operator, string $mimeType): Builder
    {
        return $query->whereHas('media', fn (Builder $mediaQuery): Builder => $mediaQuery
            ->where('mime_type', $operator, $mimeType));
    }

    public function file(): ?Media
    {
        return $this->getFirstMedia(self::COLLECTION);
    }

    public function filename(): string
    {
        $media = $this->file();

        return $media instanceof Media ? $media->name : '';
    }

    public function mimeType(): string
    {
        $media = $this->file();

        return $media instanceof Media ? (string) $media->mime_type : '';
    }

    public function disk(): string
    {
        $media = $this->file();

        return $media instanceof Media
            ? $media->disk
            : (string) config('media-library.disk_name', 'public');
    }

    public function storagePath(): string
    {
        return $this->file()?->getPathRelativeToRoot() ?? '';
    }

    public function url(): string
    {
        $media = $this->file();

        return $media instanceof Media ? $media->getUrl() : '';
    }

    public function sizeBytes(): int
    {
        $media = $this->file();

        return $media instanceof Media ? $media->size : 0;
    }

    public function width(): ?int
    {
        $width = $this->file()?->getCustomProperty('width');

        return is_numeric($width) ? (int) $width : null;
    }

    public function height(): ?int
    {
        $height = $this->file()?->getCustomProperty('height');

        return is_numeric($height) ? (int) $height : null;
    }

    public function isImage(): bool
    {
        return str_starts_with($this->mimeType(), 'image/');
    }

    public function isInUse(): bool
    {
        return $this->coverEvents()->exists()
            || $this->coverArticles()->exists()
            || $this->coverLocations()->exists();
    }

    /** @return array<string, mixed> */
    protected function casts(): array
    {
        return [
            'alt_text' => 'json:unicode',
            'archived_at' => 'immutable_datetime',
        ];
    }
}
