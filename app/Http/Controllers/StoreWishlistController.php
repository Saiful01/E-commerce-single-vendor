<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Wishlist;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StoreWishlistController extends Controller
{
    public function index(Request $request)
    {
        $ids = Wishlist::where('user_id', $request->user()->id)->latest()->pluck('product_id');

        $products = Product::active()->with('category:id,name,slug')->whereIn('id', $ids)->get()
            ->sortBy(fn ($p) => $ids->search($p->id))->values();

        return view('account.wishlist', compact('products'));
    }

    public function toggle(Request $request): JsonResponse
    {
        $data = $request->validate(['product_id' => ['required', 'integer', 'exists:products,id']]);

        $row = Wishlist::where('user_id', $request->user()->id)->where('product_id', $data['product_id']);

        if ($row->exists()) {
            $row->delete();
            $added = false;
        } else {
            Wishlist::create(['user_id' => $request->user()->id, 'product_id' => $data['product_id']]);
            $added = true;
        }

        return response()->json(['added' => $added, 'count' => Wishlist::where('user_id', $request->user()->id)->count()]);
    }
}
