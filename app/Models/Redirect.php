<?php

namespace App\Models;

use Database\Factories\RedirectFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Support\Uri;

/**
 * @property int $id
 * @property string $source_path
 * @property string $target_url
 * @property int $status_code
 * @property bool $is_active
 * @property int $hit_count
 * @property string|null $notes
 */
final class Redirect extends Model
{
    /** @use HasFactory<RedirectFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'source_path',
        'target_url',
        'status_code',
        'is_active',
        'hit_count',
        'notes',
    ];

    /** @var array<string, mixed> */
    protected $attributes = [
        'status_code' => 301,
        'is_active' => true,
        'hit_count' => 0,
    ];

    /**
     * @param  Builder<Redirect>  $query
     * @return Builder<Redirect>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public static function normalizePath(string $path): string
    {
        $path = Uri::of($path)->path();

        if ($path === '/') {
            return $path;
        }

        return '/'.Str::of($path)->trim('/')->toString();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status_code' => 'integer',
            'is_active' => 'boolean',
            'hit_count' => 'integer',
        ];
    }

    /** @return Attribute<string, string> */
    protected function sourcePath(): Attribute
    {
        return Attribute::make(
            set: fn (string $value): string => self::normalizePath($value),
        );
    }
}
