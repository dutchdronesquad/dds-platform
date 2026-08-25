<?php

namespace App\Support;

use Illuminate\Support\Str;

final class MarkdownRenderer
{
    public function toHtml(?string $markdown): ?string
    {
        if ($markdown === null || trim($markdown) === '') {
            return null;
        }

        return Str::markdown($markdown, [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);
    }

    public function toPlainText(?string $markdown): string
    {
        $html = $this->toHtml($markdown);

        if ($html === null) {
            return '';
        }

        return Str::of(html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8'))
            ->squish()
            ->toString();
    }
}
