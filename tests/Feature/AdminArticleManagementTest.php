<?php

use App\Enums\ArticleCategory;
use App\Enums\ArticleStatus;
use App\Enums\Role;
use App\Models\Article;
use App\Models\MediaAsset;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->withoutVite();
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('article management requires a management role and article permission', function () {
    $this->get(route('admin.articles.index'))->assertRedirect(route('login'));

    $user = User::factory()->create();
    $article = Article::factory()->create();

    $this->actingAs($user)
        ->get(route('admin.articles.index'))
        ->assertForbidden();

    expect($user->can('view', $article))->toBeFalse();
});

test('admins can review articles with author and capabilities', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin->value);
    $author = User::factory()->create(['name' => 'Jane Pilot']);
    $article = Article::factory()->create([
        'author_id' => $author->id,
        'title' => 'Nieuw seizoen van start',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.articles.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/articles/index')
            ->where('canCreate', true)
            ->has('articles.data', 1)
            ->where('articles.data.0.id', $article->id)
            ->where('articles.data.0.title', 'Nieuw seizoen van start')
            ->where('articles.data.0.author.name', 'Jane Pilot')
            ->where('articles.data.0.capabilities.update', true)
            ->where('articles.data.0.capabilities.delete', true),
        );
});

test('article search filters by title or slug', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin->value);
    Article::factory()->create(['title' => 'Nieuw seizoen van start', 'slug' => 'nieuw-seizoen-van-start']);
    Article::factory()->create(['title' => 'Terugblik op de finale', 'slug' => 'terugblik-op-de-finale']);

    $this->actingAs($admin)
        ->get(route('admin.articles.index', ['search' => 'SEIZOEN']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('filters.search', 'SEIZOEN')
            ->has('articles.data', 1)
            ->where('articles.data.0.title', 'Nieuw seizoen van start'),
        );
});

test('admins can open article forms with complete options and editable values', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin->value);
    $coverImage = MediaAsset::factory()->create();
    $article = Article::factory()->withCoverImage()->create([
        'cover_image_id' => $coverImage->id,
        'title' => 'Nieuw seizoen van start',
        'slug' => 'nieuw-seizoen-van-start',
        'content' => 'Dit seizoen gaan we weer racen op de baan in Alkmaar.',
        'category' => ArticleCategory::Announcement,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.articles.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/articles/create')
            ->where('defaultAuthorId', $admin->id)
            ->has('options.categories', 4)
            ->has('options.statuses', 3),
        );

    $this->get(route('admin.articles.edit', $article))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/articles/edit')
            ->where('article.id', $article->id)
            ->where('article.title', 'Nieuw seizoen van start')
            ->where('article.slug', 'nieuw-seizoen-van-start')
            ->where('article.content', 'Dit seizoen gaan we weer racen op de baan in Alkmaar.')
            ->where('article.category', ArticleCategory::Announcement->value)
            ->where('article.status', ArticleStatus::Draft->value)
            ->where('article.capabilities.delete', true)
            ->has('article.coverImage'),
        );
});

test('editors can create and update articles but cannot delete them', function () {
    $editor = User::factory()->create();
    $editor->assignRole(Role::Editor->value);

    $this->actingAs($editor)
        ->post(route('admin.articles.store'), validArticlePayload([
            'title' => 'Editor artikel',
            'slug' => '',
        ]))
        ->assertRedirect();

    $article = Article::query()->where('slug', 'editor-artikel')->firstOrFail();

    $this->actingAs($editor)
        ->put(route('admin.articles.update', $article), validArticlePayload([
            'title' => 'Bijgewerkt editor artikel',
            'slug' => 'bijgewerkt-editor-artikel',
        ]))
        ->assertRedirect(route('admin.articles.edit', $article));

    expect($article->refresh()->title)->toBe('Bijgewerkt editor artikel');

    $this->actingAs($editor)
        ->delete(route('admin.articles.destroy', $article))
        ->assertForbidden();

    $this->assertModelExists($article);
});

test('admins can create articles with a generated slug and automatic publication date', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin->value);

    $this->actingAs($admin)
        ->post(route('admin.articles.store'), validArticlePayload([
            'title' => 'Nieuw seizoen van start',
            'slug' => '',
            'status' => ArticleStatus::Published->value,
            'published_at' => '',
        ]))
        ->assertRedirect();

    $article = Article::query()->where('slug', 'nieuw-seizoen-van-start')->firstOrFail();

    expect($article)
        ->title->toBe('Nieuw seizoen van start')
        ->status->toBe(ArticleStatus::Published);
    expect($article->published_at)->not->toBeNull();
});

test('article requests reject missing titles and invalid categories', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin->value);

    $this->actingAs($admin)
        ->post(route('admin.articles.store'), validArticlePayload([
            'title' => '',
            'category' => 'invalid-category',
        ]))
        ->assertSessionHasErrors(['title', 'category']);
});

test('admins can delete articles', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin->value);
    $article = Article::factory()->create();

    $this->actingAs($admin)
        ->delete(route('admin.articles.destroy', $article))
        ->assertRedirect(route('admin.articles.index'))
        ->assertInertiaFlash('toast', [
            'type' => 'success',
            'message' => 'Artikel verwijderd.',
        ]);

    $this->assertModelMissing($article);
});

/** @param array<string, mixed> $overrides */
function validArticlePayload(array $overrides = []): array
{
    return [
        'title' => 'Nieuw seizoen van start',
        'slug' => 'nieuw-seizoen-van-start',
        'content' => 'Dit seizoen gaan we weer racen op de baan in Alkmaar.',
        'category' => ArticleCategory::News->value,
        'status' => ArticleStatus::Draft->value,
        'published_at' => null,
        ...$overrides,
    ];
}
