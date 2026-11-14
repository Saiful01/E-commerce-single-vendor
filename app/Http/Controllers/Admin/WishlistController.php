<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Wishlist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WishlistController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->string('q')->toString();

        $wishlists = Wishlist::with(['user', 'product'])
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->whereHas('product', fn ($p) => $p->where('name', 'like', "%{$search}%"))
                      ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%")
                          ->orWhere('email', 'like', "%{$search}%"));
                });
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $topProducts = Wishlist::select('product_id', DB::raw('COUNT(*) as total'))
            ->groupBy('product_id')
            ->orderByDesc('total')
            ->with('product')
            ->limit(5)
            ->get();

        return view('admin.wishlists.index', compact('wishlists', 'topProducts', 'search'));
    }

    public function show(Wishlist $wishlist)
    {
        $wishlist->load('user');

        $items = Wishlist::with('product')
            ->where('user_id', $wishlist->user_id)
            ->latest()
            ->get();

        return view('admin.wishlists.show', compact('wishlist', 'items'));
    }
}
