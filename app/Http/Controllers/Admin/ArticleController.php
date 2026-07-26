<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ArticleCategory;
use App\Enums\ArticleStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreArticleRequest;
use App\Http\Requests\Admin\UpdateArticleRequest;
use App\Models\Article;
use App\Models\User;
use App\Support\MediaAssetPickerData;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

final class ArticleController extends Controller
{
    public function __construct(private MediaAssetPickerData $mediaAssetPickerData) {}

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Article::class);

        $filters = $this->filters($request);

        return Inertia::render('admin/articles/index', [
            'articles' => fn () => $this->articles($request->user(), $filters),
            'filters' => $filters,
            'canCreate' => $request->user()->can('create', Article::class),
            'categoryOptions' => $this->categoryOptions(),
            'statusOptions' => $this->statusOptions(),
        ]);
    }

    public function create(Request $request): Response
    {
        Gate::authorize('create', Article::class);

        return Inertia::render('admin/articles/create', [
            'options' => $this->formOptions(),
            'defaultAuthorId' => $request->user()->id,
        ]);
    }

    public function store(StoreArticleRequest $request): RedirectResponse
    {
        $article = Article::query()->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Artikel aangemaakt.']);

        return to_route('admin.articles.edit', $article);
    }

    public function edit(Request $request, Article $article): Response
    {
        Gate::authorize('update', $article);

        $article->load(['author:id,name', 'coverImage.media']);

        return Inertia::render('admin/articles/edit', [
            'article' => $this->formArticle($request->user(), $article),
            'options' => $this->formOptions(),
        ]);
    }

    public function update(UpdateArticleRequest $request, Article $article): RedirectResponse
    {
        $article->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Artikel opgeslagen.']);

        return to_route('admin.articles.edit', $article);
    }

    public function destroy(Article $article): RedirectResponse
    {
        Gate::authorize('delete', $article);

        $article->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Artikel verwijderd.']);

        return to_route('admin.articles.index');
    }

    /** @return array{search: string} */
    private function filters(Request $request): array
    {
        return [
            'search' => Str::substr($request->string('search')->trim()->toString(), 0, 100),
        ];
    }

    /**
     * @param  array{search: string}  $filters
     * @return LengthAwarePaginator<int, covariant array{
     *     id: int,
     *     title: string,
     *     slug: string,
     *     category: string,
     *     status: string,
     *     publishedAt: string|null,
     *     author: array{id: int, name: string}|null,
     *     activity: array{updatedAt: string},
     *     capabilities: array{update: bool, delete: bool}
     * }>
     */
    private function articles(User $user, array $filters): LengthAwarePaginator
    {
        $query = Article::query()
            ->select([
                'id',
                'author_id',
                'title',
                'slug',
                'published_at',
                'status',
                'category',
                'updated_at',
            ])
            ->with('author:id,name');

        $this->applySearch($query, $filters['search']);

        return $query
            ->latest('id')
            ->paginate(25)
            ->appends(array_filter([
                'search' => $filters['search'] !== '' ? $filters['search'] : null,
            ], fn (mixed $value): bool => $value !== null))
            ->through(fn (Article $article): array => [
                'id' => $article->id,
                'title' => $article->title,
                'slug' => $article->slug,
                'category' => $article->category->value,
                'status' => $article->status->value,
                'publishedAt' => $article->published_at?->toIso8601String(),
                'author' => $article->author === null ? null : [
                    'id' => $article->author->id,
                    'name' => $article->author->name,
                ],
                'activity' => [
                    'updatedAt' => $article->updated_at->toIso8601String(),
                ],
                'capabilities' => [
                    'update' => $user->can('update', $article),
                    'delete' => $user->can('delete', $article),
                ],
            ]);
    }

    /** @param Builder<Article> $query */
    private function applySearch(Builder $query, string $search): void
    {
        if ($search === '') {
            return;
        }

        $searchPattern = '%'.Str::lower($search).'%';

        $query->where(function (Builder $query) use ($searchPattern): void {
            $query
                ->whereRaw('LOWER(title) LIKE ?', [$searchPattern])
                ->orWhereRaw('LOWER(slug) LIKE ?', [$searchPattern]);
        });
    }

    /** @return array<string, mixed> */
    private function formOptions(): array
    {
        return [
            'authors' => User::query()
                ->select(['id', 'name'])
                ->orderBy('name')
                ->get()
                ->map(fn (User $user): array => [
                    'id' => $user->id,
                    'label' => $user->name,
                ]),
            'categories' => $this->categoryOptions(),
            'statuses' => $this->statusOptions(),
        ];
    }

    /** @return array<string, mixed> */
    private function formArticle(User $user, Article $article): array
    {
        return [
            'id' => $article->id,
            'title' => $article->title,
            'slug' => $article->slug,
            'content' => $article->content,
            'authorId' => $article->author_id,
            'coverImageId' => $article->cover_image_id,
            'coverImage' => $article->coverImage === null
                ? null
                : $this->mediaAssetPickerData->one($article->coverImage),
            'category' => $article->category->value,
            'status' => $article->status->value,
            'publishedAt' => $article->published_at?->format('Y-m-d\TH:i'),
            'activity' => [
                'createdAt' => $article->created_at->toIso8601String(),
                'createdBy' => null,
                'updatedAt' => $article->updated_at->toIso8601String(),
                'updatedBy' => null,
            ],
            'capabilities' => [
                'delete' => $user->can('delete', $article),
            ],
        ];
    }

    /** @return list<array{value: string, label: string}> */
    private function categoryOptions(): array
    {
        return [
            ['value' => ArticleCategory::News->value, 'label' => 'Nieuws'],
            ['value' => ArticleCategory::Announcement->value, 'label' => 'Aankondiging'],
            ['value' => ArticleCategory::Community->value, 'label' => 'Community'],
            ['value' => ArticleCategory::RaceReport->value, 'label' => 'Raceverslag'],
        ];
    }

    /** @return list<array{value: string, label: string}> */
    private function statusOptions(): array
    {
        return [
            ['value' => ArticleStatus::Draft->value, 'label' => 'Concept'],
            ['value' => ArticleStatus::Published->value, 'label' => 'Gepubliceerd'],
            ['value' => ArticleStatus::Archived->value, 'label' => 'Gearchiveerd'],
        ];
    }
}
