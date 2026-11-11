<?php

namespace App\Http\Controllers;

use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
    /**
     * Display the login page.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Authenticate the user.
     */
    public function store(
        Request $request,
        AuditLogger $auditLogger
    ): RedirectResponse {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (Auth::attempt(
            array_merge($credentials, ['is_active' => true]),
            $request->boolean('remember')
        )) {
            // Regenerate the session to prevent session fixation.
            $request->session()->regenerate();

            // Record successful authentication.
            $auditLogger->record('auth.login_succeeded');

            return redirect()->intended(
                route('dashboard')
            );
        }

        return back()
            ->withErrors([
                'email' => 'The provided credentials do not match our records.',
            ])
            ->onlyInput('email');
    }

    /**
     * Log out the authenticated user.
     */
    public function destroy(
        Request $request,
        AuditLogger $auditLogger
    ): RedirectResponse {
        // Record the logout before clearing the authenticated session.
        $auditLogger->record(
            'auth.logged_out',
            actor: $request->user()
        );

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('login')
            ->with('status', 'You have been logged out successfully.');
    }
}