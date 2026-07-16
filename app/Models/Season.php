<?php

namespace App\Models;

use Database\Factories\SeasonFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property string $name
 */
final class Season extends Model
{
    /** @use HasFactory<SeasonFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'name',
    ];

    /** @return HasMany<Event, $this> */
    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    /** @return HasOne<SeasonTicket, $this> */
    public function seasonTicket(): HasOne
    {
        return $this->hasOne(SeasonTicket::class);
    }
}
