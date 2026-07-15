<?php

namespace App\Models;

use App\Enums\LocationEnvironment;
use Database\Factories\LocationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int|null $cover_image_id
 * @property string $name
 * @property string $slug
 * @property array<string, string> $description
 * @property string $street
 * @property string $house_number
 * @property string $postal_code
 * @property string $city
 * @property string $country_code
 * @property LocationEnvironment $environment
 * @property int|null $floor_size_square_metres
 * @property numeric-string|null $ceiling_height_metres
 * @property list<string>|null $facilities
 * @property string|null $website_url
 * @property numeric-string|null $latitude
 * @property numeric-string|null $longitude
 */
final class Location extends Model
{
    /** @use HasFactory<LocationFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'cover_image_id',
        'name',
        'slug',
        'description',
        'street',
        'house_number',
        'postal_code',
        'city',
        'country_code',
        'environment',
        'floor_size_square_metres',
        'ceiling_height_metres',
        'facilities',
        'website_url',
        'latitude',
        'longitude',
    ];

    /** @var array<string, mixed> */
    protected $attributes = [
        'country_code' => 'NL',
    ];

    /** @return BelongsTo<MediaAsset, $this> */
    public function coverImage(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class, 'cover_image_id');
    }

    /** @return HasMany<Event, $this> */
    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'description' => 'json:unicode',
            'environment' => LocationEnvironment::class,
            'floor_size_square_metres' => 'integer',
            'ceiling_height_metres' => 'decimal:2',
            'facilities' => 'json:unicode',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
        ];
    }
}
