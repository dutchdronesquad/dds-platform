<?php

namespace App\Models;

use Database\Factories\TrackDrawConnectionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $name
 * @property string $api_key
 */
final class TrackDrawConnection extends Model
{
    /** @use HasFactory<TrackDrawConnectionFactory> */
    use HasFactory;

    protected $fillable = ['name', 'api_key'];

    protected $hidden = ['api_key'];

    /** @return HasMany<Event, $this> */
    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['api_key' => 'encrypted'];
    }
}
