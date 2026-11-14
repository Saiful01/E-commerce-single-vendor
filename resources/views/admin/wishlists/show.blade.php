@extends('layouts.admin')
@section('title', 'Wishlist Details')

@section('content')
    @include('admin.partials.page-header', ['title' => ($wishlist->user->name ?? 'Customer') . '\'s Wishlist', 'subtitle' => $wishlist->user->email ?? ''])

    <a href="{{ route('admin.wishlists.index') }}" class="text-sm text-indigo-600 hover:underline">&larr; Back to wishlists</a>

    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm mt-4 divide-y divide-gray-100">
        @foreach($items as $item)
            <div class="flex items-center justify-between px-5 py-4 {{ $item->id === $wishlist->id ? 'bg-indigo-50/50' : '' }}">
                <div>
                    <p class="font-medium text-gray-800">{{ $item->product->name ?? 'Deleted product' }}</p>
                    <p class="text-xs text-gray-500">Added {{ $item->created_at->format('d M Y, h:i A') }}</p>
                </div>
                <span class="text-pink-500 text-lg">♥</span>
            </div>
        @endforeach
    </div>
@endsection
