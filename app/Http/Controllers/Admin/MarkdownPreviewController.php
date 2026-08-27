<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RenderMarkdownPreviewRequest;
use App\Support\MarkdownRenderer;
use Illuminate\Http\JsonResponse;

final class MarkdownPreviewController extends Controller
{
    public function __construct(private MarkdownRenderer $markdown) {}

    public function __invoke(RenderMarkdownPreviewRequest $request): JsonResponse
    {
        return response()->json([
            'html' => $this->markdown->toHtml($request->string('markdown')->toString()),
        ]);
    }
}
