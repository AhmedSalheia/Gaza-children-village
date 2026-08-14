<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Modules\Accounts\Actions\AuthenticatePortalAccount;
use Modules\Accounts\Actions\LogoutPortalAccount;
use Modules\Accounts\Data\PortalAuthConfig;

/**
 * Thin Admin Portal login/logout controller.
 *
 * All credential validation, lifecycle checking, rate limiting, session
 * management, and event recording are delegated to Accounts-owned actions.
 * This controller handles only HTTP concerns: request validation, response
 * routing, and view rendering.
 *
 * Security: guard and redirect targets are never derived from request input.
 */
final class LoginController extends Controller
{
    public function show(): View|RedirectResponse
    {
        if (Auth::guard('admin')->check()) {
            return redirect()->route('admin.dashboard');
        }

        return view('portals.admin.login');
    }

    public function store(Request $request, AuthenticatePortalAccount $authenticate): RedirectResponse
    {
        $validated = $request->validate([
            'username' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
        ]);

        $result = $authenticate(
            config: PortalAuthConfig::admin(),
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

        return redirect()->intended(route('admin.dashboard'));
    }

    public function destroy(Request $request, LogoutPortalAccount $logout): RedirectResponse
    {
        $logout(PortalAuthConfig::admin(), $request);

        return redirect()->route('admin.login');
    }
}
