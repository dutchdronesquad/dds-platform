<?php

namespace App\Models;

use App\Concerns\TracksContentActivity;
use App\Enums\EventRegistrationStatus;
use App\Enums\EventStatus;
use App\Enums\EventType;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Database\Factories\EventFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $location_id
 * @property int|null $season_id
 * @property int|null $cover_image_id
 * @property int|null $created_by
 * @property int|null $updated_by
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
 * @property bool $registration_enabled
 * @property bool $registration_closed_manually
 * @property bool $registration_full
 * @property bool $registration_waitlist_enabled
 * @property string|null $registration_url
 */
final class Event extends Model
{
    /** @use HasFactory<EventFactory> */
    use HasFactory;

    use TracksContentActivity;

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
        'registration_enabled',
        'registration_closed_manually',
        'registration_full',
        'registration_waitlist_enabled',
        'registration_opens_at',
        'registration_deadline_at',
        'registration_url',
    ];

    /** @var array<string, mixed> */
    protected $attributes = [
        'status' => EventStatus::Draft->value,
        'type' => EventType::Other->value,
        'registration_enabled' => false,
        'registration_closed_manually' => false,
        'registration_full' => false,
        'registration_waitlist_enabled' => false,
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
     * @param  Builder<Event>  $query
     * @return Builder<Event>
     */
    public function scopePubliclyVisible(Builder $query): Builder
    {
        return $query
            ->whereIn('status', [EventStatus::Published, EventStatus::Cancelled])
            ->where('published_at', '<=', now());
    }

    /**
     * @param  Builder<Event>  $query
     * @return Builder<Event>
     */
    public function scopeUpcoming(Builder $query): Builder
    {
        return $query
            ->where('starts_at', '>=', now())
            ->oldest('starts_at')
            ->oldest('id');
    }

    public function isPubliclyVisible(): bool
    {
        return in_array($this->status, [EventStatus::Published, EventStatus::Cancelled], true)
            && $this->published_at?->lte(now()) === true;
    }

    public function currentRegistrationStatus(?CarbonInterface $at = null): EventRegistrationStatus
    {
        $at ??= now();

        if (! $this->registration_enabled || $this->registration_closed_manually) {
            return EventRegistrationStatus::Closed;
        }

        if ($this->registration_opens_at?->greaterThan($at) === true) {
            return EventRegistrationStatus::Closed;
        }

        if ($this->registration_deadline_at?->lessThanOrEqualTo($at) === true) {
            return EventRegistrationStatus::Closed;
        }

        if ($this->registration_full) {
            return $this->registration_waitlist_enabled
                ? EventRegistrationStatus::Waitlist
                : EventRegistrationStatus::Full;
        }

        return EventRegistrationStatus::Open;
    }

    public function registrationIsUpcoming(?CarbonInterface $at = null): bool
    {
        $at ??= now();

        return $this->registration_enabled
            && ! $this->registration_closed_manually
            && $this->registration_opens_at?->greaterThan($at) === true;
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
            'registration_enabled' => 'boolean',
            'registration_closed_manually' => 'boolean',
            'registration_full' => 'boolean',
            'registration_waitlist_enabled' => 'boolean',
            'registration_opens_at' => 'immutable_datetime',
            'registration_deadline_at' => 'immutable_datetime',
        ];
    }
}
