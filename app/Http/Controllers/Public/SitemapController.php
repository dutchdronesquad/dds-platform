<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Support\PublicSitemap;
use Spatie\Sitemap\Sitemap;

final class SitemapController extends Controller
{
    public function __invoke(PublicSitemap $publicSitemap): Sitemap
    {
        return $publicSitemap->build();
    }
}
