<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

/**
 * Public registration creates customers only.
 * Admin panel users are created from the admin panel (Users).
 */
class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(Request $request): RedirectResponse
    {
        // Same clean-up the admin order screen uses to find a customer by phone
        $request->merge([
            'phone' => preg_replace('/[\s\-()]+/', '', (string) $request->input('phone')),
            'email' => Str::lower(trim((string) $request->input('email'))),
        ]);

        $data = $request->validate([
            'name'     => ['required', 'string', 'max:100'],
            'phone'    => ['required', 'string', 'max:20', 'regex:/^\+?[0-9]{8,15}$/', Rule::unique('users', 'phone')],
            'email'    => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ], [
            'phone.regex'  => 'Enter a valid mobile number, for example 01712345678.',
            'phone.unique' => 'This mobile number already has an account. Try signing in.',
            'email.unique' => 'This email already has an account. Try signing in.',
        ]);

        // user_type and status are set here on purpose, never read from the form
        $user = User::create([
            'name'      => $data['name'],
            'phone'     => $data['phone'],
            'email'     => $data['email'],
            'password'  => Hash::make($data['password']),
            'user_type' => 'customer',
            'status'    => true,
        ]);

        event(new Registered($user));

        Auth::login($user);
        $request->session()->regenerate();

        $intended = $request->session()->pull('url.intended');
        if ($intended && str_contains((string) parse_url($intended, PHP_URL_PATH), '/admin')) {
            $intended = null;
        }

        return redirect()->to($intended ?: route('account.dashboard'))->with('success', 'Welcome! Your account is ready.');
    }
}
