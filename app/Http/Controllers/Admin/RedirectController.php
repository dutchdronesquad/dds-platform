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
            ->select([
                'id',
                'source_path',
                'target_url',
                'status_code',
                'is_active',
                'hit_count',
                'notes',
                'updated_at',
            ])
            ->latest('updated_at')
            ->paginate(50)
            ->through(fn (Redirect $redirect): array => [
                'id' => $redirect->id,
                'sourcePath' => $redirect->source_path,
                'targetUrl' => $redirect->target_url,
                'statusCode' => $redirect->status_code,
                'isActive' => $redirect->is_active,
                'hitCount' => $redirect->hit_count,
                'notes' => $redirect->notes,
                'updatedAt' => $redirect->updated_at->toIso8601String(),
            ]);

        return Inertia::render('admin/redirects/index', [
            'redirects' => $redirects,
            'summary' => [
                'total' => $redirects->total(),
                'active' => Redirect::query()->active()->count(),
                'hits' => (int) Redirect::query()->sum('hit_count'),
            ],
        ]);
    }
}
