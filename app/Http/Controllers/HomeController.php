<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;

class HomeController extends Controller
{
    public function index()
    {
        $cards = fn () => Product::active()->with('category:id,name,slug');

        return view('home', [
            'categories'  => Category::active()->featured()->whereNull('parent_id')->ordered()->take(10)->get(),
            'deals'       => $cards()->whereNotNull('discount_price')->whereColumn('discount_price', '<', 'price')
                                ->latest()->take(8)->get(),
            'featured'    => $cards()->featured()->latest()->take(8)->get(),
            'newArrivals' => $cards()->latest()->take(8)->get(),
            'brands'      => Brand::active()->featured()->ordered()->take(12)->get(),
        ]);
    }
}
