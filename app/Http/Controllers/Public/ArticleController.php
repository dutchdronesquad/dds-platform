<?php

namespace App\Http\Controllers\Public;

use App\Enums\ArticleCategory;
use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Support\MarkdownRenderer;
use App\Support\PublicArticleData;
use App\Support\SeoMetadata;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class ArticleController extends Controller
{
    public function __construct(
        private PublicArticleData $articleData,
        private MarkdownRenderer $markdown,
    ) {}

    public function index(Request $request, SeoMetadata $seoMetadata): Response
    {
        $activeCategory = ArticleCategory::tryFrom($request->string('category')->toString());

        $articles = Article::query()
            ->select([
                'id',
                'author_id',
                'cover_image_id',
                'title',
                'slug',
                'content',
                'published_at',
                'status',
                'category',
            ])
            ->publiclyVisible()
            ->when($activeCategory !== null, fn (Builder $query): Builder => $query
                ->where('category', $activeCategory))
            ->with([
                'author:id,name',
                'coverImage:id,alt_text',
                'coverImage.media',
            ])
            ->orderByDesc('published_at')
            ->paginate(12)
            ->withQueryString()
            ->through(fn (Article $article): array => $this->articleData->summary($article));

        return Inertia::render('public/news-index', [
            'activeCategory' => $activeCategory?->value,
            'articles' => $articles,
            'categoryFilters' => $this->categoryFilters(),
            'seo' => $seoMetadata->forPage('news'),
        ]);
    }

    /** @return list<array{value: string, label: string}> */
    private function categoryFilters(): array
    {
        return [
            ['value' => ArticleCategory::News->value, 'label' => 'Nieuws'],
            ['value' => ArticleCategory::Announcement->value, 'label' => 'Aankondigingen'],
            ['value' => ArticleCategory::Community->value, 'label' => 'Community'],
            ['value' => ArticleCategory::RaceReport->value, 'label' => 'Raceverslagen'],
        ];
    }

    public function show(Article $article, SeoMetadata $seoMetadata): Response
    {
        abort_unless($article->isPubliclyVisible(), 404);

        $article->load([
            'author:id,name',
            'coverImage:id,alt_text',
            'coverImage.media',
        ]);

        $image = $this->articleData->image($article);
        $description = str($this->markdown->toPlainText($article->content))
            ->limit(155)
            ->toString();

        return Inertia::render('public/article-show', [
            'article' => [
                ...$this->articleData->summary($article),
                'contentHtml' => $this->markdown->toHtml($article->content),
            ],
            'seo' => $seoMetadata->forPage('article', [
                'title' => $article->title,
                'description' => $description,
                'canonical_path' => route('news.show', ['article' => $article->slug], false),
                'image_path' => $image['src'],
                'image_alt' => $image['alt'],
            ]),
        ]);
    }
}
