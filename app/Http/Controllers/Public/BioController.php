<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Support\BioPageData;
use App\Support\SeoMetadata;
use Inertia\Inertia;
use Inertia\Response;

final class BioController extends Controller
{
    public function __invoke(BioPageData $bioPageData, SeoMetadata $seoMetadata): Response
    {
        return Inertia::render('public/bio', [
            ...$bioPageData->forPage(),
            'seo' => $seoMetadata->forPage('bio'),
        ]);
    }
}
