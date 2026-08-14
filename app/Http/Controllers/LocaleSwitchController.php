<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * POST /locale-switch — store the chosen locale in the session and,
 * if the user is authenticated, persist it to their account.
 *
 * Route is portal-specific and CSRF-protected (web middleware group).
 * Unauthenticated users get a session-level preference only.
 */
final class LocaleSwitchController extends Controller
{
    private const SUPPORTED = ['ar', 'en'];

    public function __invoke(Request $request): RedirectResponse
    {
        $locale = $request->input('locale', 'ar');

        if (! in_array($locale, self::SUPPORTED, true)) {
            $locale = 'ar';
        }

        $request->session()->put('locale', $locale);

        // Persist to account record if authenticated.
        $user = $request->user();
        if ($user !== null && method_exists($user, 'save') && isset($user->locale_preference)) {
            $user->locale_preference = $locale;
            $user->save();
        }

        return back()->withHeaders([
            'Vary' => 'Accept-Language',
        ]);
    }
}
