<?php

namespace App\Http\Requests\Admin;

use App\Models\Article;

class UpdateArticleRequest extends StoreArticleRequest
{
    public function authorize(): bool
    {
        $article = $this->route('article');

        return $article instanceof Article
            && $this->user()?->can('update', $article) === true;
    }
}
