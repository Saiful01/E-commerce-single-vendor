<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        // ইউজার লগইন করা আছে কিনা এবং সে Admin কিনা চেক করা
        if (Auth::check() && Auth::user()->isAdmin()) {
            if (!Auth::user()->status) {
                Auth::logout();
                return redirect()->route('login')->with('error', 'Your account is disabled.');
            }
            return $next($request);
        }

        return redirect('/')->with('error', 'Unauthorized access.');
    }
}
