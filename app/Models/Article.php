<?php

namespace App\Models;

use App\Enums\ArticleCategory;
use App\Enums\ArticleStatus;
use Carbon\CarbonImmutable;
use Database\Factories\ArticleFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int|null $author_id
 * @property int|null $cover_image_id
 * @property string $title
 * @property string $slug
 * @property string $content
 * @property CarbonImmutable|null $published_at
 * @property ArticleStatus $status
 * @property ArticleCategory $category
 */
final class Article extends Model
{
    /** @use HasFactory<ArticleFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'author_id',
        'cover_image_id',
        'title',
        'slug',
        'content',
        'published_at',
        'status',
        'category',
    ];

    /** @var array<string, mixed> */
    protected $attributes = [
        'status' => ArticleStatus::Draft->value,
        'category' => ArticleCategory::News->value,
    ];

    /** @return BelongsTo<User, $this> */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /** @return BelongsTo<MediaAsset, $this> */
    public function coverImage(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class, 'cover_image_id');
    }

    /**
     * @param  Builder<Article>  $query
     * @return Builder<Article>
     */
    public function scopePubliclyVisible(Builder $query): Builder
    {
        return $query
            ->where('status', ArticleStatus::Published)
            ->where('published_at', '<=', now());
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'published_at' => 'immutable_datetime',
            'status' => ArticleStatus::class,
            'category' => ArticleCategory::class,
        ];
    }
}
