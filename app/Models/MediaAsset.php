<?php

namespace App\Models;

use Database\Factories\MediaAssetFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $disk
 * @property string $path
 * @property string $original_filename
 * @property string $mime_type
 * @property int $size_bytes
 * @property int|null $width
 * @property int|null $height
 * @property array<string, string>|null $alt_text
 * @property bool $is_decorative
 */
final class MediaAsset extends Model
{
    /** @use HasFactory<MediaAssetFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'disk',
        'path',
        'original_filename',
        'mime_type',
        'size_bytes',
        'width',
        'height',
        'alt_text',
        'is_decorative',
    ];

    /** @var array<string, mixed> */
    protected $attributes = [
        'disk' => 'public',
        'is_decorative' => false,
    ];

    /** @return HasMany<Event, $this> */
    public function coverEvents(): HasMany
    {
        return $this->hasMany(Event::class, 'cover_image_id');
    }

    /** @return HasMany<Location, $this> */
    public function coverLocations(): HasMany
    {
        return $this->hasMany(Location::class, 'cover_image_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'size_bytes' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
            'alt_text' => 'json:unicode',
            'is_decorative' => 'boolean',
        ];
    }
}
