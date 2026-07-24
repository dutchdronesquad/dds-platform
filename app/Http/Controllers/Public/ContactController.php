<?php

namespace App\Http\Controllers\Public;

use App\Actions\SubmitContactRequest;
use App\Enums\ContactTopic;
use App\Http\Controllers\Controller;
use App\Http\Requests\Public\StoreContactRequest;
use App\Support\SeoMetadata;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

final class ContactController extends Controller
{
    public function index(Request $request, SeoMetadata $seoMetadata): Response
    {
        $sourceContext = Str::substr(
            $request->string('source')->trim()->toString(),
            0,
            255,
        );

        return Inertia::render('public/contact', [
            'page' => config('public_pages.contact'),
            'seo' => $seoMetadata->forPage('contact'),
            'sourceContext' => $sourceContext !== '' ? $sourceContext : null,
            'topics' => array_map(
                fn (ContactTopic $topic): array => [
                    'value' => $topic->value,
                    'label' => $topic->label(),
                ],
                ContactTopic::cases(),
            ),
        ]);
    }

    public function store(
        StoreContactRequest $request,
        SubmitContactRequest $submitContactRequest,
    ): RedirectResponse {
        $submitContactRequest->handle($request->contactData());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Bedankt! Je bericht is opgeslagen. We nemen zo snel mogelijk contact met je op.',
        ]);

        return to_route('contact');
    }
}
