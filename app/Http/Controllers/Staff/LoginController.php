<?php

declare(strict_types=1);

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Modules\Accounts\Actions\AuthenticatePortalAccount;
use Modules\Accounts\Actions\LogoutPortalAccount;
use Modules\Accounts\Data\PortalAuthConfig;

/**
 * Thin Staff Portal login/logout controller.
 *
 * Staff authentication grants a staff account actor identity only.
 * It does NOT grant institution data access — that additionally requires
 * eligible active positions, F02 trusted operational context, and
 * Authorization policies (deferred to F13 and later).
 */
final class LoginController extends Controller
{
    public function show(): View|RedirectResponse
    {
        if (Auth::guard('staff')->check()) {
            return redirect()->route('staff.dashboard');
        }

        return view('portals.staff.login');
    }

    public function store(Request $request, AuthenticatePortalAccount $authenticate): RedirectResponse
    {
        $validated = $request->validate([
            'username' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
        ]);

        $result = $authenticate(
            config: PortalAuthConfig::staff(),
            rawIdentifier: $validated['username'],
            password: $validated['password'],
            ip: $request->ip() ?? '0.0.0.0',
            request: $request,
        );

        if ($result->isThrottled()) {
            return back()
                ->withErrors(['credentials' => __('Too many login attempts. Please try again later.')])
                ->withInput($request->only('username'))
                ->withHeaders(['Retry-After' => (string) $result->retryAfter]);
        }

        if ($result->isFailed()) {
            return back()
                ->withErrors(['credentials' => __('The provided credentials could not be verified.')])
                ->withInput($request->only('username'));
        }

        return redirect()->intended(route('staff.dashboard'));
    }

    public function destroy(Request $request, LogoutPortalAccount $logout): RedirectResponse
    {
        $logout(PortalAuthConfig::staff(), $request);

        return redirect()->route('staff.login');
    }
}
