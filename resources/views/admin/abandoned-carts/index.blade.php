@extends('layouts.admin')
@section('title', 'Abandoned Carts')

@section('content')
    @include('admin.partials.page-header', ['title' => 'Abandoned Carts', 'subtitle' => 'Je customer cart-e product rekhe order kore ni.'])

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5">
            <p class="text-xs text-gray-500 uppercase">Open carts</p>
            <p class="text-2xl font-bold text-gray-800 mt-1">{{ $stats['open'] }}</p>
        </div>
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5">
            <p class="text-xs text-gray-500 uppercase">Potential revenue</p>
            <p class="text-2xl font-bold text-indigo-600 mt-1">{{ number_format($stats['open_value'], 2) }}</p>
        </div>
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5">
            <p class="text-xs text-gray-500 uppercase">Recovered</p>
            <p class="text-2xl font-bold text-green-600 mt-1">{{ $stats['recovered'] }}</p>
        </div>
    </div>

    @include('admin.partials.tabs', ['tabs' => [
        ['label' => 'Open', 'url' => route('admin.abandoned-carts.index', ['filter' => 'open']), 'active' => $filter === 'open'],
        ['label' => 'Recovered', 'url' => route('admin.abandoned-carts.index', ['filter' => 'recovered']), 'active' => $filter === 'recovered'],
        ['label' => 'All', 'url' => route('admin.abandoned-carts.index', ['filter' => 'all']), 'active' => $filter === 'all'],
    ]])

    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
            <tr>
                <th class="px-5 py-3 text-left">Customer</th>
                <th class="px-5 py-3 text-left">Items</th>
                <th class="px-5 py-3 text-left">Total</th>
                <th class="px-5 py-3 text-left">Reminders</th>
                <th class="px-5 py-3 text-left">Status</th>
                <th class="px-5 py-3 text-left">Last activity</th>
                <th class="px-5 py-3 text-right">Action</th>
            </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
            @forelse($carts as $c)
                <tr class="hover:bg-gray-50">
                    <td class="px-5 py-3">
                        <p class="font-medium text-gray-800">{{ $c->customer_name }}</p>
                        <p class="text-xs text-gray-500">{{ $c->contact_email ?? $c->contact_phone ?? '—' }}</p>
                    </td>
                    <td class="px-5 py-3 text-gray-600">{{ $c->items_count }}</td>
                    <td class="px-5 py-3 font-semibold text-gray-800">{{ number_format($c->total, 2) }}</td>
                    <td class="px-5 py-3 text-gray-600">
                        {{ $c->reminder_count }}
                        @if($c->last_reminded_at)<span class="text-xs text-gray-400 block">{{ $c->last_reminded_at->diffForHumans() }}</span>@endif
                    </td>
                    <td class="px-5 py-3">@include('admin.partials.badge', ['value' => $c->recovered_at ? 'recovered' : 'open'])</td>
                    <td class="px-5 py-3 text-gray-500">{{ $c->updated_at->diffForHumans() }}</td>
                    <td class="px-5 py-3 text-right">
                        <a href="{{ route('admin.abandoned-carts.show', $c) }}" class="text-indigo-600 hover:text-indigo-800 font-medium">View</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="px-5 py-10 text-center text-gray-400">No abandoned carts.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $carts->links() }}</div>
@endsection
