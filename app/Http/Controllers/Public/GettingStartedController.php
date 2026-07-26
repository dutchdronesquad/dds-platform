<?php

namespace App\Http\Controllers\Public;

use App\Enums\GettingStartedEntrySource;
use App\Http\Controllers\Controller;
use App\Support\GettingStartedGuide;
use App\Support\GettingStartedGuides;
use App\Support\SeoMetadata;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class GettingStartedController extends Controller
{
    public function index(Request $request, SeoMetadata $seoMetadata): Response
    {
        $guides = GettingStartedGuides::fromConfig();

        return Inertia::render('public/getting-started/index', [
            'entrySource' => $this->entrySource($request),
            'guides' => array_map(
                fn (GettingStartedGuide $guide): array => $this->guideSummary($guide),
                $guides->all(),
            ),
            'seo' => $seoMetadata->forPage('getting_started'),
        ]);
    }

    public function show(string $guide, Request $request, SeoMetadata $seoMetadata): Response
    {
        $guideEntry = GettingStartedGuides::fromConfig()->find($guide);

        abort_unless($guideEntry instanceof GettingStartedGuide, 404);

        return Inertia::render("public/getting-started/{$guideEntry->slug}", [
            'entrySource' => $this->entrySource($request),
            'guide' => $this->guideSummary($guideEntry),
            'seo' => $seoMetadata->forPage('getting_started', [
                'title' => $guideEntry->title,
                'description' => $guideEntry->summary,
                'canonical_path' => "/getting-started/{$guideEntry->slug}",
                'image_path' => $guideEntry->heroImage['src'],
                'image_alt' => $guideEntry->heroImage['alt'],
            ]),
        ]);
    }

    private function entrySource(Request $request): ?string
    {
        $source = $request->input('source');

        if (! is_string($source)) {
            return null;
        }

        return GettingStartedEntrySource::tryFrom($source)?->value;
    }

    /**
     * @return array{editorialOwner: string, eyebrow: string, heroImage: array{src: string, alt: string, position?: string}, reviewedAt: string, slug: string, summary: string, title: string}
     */
    private function guideSummary(GettingStartedGuide $guide): array
    {
        return [
            'editorialOwner' => $guide->editorialOwner,
            'slug' => $guide->slug,
            'title' => $guide->title,
            'eyebrow' => $guide->eyebrow,
            'summary' => $guide->summary,
            'heroImage' => $guide->heroImage,
            'reviewedAt' => $guide->reviewedAt,
        ];
    }
}
