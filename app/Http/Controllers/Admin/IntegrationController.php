<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TrackDrawConnection;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

final class IntegrationController extends Controller
{
    public function __invoke(): Response
    {
        Gate::authorize('manage', TrackDrawConnection::class);

        return Inertia::render('admin/integrations', [
            'trackDrawConnectionCount' => TrackDrawConnection::query()->count(),
        ]);
    }
}
