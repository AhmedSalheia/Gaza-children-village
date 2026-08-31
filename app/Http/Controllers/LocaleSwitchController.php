<?php


declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class LocaleSwitchController extends Controller
{
    private const SUPPORTED = ['ar', 'en'];

    public function __invoke(Request $request): RedirectResponse
    {
        $locale = $request->input('locale', 'ar');

        if (! in_array($locale, self::SUPPORTED, true)) {
            $locale = 'ar';
        }

        // Store selected language in session.
        $request->session()->put('locale', $locale);

        // Persist selected language for authenticated user.
        $user = $request->user();

        if ($user !== null) {
            $user->locale_preference = $locale;
            $user->save();
        }

        return back();
    }
}
