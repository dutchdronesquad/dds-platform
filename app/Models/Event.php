<?php

namespace App\Models;

use App\Enums\EventRegistrationStatus;
use App\Enums\EventStatus;
use App\Enums\EventType;
use Carbon\CarbonImmutable;
use Database\Factories\EventFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $location_id
 * @property int|null $season_id
 * @property int|null $cover_image_id
 * @property string $title
 * @property string $slug
 * @property string|null $content
 * @property CarbonImmutable $starts_at
 * @property CarbonImmutable|null $ends_at
 * @property CarbonImmutable|null $published_at
 * @property EventStatus $status
 * @property EventType $type
 * @property int|null $price_cents
 * @property int|null $capacity
 * @property CarbonImmutable|null $registration_opens_at
 * @property CarbonImmutable|null $registration_deadline_at
 * @property EventRegistrationStatus $registration_status
 * @property string|null $registration_url
 */
final class Event extends Model
{
    /** @use HasFactory<EventFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'location_id',
        'season_id',
        'cover_image_id',
        'title',
        'slug',
        'content',
        'starts_at',
        'ends_at',
        'published_at',
        'status',
        'type',
        'price_cents',
        'capacity',
        'registration_opens_at',
        'registration_deadline_at',
        'registration_status',
        'registration_url',
    ];

    /** @var array<string, mixed> */
    protected $attributes = [
        'status' => EventStatus::Draft->value,
        'type' => EventType::Other->value,
        'registration_status' => EventRegistrationStatus::Closed->value,
    ];

    /** @return BelongsTo<Location, $this> */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /** @return BelongsTo<Season, $this> */
    public function season(): BelongsTo
    {
        return $this->belongsTo(Season::class);
    }

    /** @return BelongsTo<MediaAsset, $this> */
    public function coverImage(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class, 'cover_image_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_at' => 'immutable_datetime',
            'ends_at' => 'immutable_datetime',
            'published_at' => 'immutable_datetime',
            'status' => EventStatus::class,
            'type' => EventType::class,
            'price_cents' => 'integer',
            'capacity' => 'integer',
            'registration_opens_at' => 'immutable_datetime',
            'registration_deadline_at' => 'immutable_datetime',
            'registration_status' => EventRegistrationStatus::class,
        ];
    }
}
