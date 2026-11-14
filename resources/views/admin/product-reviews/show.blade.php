@extends('layouts.admin')
@section('title', 'Review Details')

@section('content')
    <a href="{{ route('admin.product-reviews.index') }}" class="text-sm text-indigo-600 hover:underline">&larr; Back to reviews</a>

    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm mt-4 p-6 max-w-3xl">
        <div class="flex items-start justify-between gap-4">
            <div>
                <p class="text-xs text-gray-500">Product</p>
                <h2 class="text-lg font-semibold text-gray-800">{{ $review->product->name ?? 'Deleted product' }}</h2>
            </div>
            @include('admin.partials.badge', ['value' => $review->status])
        </div>

        <div class="grid grid-cols-2 gap-4 mt-5 text-sm">
            <div><p class="text-xs text-gray-500">Customer</p><p class="font-medium text-gray-800">{{ $review->user->name ?? 'Guest' }}</p></div>
            <div><p class="text-xs text-gray-500">Submitted</p><p class="font-medium text-gray-800">{{ $review->created_at->format('d M Y, h:i A') }}</p></div>
            <div><p class="text-xs text-gray-500">Rating</p>@include('admin.partials.stars', ['rating' => $review->rating])</div>
        </div>

        <div class="mt-5">
            @if($review->title)<h3 class="font-semibold text-gray-800">{{ $review->title }}</h3>@endif
            <p class="text-gray-600 mt-1 whitespace-pre-line">{{ $review->comment }}</p>
        </div>

        <div class="flex flex-wrap items-center gap-3 mt-6 pt-5 border-t border-gray-100">
            @foreach(['approved' => 'Approve', 'rejected' => 'Reject', 'pending' => 'Mark pending'] as $st => $label)
                @if($review->status !== $st)
                    <form method="POST" action="{{ route('admin.product-reviews.status', $review) }}">
                        @csrf @method('PATCH')
                        <input type="hidden" name="status" value="{{ $st }}">
                        <button class="px-4 py-2 rounded-xl text-sm font-semibold text-white {{ $st === 'approved' ? 'bg-green-600 hover:bg-green-700' : ($st === 'rejected' ? 'bg-amber-500 hover:bg-amber-600' : 'bg-slate-500 hover:bg-slate-600') }}">{{ $label }}</button>
                    </form>
                @endif
            @endforeach
            <div class="ml-auto">
                @include('admin.partials.delete-form', ['key' => 'review-' . $review->id, 'action' => route('admin.product-reviews.destroy', $review)])
            </div>
        </div>
    </div>
@endsection
