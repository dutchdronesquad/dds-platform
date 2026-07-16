<?php

namespace App\Models;

use Database\Factories\SeasonFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 */
final class Season extends Model
{
    /** @use HasFactory<SeasonFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'name',
        'slug',
    ];

    protected static function booted(): void
    {
        self::creating(function (Season $season): void {
            $slug = $season->getAttribute('slug');

            if (! is_string($slug) || $slug === '') {
                $season->slug = self::uniqueSlug($season->name);
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

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

    private static function uniqueSlug(string $name): string
    {
        $baseSlug = Str::slug($name) ?: 'seizoen';
        $slug = $baseSlug;
        $suffix = 2;

        while (self::query()->where('slug', $slug)->exists()) {
            $slug = "{$baseSlug}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}
