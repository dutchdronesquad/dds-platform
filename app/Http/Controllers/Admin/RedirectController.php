<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Redirect;
use Inertia\Inertia;
use Inertia\Response;

final class RedirectController extends Controller
{
    public function index(): Response
    {
        $redirects = Redirect::query()
            ->latest('updated_at')
            ->get();

        return Inertia::render('admin/redirects/index', [
            'redirects' => $redirects->map(fn (Redirect $redirect): array => [
                'id' => $redirect->id,
                'sourcePath' => $redirect->source_path,
                'targetUrl' => $redirect->target_url,
                'statusCode' => $redirect->status_code,
                'isActive' => $redirect->is_active,
                'hitCount' => $redirect->hit_count,
                'notes' => $redirect->notes,
                'updatedAt' => $redirect->updated_at->toIso8601String(),
            ]),
            'summary' => [
                'total' => $redirects->count(),
                'active' => $redirects->where('is_active', true)->count(),
                'hits' => $redirects->sum('hit_count'),
            ],
        ]);
    }
}
