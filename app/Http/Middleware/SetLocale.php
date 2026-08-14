<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

/**
 * Set the application locale per request.
 *
 * Priority order (F20):
 *   1. Authenticated account's locale_preference column (if set).
 *   2. Session 'locale' key (set by POST /locale-switch).
 *   3. Default: 'ar' (Arabic — GCV default per spec).
 *
 * Supported locales: 'ar', 'en'. Unknown values fall back to 'ar'.
 *
 * Timezone is always Asia/Gaza (set in config/app.php; this middleware
 * does not override it).
 */
final class SetLocale
{
    /** @var list<string> */
    private const SUPPORTED = ['ar', 'en'];

    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->resolveLocale($request);
        App::setLocale($locale);

        // Persist to session for unauthenticated requests so subsequent
        // page loads remember the choice without requiring login.
        if (! $request->session()->has('locale') || $request->session()->get('locale') !== $locale) {
            $request->session()->put('locale', $locale);
        }

        return $next($request);
    }

    private function resolveLocale(Request $request): string
    {
        // 1. Authenticated account preference
        $user = $request->user();
        if ($user !== null && isset($user->locale_preference) && $user->locale_preference !== null) {
            $pref = $user->locale_preference;
            if (in_array($pref, self::SUPPORTED, true)) {
                return $pref;
            }
        }

        // 2. Session
        $sessionLocale = $request->session()->get('locale');
        if (is_string($sessionLocale) && in_array($sessionLocale, self::SUPPORTED, true)) {
            return $sessionLocale;
        }

        // 3. Default
        return 'ar';
    }
}
