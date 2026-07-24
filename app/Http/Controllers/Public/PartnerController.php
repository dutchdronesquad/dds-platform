<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Support\PublicPartnerData;
use App\Support\SeoMetadata;
use Inertia\Inertia;
use Inertia\Response;

final class PartnerController extends Controller
{
    public function __construct(private PublicPartnerData $partnerData) {}

    public function index(SeoMetadata $seoMetadata): Response
    {
        return Inertia::render('public/partners-index', [
            'partners' => $this->partnerData->all(),
            'seo' => $seoMetadata->forPage('partners'),
        ]);
    }
}
