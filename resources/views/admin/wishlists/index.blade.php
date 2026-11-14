@extends('layouts.admin')
@section('title', 'Wishlists')

@section('content')
    @include('admin.partials.page-header', ['title' => 'Wishlists', 'subtitle' => 'Customers ki ki wishlist-e rakhche (view only).'])

    @if($topProducts->isNotEmpty())
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5 mb-6">
            <h3 class="text-sm font-semibold text-gray-700 mb-3">Most wishlisted products</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
                @foreach($topProducts as $row)
                    <div class="rounded-xl bg-slate-50 border border-slate-100 p-3">
                        <p class="text-sm font-medium text-gray-800 truncate">{{ $row->product->name ?? 'Deleted product' }}</p>
                        <p class="text-xs text-pink-600 font-semibold mt-1">♥ {{ $row->total }} wishlists</p>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <form method="GET" class="mb-4 flex gap-2">
        <input type="text" name="q" value="{{ $search }}" placeholder="Search product or customer..."
               class="w-full sm:w-80 rounded-xl border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
        <button class="px-4 py-2 bg-slate-800 text-white text-sm rounded-xl">Search</button>
        @if($search)<a href="{{ route('admin.wishlists.index') }}" class="px-4 py-2 text-sm text-gray-600">Reset</a>@endif
    </form>

    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
            <tr>
                <th class="px-5 py-3 text-left">Customer</th>
                <th class="px-5 py-3 text-left">Product</th>
                <th class="px-5 py-3 text-left">Added</th>
                <th class="px-5 py-3 text-right">Action</th>
            </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
            @forelse($wishlists as $w)
                <tr class="hover:bg-gray-50">
                    <td class="px-5 py-3">
                        <p class="font-medium text-gray-800">{{ $w->user->name ?? 'Deleted user' }}</p>
                        <p class="text-xs text-gray-500">{{ $w->user->email ?? '' }}</p>
                    </td>
                    <td class="px-5 py-3 text-gray-700">{{ $w->product->name ?? 'Deleted product' }}</td>
                    <td class="px-5 py-3 text-gray-500">{{ $w->created_at->diffForHumans() }}</td>
                    <td class="px-5 py-3 text-right">
                        <a href="{{ route('admin.wishlists.show', $w) }}" class="text-indigo-600 hover:text-indigo-800 font-medium">View</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="4" class="px-5 py-10 text-center text-gray-400">No wishlist items yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $wishlists->links() }}</div>
@endsection
