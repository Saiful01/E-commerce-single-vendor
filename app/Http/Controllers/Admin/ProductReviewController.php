<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductReview;
use Illuminate\Http\Request;

class ProductReviewController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status');

        $reviews = ProductReview::with(['user', 'product'])
            ->when(in_array($status, ProductReview::STATUSES), fn ($q) => $q->where('status', $status))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $counts = ProductReview::selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('admin.product-reviews.index', compact('reviews', 'counts', 'status'));
    }

    public function show(ProductReview $productReview)
    {
        $productReview->load(['user', 'product']);

        return view('admin.product-reviews.show', ['review' => $productReview]);
    }

    public function updateStatus(Request $request, ProductReview $productReview)
    {
        $data = $request->validate([
            'status' => 'required|in:' . implode(',', ProductReview::STATUSES),
        ]);

        $productReview->update($data);

        return back()->with('success', 'Review status updated to ' . $data['status'] . '.');
    }

    public function destroy(ProductReview $productReview)
    {
        $productReview->delete();

        return redirect()->route('admin.product-reviews.index')->with('success', 'Review deleted.');
    }
}
