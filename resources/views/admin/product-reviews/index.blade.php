@extends('layouts.admin')
@section('title', 'Product Reviews')

@section('content')
    @include('admin.partials.page-header', ['title' => 'Product Reviews', 'subtitle' => 'Review approve / reject / delete korun.'])

    @include('admin.partials.tabs', ['tabs' => [
        ['label' => 'All', 'url' => route('admin.product-reviews.index'), 'active' => !$status, 'count' => $counts->sum()],
        ['label' => 'Pending', 'url' => route('admin.product-reviews.index', ['status' => 'pending']), 'active' => $status === 'pending', 'count' => $counts['pending'] ?? 0],
        ['label' => 'Approved', 'url' => route('admin.product-reviews.index', ['status' => 'approved']), 'active' => $status === 'approved', 'count' => $counts['approved'] ?? 0],
        ['label' => 'Rejected', 'url' => route('admin.product-reviews.index', ['status' => 'rejected']), 'active' => $status === 'rejected', 'count' => $counts['rejected'] ?? 0],
    ]])

    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
            <tr>
                <th class="px-5 py-3 text-left">Product</th>
                <th class="px-5 py-3 text-left">Customer</th>
                <th class="px-5 py-3 text-left">Rating</th>
                <th class="px-5 py-3 text-left">Review</th>
                <th class="px-5 py-3 text-left">Status</th>
                <th class="px-5 py-3 text-right">Actions</th>
            </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
            @forelse($reviews as $r)
                <tr class="hover:bg-gray-50 align-top">
                    <td class="px-5 py-3 font-medium text-gray-800">{{ $r->product->name ?? 'Deleted product' }}</td>
                    <td class="px-5 py-3 text-gray-600">{{ $r->user->name ?? 'Guest' }}</td>
                    <td class="px-5 py-3">@include('admin.partials.stars', ['rating' => $r->rating])</td>
                    <td class="px-5 py-3 max-w-xs">
                        @if($r->title)<p class="font-medium text-gray-800 truncate">{{ $r->title }}</p>@endif
                        <p class="text-gray-500 text-xs line-clamp-2">{{ $r->comment }}</p>
                    </td>
                    <td class="px-5 py-3">@include('admin.partials.badge', ['value' => $r->status])</td>
                    <td class="px-5 py-3 text-right whitespace-nowrap space-x-2">
                        @if($r->status !== 'approved')
                            <form method="POST" action="{{ route('admin.product-reviews.status', $r) }}" class="inline">
                                @csrf @method('PATCH')
                                <input type="hidden" name="status" value="approved">
                                <button class="text-green-600 hover:text-green-800 font-medium">Approve</button>
                            </form>
                        @endif
                        @if($r->status !== 'rejected')
                            <form method="POST" action="{{ route('admin.product-reviews.status', $r) }}" class="inline">
                                @csrf @method('PATCH')
                                <input type="hidden" name="status" value="rejected">
                                <button class="text-amber-600 hover:text-amber-800 font-medium">Reject</button>
                            </form>
                        @endif
                        <a href="{{ route('admin.product-reviews.show', $r) }}" class="text-indigo-600 hover:text-indigo-800 font-medium">View</a>
                        @include('admin.partials.delete-form', ['key' => 'review-' . $r->id, 'action' => route('admin.product-reviews.destroy', $r)])
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="px-5 py-10 text-center text-gray-400">No reviews found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $reviews->links() }}</div>
@endsection
