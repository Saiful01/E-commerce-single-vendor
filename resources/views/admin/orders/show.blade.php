@extends('layouts.admin')

@section('title', 'Order ' . $order->order_number)

@section('content')
    @php
        $money = \App\Models\SiteSetting::current();

        $paymentStyles = [
            'unpaid'   => 'bg-rose-50 text-rose-700 border-rose-200',
            'partial'  => 'bg-amber-50 text-amber-700 border-amber-200',
            'paid'     => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            'refunded' => 'bg-slate-100 text-slate-600 border-slate-200',
        ];

        $isFinal = $order->status->is_final;

        $input = 'w-full text-xs rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500';
        $label = 'block text-xs font-semibold text-slate-700 mb-1';
        $card  = 'bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs';
        $btn   = 'px-4 py-2.5 bg-indigo-600 text-white font-semibold text-xs rounded-xl shadow-lg shadow-indigo-600/30 hover:bg-indigo-700 transition-all';
        $err   = 'text-rose-500 text-[11px] mt-1 block';
    @endphp

    <div class="max-w-6xl mx-auto space-y-5">

        {{-- Header --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <div class="flex items-center gap-3 flex-wrap">
                    <h2 class="text-xl font-bold text-slate-800 font-mono">{{ $order->order_number }}</h2>
                    <span class="px-3 py-1 rounded-full text-[11px] font-bold border"
                          style="background-color: {{ $order->status->color }}1a; color: {{ $order->status->color }}; border-color: {{ $order->status->color }}55">{{ $order->status->name }}</span>
                    <span class="px-2.5 py-1 rounded-md text-[10px] font-bold border {{ $paymentStyles[$order->payment_status] ?? $paymentStyles['unpaid'] }}">{{ $order->payment_status_label }}</span>
                </div>
                <p class="text-xs text-slate-500 mt-1">
                    Placed {{ $order->created_at->format('d M Y, h:i A') }}
                    · {{ $order->placed_by === 'admin' ? 'created by an admin' : 'placed by the customer' }}
                </p>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('admin.orders.invoice', $order) }}" target="_blank"
                   class="px-4 py-2.5 border border-slate-200 bg-white text-slate-700 font-semibold text-xs rounded-xl hover:bg-slate-50 flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                    Print invoice
                </a>
                <a href="{{ route('admin.orders.index') }}" class="text-xs font-semibold text-slate-500 hover:text-slate-800">&larr; Back to orders</a>
            </div>
        </div>

        @if ($errors->any())
            <div class="rounded-2xl border border-rose-200 bg-rose-50 p-4">
                <ul class="list-disc pl-5 text-[11px] text-rose-600 space-y-0.5">
                    @foreach ($errors->all() as $message) <li>{{ $message }}</li> @endforeach
                </ul>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 items-start">

            {{-- ======================= LEFT ======================= --}}
            <div class="lg:col-span-2 space-y-4">

                {{-- Items --}}
                <section class="{{ $card }} !p-0 overflow-hidden">
                    <div class="px-5 py-4 border-b border-slate-100">
                        <h3 class="text-sm font-bold text-slate-800">Items ({{ $order->items->sum('quantity') }})</h3>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                            <tr class="bg-slate-50/50 text-[11px] font-bold text-slate-400 uppercase tracking-wider">
                                <th class="py-3 px-5">Product</th>
                                <th class="py-3 px-5 text-right">Price</th>
                                <th class="py-3 px-5 text-right">Qty</th>
                                <th class="py-3 px-5 text-right">Total</th>
                            </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-xs font-medium">
                            @foreach ($order->items as $item)
                                <tr>
                                    <td class="py-3 px-5">
                                        <div class="flex items-center gap-3">
                                            @if ($item->image_url)
                                                <img src="{{ $item->image_url }}" class="w-12 h-12 rounded-xl object-cover border border-slate-200 shrink-0" alt="">
                                            @else
                                                <span class="w-12 h-12 rounded-xl bg-slate-100 border border-slate-200 shrink-0"></span>
                                            @endif
                                            <div class="min-w-0">
                                                <p class="font-bold text-slate-800">{{ $item->name }}</p>
                                                @if ($item->variant_label) <p class="text-[11px] text-slate-500">{{ $item->variant_label }}</p> @endif
                                                @if ($item->sku) <p class="text-[10px] font-mono text-slate-400">SKU: {{ $item->sku }}</p> @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-3 px-5 text-right text-slate-600">{{ $money->formatMoney($item->unit_price) }}</td>
                                    <td class="py-3 px-5 text-right text-slate-600">{{ $item->quantity }}</td>
                                    <td class="py-3 px-5 text-right font-bold text-slate-800">{{ $money->formatMoney($item->line_total) }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>

                    <dl class="px-5 py-4 border-t border-slate-100 bg-slate-50/40 text-xs space-y-2 max-w-sm ml-auto">
                        <div class="flex justify-between"><dt class="text-slate-500">Subtotal</dt><dd class="font-semibold text-slate-800">{{ $money->formatMoney($order->subtotal) }}</dd></div>
                        @if ($order->discount_amount > 0)
                            <div class="flex justify-between">
                                <dt class="text-slate-500">Discount @if ($order->coupon_code) <span class="font-mono text-[10px] px-1.5 py-0.5 rounded bg-indigo-50 text-indigo-700">{{ $order->coupon_code }}</span> @endif</dt>
                                <dd class="font-semibold text-emerald-600">- {{ $money->formatMoney($order->discount_amount) }}</dd>
                            </div>
                        @endif
                        <div class="flex justify-between"><dt class="text-slate-500">Delivery ({{ $order->zone_label }})</dt><dd class="font-semibold text-slate-800">{{ $money->formatMoney($order->shipping_charge) }}</dd></div>
                        @if ($order->payment_fee > 0)
                            <div class="flex justify-between"><dt class="text-slate-500">Payment fee</dt><dd class="font-semibold text-slate-800">{{ $money->formatMoney($order->payment_fee) }}</dd></div>
                        @endif
                        <div class="flex justify-between pt-2 border-t border-slate-200 text-sm"><dt class="font-bold text-slate-800">Total</dt><dd class="font-bold text-slate-800">{{ $money->formatMoney($order->total) }}</dd></div>
                        <div class="flex justify-between"><dt class="text-slate-500">Paid</dt><dd class="font-semibold text-slate-800">{{ $money->formatMoney($order->paid_amount) }}</dd></div>
                        @if ($order->due_amount > 0 && $order->payment_status !== 'refunded')
                            <div class="flex justify-between"><dt class="text-slate-500">Still to pay</dt><dd class="font-bold text-rose-600">{{ $money->formatMoney($order->due_amount) }}</dd></div>
                        @endif
                    </dl>
                </section>

                {{-- Timeline --}}
                <section class="{{ $card }}">
                    <h3 class="text-sm font-bold text-slate-800 mb-4">Timeline</h3>

                    <ol class="relative border-l border-slate-200 ml-2 space-y-5">
                        @foreach ($order->histories as $history)
                            <li class="pl-5 relative">
                                <span class="absolute -left-[5px] top-1.5 w-2.5 h-2.5 rounded-full ring-4 ring-white" style="background-color: {{ $history->status->color }}"></span>
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="text-xs font-bold" style="color: {{ $history->status->color }}">{{ $history->status->name }}</span>
                                    <span class="text-[11px] text-slate-400">{{ $history->created_at->format('d M Y, h:i A') }}</span>
                                    @if ($history->notified) <span class="px-1.5 py-0.5 rounded text-[10px] font-semibold bg-sky-50 text-sky-700">Customer notified</span> @endif
                                </div>
                                @if ($history->note) <p class="text-xs text-slate-600 mt-0.5">{{ $history->note }}</p> @endif
                                <p class="text-[11px] text-slate-400 mt-0.5">by {{ $history->user?->name ?? 'the customer or system' }}</p>
                            </li>
                        @endforeach
                    </ol>
                </section>
            </div>

            {{-- ======================= RIGHT ======================= --}}
            <aside class="space-y-4">

                {{-- Status --}}
                <section class="{{ $card }}">
                    <h3 class="text-sm font-bold text-slate-800 mb-3">Order status</h3>

                    @if ($isFinal)
                        <div class="rounded-xl bg-slate-50 border border-slate-200 p-3 text-[11px] text-slate-600">
                            This order is <span class="font-bold">{{ $order->status->name }}</span>. It is a final status, so it can no longer be changed.
                        </div>
                    @else
                        <form action="{{ route('admin.orders.status', $order) }}" method="POST" class="space-y-3">
                            @csrf
                            @method('PATCH')

                            <div>
                                <label class="{{ $label }}">Move to</label>
                                <select name="order_status_id" class="{{ $input }}">
                                    @foreach ($statuses as $status)
                                        @continue($status->id === $order->order_status_id)
                                        <option value="{{ $status->id }}" @selected(old('order_status_id') == $status->id)>{{ $status->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="{{ $label }}">Note <span class="font-normal text-slate-400">(optional)</span></label>
                                <input type="text" name="note" value="{{ old('note') }}" maxlength="500" placeholder="Shown in the timeline" class="{{ $input }}">
                            </div>
                            <button type="submit" class="{{ $btn }} w-full">Update status</button>
                            <p class="text-[10px] text-slate-400">Cancelled and returned orders put their items back in stock automatically.</p>
                        </form>
                    @endif
                </section>

                {{-- Customer --}}
                <section class="{{ $card }}">
                    <h3 class="text-sm font-bold text-slate-800 mb-3">Customer</h3>
                    <dl class="text-xs space-y-2">
                        <div><dt class="text-slate-400">Name</dt><dd class="font-semibold text-slate-800">{{ $order->customer_name }}</dd></div>
                        <div><dt class="text-slate-400">Phone</dt><dd class="font-semibold text-slate-800">{{ $order->customer_phone }}</dd></div>
                        @if ($order->customer_email) <div><dt class="text-slate-400">Email</dt><dd class="font-semibold text-slate-800 break-all">{{ $order->customer_email }}</dd></div> @endif
                        <div>
                            <dt class="text-slate-400">Account</dt>
                            <dd class="font-semibold text-slate-800">
                                @if ($order->user) <a href="{{ route('admin.users.edit', $order->user) }}" class="text-indigo-600 hover:text-indigo-800">{{ $order->user->name }}</a> @else Guest checkout @endif
                            </dd>
                        </div>
                    </dl>
                    @if ($order->customer_note)
                        <div class="mt-3 rounded-xl bg-amber-50 border border-amber-200 p-3">
                            <p class="text-[10px] font-bold text-amber-700 uppercase tracking-wide">Customer note</p>
                            <p class="text-xs text-amber-900 mt-0.5 whitespace-pre-line">{{ $order->customer_note }}</p>
                        </div>
                    @endif
                </section>

                {{-- Delivery address --}}
                <section class="{{ $card }}">
                    <h3 class="text-sm font-bold text-slate-800 mb-3">Delivery address</h3>
                    <address class="not-italic text-xs space-y-0.5 text-slate-600">
                        <p class="font-semibold text-slate-800">{{ $order->shipping_name }}</p>
                        <p>{{ $order->shipping_phone }}</p>
                        <p class="whitespace-pre-line pt-1">{{ $order->shipping_address }}</p>
                        <p>{{ $order->shipping_area }}, {{ $order->shipping_city }}</p>
                        <p class="pt-1"><span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-slate-100 text-slate-600">{{ $order->zone_label }}</span></p>
                    </address>
                </section>

                {{-- Courier --}}
                <section class="{{ $card }}">
                    <h3 class="text-sm font-bold text-slate-800 mb-3">Courier</h3>

                    <form action="{{ route('admin.orders.shipping', $order) }}" method="POST" class="space-y-3">
                        @csrf
                        @method('PATCH')

                        <div>
                            <label class="{{ $label }}">Courier service</label>
                            <select name="courier_service_id" class="{{ $input }}">
                                <option value="">Not assigned</option>
                                @foreach ($couriers as $courier)
                                    <option value="{{ $courier->id }}" @selected(old('courier_service_id', $order->courier_service_id ?? $couriers->firstWhere('is_default', true)?->id) == $courier->id)>{{ $courier->name }}</option>
                                @endforeach
                                {{-- keep a switched-off courier visible if the order already uses it --}}
                                @if ($order->courier && ! $couriers->contains('id', $order->courier->id))
                                    <option value="{{ $order->courier->id }}" selected>{{ $order->courier->name }} (off)</option>
                                @endif
                            </select>
                        </div>
                        <div>
                            <label class="{{ $label }}">Tracking code</label>
                            <input type="text" name="tracking_code" value="{{ old('tracking_code', $order->tracking_code) }}" maxlength="100" class="{{ $input }} font-mono">
                            @error('tracking_code') <span class="{{ $err }}">{{ $message }}</span> @enderror
                        </div>

                        @if ($order->tracking_url)
                            <a href="{{ $order->tracking_url }}" target="_blank" rel="noopener" class="inline-block text-[11px] font-semibold text-indigo-600 hover:text-indigo-800">Track this parcel &rarr;</a>
                        @endif

                        <button type="submit" class="{{ $btn }} w-full">Save courier</button>
                    </form>
                </section>

                {{-- Payment --}}
                @include('admin.orders._payments', ['order' => $order])
                @include('admin.orders._online_payments', ['order' => $order])
                @include('admin.orders._steadfast', ['order' => $order])

                @include('admin.orders._returns', ['order' => $order])
                {{-- Internal note --}}
                <section class="{{ $card }}">
                    <h3 class="text-sm font-bold text-slate-800 mb-3">Internal note</h3>
                    <form action="{{ route('admin.orders.note', $order) }}" method="POST" class="space-y-3">
                        @csrf
                        @method('PATCH')
                        <textarea name="admin_note" rows="3" maxlength="2000" placeholder="Only your team sees this" class="{{ $input }}">{{ old('admin_note', $order->admin_note) }}</textarea>
                        <button type="submit" class="{{ $btn }} w-full">Save note</button>
                    </form>
                </section>
            </aside>
        </div>
    </div>
@endsection
