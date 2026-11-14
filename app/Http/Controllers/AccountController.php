<?php

namespace App\Http\Controllers;

use App\Models\Wishlist;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AccountController extends Controller
{
    public function dashboard(Request $request)
    {
        $user   = $request->user();
        $orders = $user->orders();

        return view('account.dashboard', [
            'user'    => $user,
            'stats'   => [
                'orders'   => (clone $orders)->count(),
                'active'   => (clone $orders)->whereHas('status', fn ($s) => $s->where('is_final', false))->count(),
                // Cancelled / returned orders do not count as spending
                'spent'    => (clone $orders)->whereHas('status', fn ($s) => $s->where('restock_items', false))->sum('total'),
                'wishlist' => Wishlist::where('user_id', $user->id)->count(),
            ],
            'recent'  => (clone $orders)->with(['status', 'items'])->latest()->take(5)->get(),
            'address' => $user->shippingAddress,
        ]);
    }

    public function profile(Request $request)
    {
        return view('account.profile', ['user' => $request->user()]);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'name'  => ['required', 'string', 'max:100'],
            'phone' => ['required', 'string', 'max:20', 'regex:/^[0-9+\-\s()]+$/', Rule::unique('users', 'phone')->ignore($user->id)],
        ]);

        $user->update($data);

        return back()->with('success', 'Profile updated.');
    }
}
