<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();
        $request->session()->regenerate();

        $user = $request->user();

        // Switched-off accounts (admin panel > Users > toggle) cannot sign in
        if ($user->status === false) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            throw ValidationException::withMessages([
                'login' => 'This account is switched off. Please contact the store.',
            ]);
        }

        // Admin panel users go to the admin panel, customers to their own panel
        $home = $user->isAdmin() ? route('admin.dashboard') : route('account.dashboard');

        $intended = $request->session()->pull('url.intended');
        if ($intended && ! $user->isAdmin() && str_contains((string) parse_url($intended, PHP_URL_PATH), '/admin')) {
            $intended = null;   // a customer must never be sent to an admin URL
        }

        return redirect()->to($intended ?: $home);
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
