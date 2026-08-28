<?php

namespace App\Http\Requests\Admin;

use App\Enums\ArticleCategory;
use App\Enums\ArticleStatus;
use App\Models\Article;
use App\Models\MediaAsset;
use App\Models\User;
use App\Support\UtcDateTime;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreArticleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Article::class) === true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $article = $this->article();

        return [
            'author_id' => ['nullable', 'integer', Rule::exists(User::class, 'id')],
            'cover_image_id' => [
                'nullable',
                'integer',
                Rule::exists(MediaAsset::class, 'id'),
                function (string $attribute, mixed $value, Closure $fail) use ($article): void {
                    $mediaAsset = MediaAsset::query()
                        ->with('media')
                        ->find($value);

                    if (! $mediaAsset instanceof MediaAsset || ! $mediaAsset->isImage()) {
                        $fail('De geselecteerde omslagafbeelding is niet geldig.');

                        return;
                    }

                    if (
                        $mediaAsset->archived_at !== null
                        && $article?->cover_image_id !== $mediaAsset->id
                    ) {
                        $fail('Een gearchiveerde afbeelding kan niet als nieuwe omslag worden gekozen.');
                    }
                },
            ],
            'title' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:255',
                'alpha_dash:ascii',
                Rule::unique(Article::class, 'slug')->ignore($article),
            ],
            'content' => ['required', 'string', 'max:50000'],
            'category' => ['required', Rule::enum(ArticleCategory::class)],
            'status' => ['required', Rule::enum(ArticleStatus::class)],
            'published_at' => ['nullable', 'date'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'author_id' => 'auteur',
            'cover_image_id' => 'omslagafbeelding',
            'title' => 'titel',
            'slug' => 'URL-slug',
            'content' => 'inhoud',
            'category' => 'categorie',
            'status' => 'status',
            'published_at' => 'publicatiedatum',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->string('slug')->trim()->isEmpty() && $this->filled('title')) {
            $this->merge(['slug' => Str::slug($this->string('title')->toString())]);
        }

        if (
            $this->string('status')->toString() === ArticleStatus::Published->value
            && $this->string('published_at')->trim()->isEmpty()
        ) {
            $this->merge(['published_at' => now()->toIso8601String()]);
        }

        $this->merge(UtcDateTime::valuesForStorage([
            'published_at' => $this->input('published_at'),
        ]));
    }

    protected function article(): ?Article
    {
        $article = $this->route('article');

        return $article instanceof Article ? $article : null;
    }
}
