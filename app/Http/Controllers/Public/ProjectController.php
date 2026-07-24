<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Support\PublicProjectData;
use App\Support\SeoMetadata;
use Inertia\Inertia;
use Inertia\Response;

final class ProjectController extends Controller
{
    public function __construct(private PublicProjectData $projectData) {}

    public function index(SeoMetadata $seoMetadata): Response
    {
        return Inertia::render('public/projects-index', [
            'projects' => $this->projectData->forOverview(),
            'seo' => $seoMetadata->forPage('projects'),
        ]);
    }
}
