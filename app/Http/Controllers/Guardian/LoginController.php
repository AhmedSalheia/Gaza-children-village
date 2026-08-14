<?php

declare(strict_types=1);

namespace App\Http\Controllers\Guardian;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Modules\Accounts\Actions\AuthenticatePortalAccount;
use Modules\Accounts\Actions\LogoutPortalAccount;
use Modules\Accounts\Data\PortalAuthConfig;

/**
 * Thin Guardian / Parent-Student Portal login/logout controller.
 *
 * Login identifier: opaque login_identifier (not labeled or stored as national ID).
 * Guardian national-ID resolution remains deferred until approved person
 * identifiers exist (F11/F13).
 *
 * Guardian authentication grants a guardian account actor identity only.
 * No student records are accessible until a future verified guardian-student
 * relationship exists (deferred to F13/F15).
 */
final class LoginController extends Controller
{
    public function show(): View|RedirectResponse
    {
        if (Auth::guard('guardian')->check()) {
            return redirect()->route('guardian.dashboard');
        }

        return view('portals.guardian.login');
    }

    public function store(Request $request, AuthenticatePortalAccount $authenticate): RedirectResponse
    {
        $validated = $request->validate([
            'login_identifier' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
        ]);

        $result = $authenticate(
            config: PortalAuthConfig::guardian(),
            rawIdentifier: $validated['login_identifier'],
            password: $validated['password'],
            ip: $request->ip() ?? '0.0.0.0',
            request: $request,
        );

        if ($result->isThrottled()) {
            return back()
                ->withErrors(['credentials' => __('Too many login attempts. Please try again later.')])
                ->withInput($request->only('login_identifier'))
                ->withHeaders(['Retry-After' => (string) $result->retryAfter]);
        }

        if ($result->isFailed()) {
            return back()
                ->withErrors(['credentials' => __('The provided credentials could not be verified.')])
                ->withInput($request->only('login_identifier'));
        }

        return redirect()->intended(route('guardian.dashboard'));
    }

    public function destroy(Request $request, LogoutPortalAccount $logout): RedirectResponse
    {
        $logout(PortalAuthConfig::guardian(), $request);

        return redirect()->route('guardian.login');
    }
}
