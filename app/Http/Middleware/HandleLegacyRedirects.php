<?php

namespace App\Http\Middleware;

use App\Models\Redirect;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Uri;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

final class HandleLegacyRedirects
{
    private const int MAX_REDIRECT_CHAIN_LENGTH = 10;

    /** @var list<int> */
    private const array SUPPORTED_STATUS_CODES = [301, 302, 307, 308];

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->isMethodSafe()) {
            return $next($request);
        }

        $sourcePath = Redirect::normalizePath('/'.$request->path());
        $redirect = Redirect::query()
            ->active()
            ->where('source_path', $sourcePath)
            ->first();

        if (
            ! $redirect
            || ! in_array($redirect->status_code, self::SUPPORTED_STATUS_CODES, true)
            || ! $this->hasValidTarget($redirect->target_url)
            || $this->wouldCreateLoop($redirect, $request)
        ) {
            return $next($request);
        }

        $redirect->increment('hit_count');

        return redirect()->to($redirect->target_url, $redirect->status_code);
    }

    private function hasValidTarget(string $targetUrl): bool
    {
        if (Str::startsWith($targetUrl, '/') && ! Str::startsWith($targetUrl, '//')) {
            return true;
        }

        try {
            $uri = Uri::of($targetUrl);
        } catch (Throwable) {
            return false;
        }

        return in_array($uri->scheme(), ['http', 'https'], true) && $uri->host() !== null;
    }

    private function wouldCreateLoop(Redirect $redirect, Request $request): bool
    {
        $visitedPaths = [$redirect->source_path => true];
        $currentRedirect = $redirect;

        for ($depth = 0; $depth < self::MAX_REDIRECT_CHAIN_LENGTH; $depth++) {
            $targetPath = $this->localTargetPath($currentRedirect->target_url, $request);

            if ($targetPath === null) {
                return false;
            }

            if (isset($visitedPaths[$targetPath])) {
                return true;
            }

            $visitedPaths[$targetPath] = true;
            $currentRedirect = Redirect::query()
                ->active()
                ->where('source_path', $targetPath)
                ->first();

            if (! $currentRedirect) {
                return false;
            }
        }

        return true;
    }

    private function localTargetPath(string $targetUrl, Request $request): ?string
    {
        $uri = Uri::of($targetUrl);
        $targetHost = $uri->host();

        if ($targetHost !== null) {
            $applicationHost = Uri::of((string) config('app.url'))->host();
            $localHosts = array_filter([$request->getHost(), $applicationHost]);

            if (! in_array(Str::lower($targetHost), array_map(Str::lower(...), $localHosts), true)) {
                return null;
            }
        }

        return Redirect::normalizePath($uri->path());
    }
}
