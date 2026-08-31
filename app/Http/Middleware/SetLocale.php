<?php
declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

final class SetLocale
{
    /** @var list<string> */
    private const SUPPORTED = ['ar', 'en'];

    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->resolveLocale($request);

        App::setLocale($locale);

        // Remember the selected locale in the session.
        $request->session()->put('locale', $locale);

        return $next($request);
    }

    private function resolveLocale(Request $request): string
    {
        /*
         * 1. Session has the highest priority.
         *
         * The user may have just changed the language using
         * POST /locale-switch.
         */
        $sessionLocale = $request->session()->get('locale');

        if (
            is_string($sessionLocale)
            && in_array($sessionLocale, self::SUPPORTED, true)
        ) {
            return $sessionLocale;
        }

        /*
         * 2. Authenticated user's saved preference.
         */
        $user = $request->user();

        if (
            $user !== null
            && isset($user->locale_preference)
            && is_string($user->locale_preference)
            && in_array($user->locale_preference, self::SUPPORTED, true)
        ) {
            return $user->locale_preference;
        }

        /*
         * 3. Default language.
         */
        return 'ar';
    }
}
