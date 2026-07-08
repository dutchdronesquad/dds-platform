<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\LocaleUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cookie;
use Inertia\Inertia;

class LocaleController extends Controller
{
    public function update(LocaleUpdateRequest $request): RedirectResponse
    {
        $locale = (string) $request->validated('locale');

        if ($request->user() && config('localization.user_preference.enabled')) {
            $request->user()->forceFill([
                (string) config('localization.user_preference.column', 'locale') => $locale,
            ])->save();
        }

        Cookie::queue(Cookie::make(
            (string) config('localization.cookie.name', 'locale'),
            $locale,
            (int) config('localization.cookie.duration_minutes', 525600),
            null,
            null,
            null,
            false,
            false,
            'lax',
        ));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Language preference updated.')]);

        return back();
    }
}
