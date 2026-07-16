<?php

namespace App\Models;

use App\Enums\SeasonTicketSalesState;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Database\Factories\SeasonTicketFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $season_id
 * @property SeasonTicketSalesState $sales_state
 * @property CarbonImmutable|null $sales_opens_at
 * @property CarbonImmutable|null $sales_closes_at
 * @property string|null $registration_url
 * @property string|null $copy
 * @property int|null $price_cents
 * @property int|null $capacity
 */
final class SeasonTicket extends Model
{
    /** @use HasFactory<SeasonTicketFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'season_id',
        'sales_state',
        'sales_opens_at',
        'sales_closes_at',
        'registration_url',
        'copy',
        'price_cents',
        'capacity',
    ];

    /** @var array<string, mixed> */
    protected $attributes = [
        'sales_state' => SeasonTicketSalesState::NotOffered->value,
    ];

    /** @return BelongsTo<Season, $this> */
    public function season(): BelongsTo
    {
        return $this->belongsTo(Season::class);
    }

    public function currentSalesState(?CarbonInterface $at = null): SeasonTicketSalesState
    {
        $at ??= now();

        if (in_array($this->sales_state, [
            SeasonTicketSalesState::NotOffered,
            SeasonTicketSalesState::Closed,
        ], true)) {
            return $this->sales_state;
        }

        if ($this->sales_closes_at?->lessThanOrEqualTo($at) === true) {
            return SeasonTicketSalesState::Closed;
        }

        if ($this->sales_opens_at?->greaterThan($at) === true) {
            return SeasonTicketSalesState::ComingSoon;
        }

        if ($this->sales_state === SeasonTicketSalesState::ComingSoon
            && $this->sales_opens_at?->lessThanOrEqualTo($at) === true) {
            return SeasonTicketSalesState::Available;
        }

        return $this->sales_state;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sales_state' => SeasonTicketSalesState::class,
            'sales_opens_at' => 'immutable_datetime',
            'sales_closes_at' => 'immutable_datetime',
            'price_cents' => 'integer',
            'capacity' => 'integer',
        ];
    }
}
