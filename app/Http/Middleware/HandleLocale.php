<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class HandleLocale
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        App::setLocale($this->resolveLocale($request));

        return $next($request);
    }

    protected function resolveLocale(Request $request): string
    {
        $supportedLocales = $this->supportedLocales();

        $candidates = [
            $request->user()?->getAttribute((string) config('localization.user_preference.column', 'locale')),
            $request->cookies->get((string) config('localization.cookie.name', 'locale')),
            $request->headers->has('Accept-Language') ? $request->getPreferredLanguage($supportedLocales) : null,
            config('localization.default_locale'),
            config('app.locale'),
        ];

        foreach ($candidates as $candidate) {
            if (is_string($candidate) && in_array($candidate, $supportedLocales, true)) {
                return $candidate;
            }
        }

        return 'en';
    }

    /**
     * @return list<string>
     */
    protected function supportedLocales(): array
    {
        $locales = config('localization.supported_locales', []);

        if (! is_array($locales)) {
            return [];
        }

        return array_keys($locales);
    }
}
