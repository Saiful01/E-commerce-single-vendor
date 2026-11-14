@extends('layouts.admin')
@section('title', 'Abandoned Cart')

@section('content')
    <a href="{{ route('admin.abandoned-carts.index') }}" class="text-sm text-indigo-600 hover:underline">&larr; Back to abandoned carts</a>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-4">
        <div class="lg:col-span-2 bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 font-semibold text-gray-800">Cart items</div>
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
                <tr><th class="px-5 py-3 text-left">Product</th><th class="px-5 py-3 text-right">Price</th><th class="px-5 py-3 text-right">Qty</th><th class="px-5 py-3 text-right">Subtotal</th></tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                @foreach($cart->items as $item)
                    <tr>
                        <td class="px-5 py-3 text-gray-800">{{ $item['name'] ?? 'Product #' . ($item['product_id'] ?? '?') }}</td>
                        <td class="px-5 py-3 text-right text-gray-600">{{ number_format($item['price'] ?? 0, 2) }}</td>
                        <td class="px-5 py-3 text-right text-gray-600">{{ $item['qty'] ?? 1 }}</td>
                        <td class="px-5 py-3 text-right font-medium">{{ number_format(($item['price'] ?? 0) * ($item['qty'] ?? 1), 2) }}</td>
                    </tr>
                @endforeach
                </tbody>
                <tfoot>
                <tr class="bg-gray-50"><td colspan="3" class="px-5 py-3 text-right font-semibold">Total</td><td class="px-5 py-3 text-right font-bold text-indigo-600">{{ number_format($cart->total, 2) }}</td></tr>
                </tfoot>
            </table>
        </div>

        <div class="space-y-6">
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5 text-sm">
                <h3 class="font-semibold text-gray-800 mb-3">Customer</h3>
                <p class="text-gray-800 font-medium">{{ $cart->customer_name }}</p>
                <p class="text-gray-500">{{ $cart->contact_email ?? 'No email' }}</p>
                <p class="text-gray-500">{{ $cart->contact_phone ?? 'No phone' }}</p>
                <div class="mt-3 pt-3 border-t border-gray-100 text-xs text-gray-500 space-y-1">
                    <p>Created: {{ $cart->created_at->format('d M Y, h:i A') }}</p>
                    <p>Reminders sent: {{ $cart->reminder_count }}</p>
                    @if($cart->last_reminded_at)<p>Last reminder: {{ $cart->last_reminded_at->diffForHumans() }}</p>@endif
                    @if($cart->recovered_at)<p class="text-green-600 font-medium">Recovered: {{ $cart->recovered_at->format('d M Y') }}</p>@endif
                </div>
            </div>

            @if(!$cart->recovered_at)
                <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5">
                    <h3 class="font-semibold text-gray-800 mb-3">Send reminder</h3>
                    <div class="flex gap-2">
                        <form method="POST" action="{{ route('admin.abandoned-carts.remind', $cart) }}" class="flex-1">
                            @csrf <input type="hidden" name="channel" value="email">
                            <button class="w-full px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-xl">Email</button>
                        </form>
                        <form method="POST" action="{{ route('admin.abandoned-carts.remind', $cart) }}" class="flex-1">
                            @csrf <input type="hidden" name="channel" value="sms">
                            <button class="w-full px-4 py-2.5 bg-sky-600 hover:bg-sky-700 text-white text-sm font-semibold rounded-xl">SMS</button>
                        </form>
                    </div>
                    <p class="text-xs text-gray-400 mt-3">Template slug: <code>abandoned-cart-email</code> / <code>abandoned-cart-sms</code></p>
                </div>
            @endif
        </div>
    </div>
@endsection
