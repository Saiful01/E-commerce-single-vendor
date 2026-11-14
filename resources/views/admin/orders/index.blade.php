@extends('layouts.admin')

@section('title', 'Orders')

@section('content')
    @php
        $money    = \App\Models\SiteSetting::current();
        $gateways = \App\Models\PaymentGateway::active()->ordered()->get(['id', 'name']);
        $active   = $statuses->where('is_active', true);

        $paymentStyles = [
            'unpaid'   => 'bg-rose-50 text-rose-700 border-rose-200',
            'partial'  => 'bg-amber-50 text-amber-700 border-amber-200',
            'paid'     => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            'refunded' => 'bg-slate-100 text-slate-600 border-slate-200',
        ];

        $tabBase = 'px-3.5 py-2 text-xs font-semibold rounded-xl border whitespace-nowrap transition-colors flex items-center gap-2';
        $tabOn   = 'bg-indigo-600 text-white border-indigo-600 shadow-sm';
        $tabOff  = 'bg-white text-slate-600 border-slate-200 hover:border-indigo-300 hover:text-indigo-600';
        $inputCls = 'w-full text-xs rounded-lg border-slate-200 focus:border-indigo-500 focus:ring-indigo-500';

        $keep = array_filter([
            'q' => $filters['q'], 'payment' => $filters['payment'], 'courier' => $filters['courierId'],
            'from' => $filters['from'], 'to' => $filters['to'],
        ]);
    @endphp

    <div class="space-y-5" x-data="{ pay: null, picked: [] }" @keydown.escape.window="pay = null">

        {{-- Header --}}
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-bold text-slate-800">Orders</h2>
                <p class="text-xs text-slate-500 mt-1">{{ $total }} {{ Str::plural('order', $total) }} in total</p>
            </div>
            <a href="{{ route('admin.orders.create') }}"
               class="px-4 py-2.5 bg-indigo-600 text-white font-semibold text-xs rounded-xl shadow-lg shadow-indigo-600/30 hover:bg-indigo-700 transition-all flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                New order
            </a>
        </div>

        {{-- Today --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs">
                <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Orders today</p>
                <p class="text-2xl font-bold text-slate-800 mt-1">{{ $stats['today'] }}</p>
            </div>
            <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs">
                <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Sales today</p>
                <p class="text-2xl font-bold text-slate-800 mt-1">{{ $money->formatMoney($stats['revenue']) }}</p>
            </div>
            <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs">
                <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Waiting for action</p>
                <p class="text-2xl font-bold text-amber-600 mt-1">{{ $stats['waiting'] }}</p>
            </div>
        </div>

        {{-- Status tabs --}}
        <nav class="flex gap-2 overflow-x-auto pb-1" aria-label="Order status">
            <a href="{{ route('admin.orders.index', $keep) }}" class="{{ $tabBase }} {{ $filters['statusId'] ? $tabOff : $tabOn }}">
                All <span class="opacity-70">{{ $total }}</span>
            </a>
            @foreach ($statuses as $status)
                <a href="{{ route('admin.orders.index', $keep + ['status' => $status->id]) }}"
                   class="{{ $tabBase }} {{ (string) $filters['statusId'] === (string) $status->id ? $tabOn : $tabOff }}">
                    <span class="w-2 h-2 rounded-full" style="background-color: {{ $status->color }}"></span>
                    {{ $status->name }} <span class="opacity-70">{{ $counts[$status->id] ?? 0 }}</span>
                </a>
            @endforeach
        </nav>

        {{-- Filters --}}
        <form method="GET" action="{{ route('admin.orders.index') }}" x-data x-ref="filter"
              class="bg-white p-3 rounded-2xl border border-slate-200/80 shadow-xs grid grid-cols-1 md:grid-cols-12 gap-2">
            @if ($filters['statusId']) <input type="hidden" name="status" value="{{ $filters['statusId'] }}"> @endif

            <div class="relative md:col-span-4">
                <svg class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 10a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input type="search" name="q" value="{{ $filters['q'] }}" placeholder="Order number, name, phone or tracking code"
                       @input.debounce.500ms="$refs.filter.submit()"
                       @if ($filters['q'] !== '') x-init="$el.focus(); $el.setSelectionRange($el.value.length, $el.value.length)" @endif
                       class="w-full pl-9 text-xs rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500">
            </div>

            <select name="payment" @change="$refs.filter.submit()" class="md:col-span-2 text-xs rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">Any payment</option>
                @foreach (\App\Models\Order::PAYMENT_STATUSES as $key => $name)
                    <option value="{{ $key }}" @selected($filters['payment'] === $key)>{{ $name }}</option>
                @endforeach
            </select>

            <select name="courier" @change="$refs.filter.submit()" class="md:col-span-2 text-xs rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">Any courier</option>
                @foreach ($couriers as $courier)
                    <option value="{{ $courier->id }}" @selected((string) $filters['courierId'] === (string) $courier->id)>{{ $courier->name }}</option>
                @endforeach
            </select>

            <input type="date" name="from" value="{{ $filters['from'] }}" @change="$refs.filter.submit()" aria-label="From date"
                   class="md:col-span-2 text-xs rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500">
            <input type="date" name="to" value="{{ $filters['to'] }}" @change="$refs.filter.submit()" aria-label="To date"
                   class="md:col-span-2 text-xs rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500">
        </form>

        @if ($filtering)
            <p class="text-[11px] text-slate-500 -mt-2">
                {{ $orders->total() }} {{ Str::plural('order', $orders->total()) }} match.
                <a href="{{ route('admin.orders.index') }}" class="font-semibold text-indigo-600 hover:text-indigo-800">Clear filters</a>
            </p>
        @endif

        {{-- Send many orders to Steadfast --}}
        @php $steadfastOn = (bool) \App\Services\Couriers\SteadfastService::activeCourier(); @endphp
        @if ($steadfastOn)
            <form id="bulkForm" action="{{ route('admin.orders.steadfast.bulk') }}" method="POST" x-show="picked.length" x-cloak
                  onsubmit="return confirm('Send the selected orders to Steadfast?')"
                  class="flex items-center justify-between gap-3 rounded-2xl border border-indigo-200 bg-indigo-50 px-4 py-3 text-xs">
                @csrf
                <span class="font-semibold text-indigo-900"><span x-text="picked.length"></span> selected</span>
                <div class="flex items-center gap-3">
                    <button type="button" @click="picked = []" class="font-semibold text-indigo-700 hover:text-indigo-900">Clear</button>
                    <button class="px-4 py-2 bg-indigo-600 text-white font-semibold rounded-xl hover:bg-indigo-700">Send to Steadfast</button>
                </div>
            </form>
        @endif

        {{-- Table --}}
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                    <tr class="bg-slate-50/50 border-b border-slate-100 text-[11px] font-bold text-slate-400 uppercase tracking-wider">
                        <th class="py-3.5 pl-6 pr-0 w-8"></th>
                        <th class="py-3.5 px-6">Order</th>
                        <th class="py-3.5 px-6">Customer</th>
                        <th class="py-3.5 px-6">Total</th>
                        <th class="py-3.5 px-6">Payment</th>
                        <th class="py-3.5 px-6">Status</th>
                        <th class="py-3.5 px-6">Courier</th>
                        <th class="py-3.5 px-6 text-right">Actions</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-xs font-medium">
                    @forelse ($orders as $order)
                        @php
                            $c   = $order->status->color;
                            $due = $order->due_amount;
                            $canPay = $due > 0 && ! $order->status->restock_items;
                        @endphp
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="py-3.5 pl-6 pr-0">
                                @if ($steadfastOn && ! $order->courier_consignment_id && ! $order->status->restock_items && ! $order->status->is_final)
                                    <input type="checkbox" form="bulkForm" name="order_ids[]" value="{{ $order->id }}" x-model="picked" class="rounded border-slate-300 text-indigo-600" aria-label="Select {{ $order->order_number }}">
                                @endif
                            </td>
                            <td class="py-3.5 px-6">
                                <a href="{{ route('admin.orders.show', $order) }}" class="font-mono text-[13px] font-bold text-slate-800 hover:text-indigo-600 transition-colors">{{ $order->order_number }}</a>
                                <p class="text-[11px] text-slate-400 mt-0.5">{{ $order->created_at->format('d M Y, h:i A') }}</p>
                            </td>

                            <td class="py-3.5 px-6">
                                <p class="font-semibold text-slate-800">{{ $order->customer_name }}</p>
                                <p class="text-[11px] text-slate-400">{{ $order->customer_phone }}</p>
                            </td>

                            <td class="py-3.5 px-6">
                                <p class="font-bold text-slate-800">{{ $money->formatMoney($order->total) }}</p>
                                <p class="text-[11px] text-slate-400">{{ $order->items_count }} {{ Str::plural('item', $order->items_count) }}</p>
                                @if ($order->returned_total > 0)<p class="text-[11px] font-semibold text-rose-500">Returned {{ $money->formatMoney($order->returned_total) }}</p>@endif
                            </td>

                            {{-- Payment: status + add a payment --}}
                            <td class="py-3.5 px-6">
                                <span class="px-2.5 py-1 rounded-md text-[10px] font-bold border {{ $paymentStyles[$order->payment_status] ?? $paymentStyles['unpaid'] }}">{{ $order->payment_status_label }}</span>
                                <p class="text-[11px] text-slate-400 mt-1">{{ $order->paymentGateway?->name ?? 'Not set' }}</p>
                                @if ($order->paid_amount > 0 && $due > 0)<p class="text-[11px] text-slate-500">Paid {{ $money->formatMoney($order->paid_amount) }}</p>@endif
                                @if ($canPay)
                                    <button type="button"
                                            @click='pay = @js(["url" => route("admin.orders.payments.store", $order), "number" => $order->order_number, "due" => $due, "fmt" => $money->formatMoney($due), "gateway" => $order->payment_gateway_id])'
                                            class="mt-1 text-[11px] font-semibold text-indigo-600 hover:text-indigo-800">Add payment · due {{ $money->formatMoney($due) }}</button>
                                @endif
                            </td>

                            {{-- Status: change right here --}}
                            <td class="py-3.5 px-6">
                                @if ($order->status->is_final)
                                    <span class="inline-block px-3 py-1 rounded-full text-[11px] font-bold border"
                                          style="background-color: {{ $c }}1a; color: {{ $c }}; border-color: {{ $c }}55">{{ $order->status->name }}</span>
                                @else
                                    <form action="{{ route('admin.orders.status', $order) }}" method="POST"
                                          x-data="{ was: '{{ $order->order_status_id }}',
                                                    go(e) { const o = e.target.selectedOptions[0];
                                                            if (o.dataset.final === '1' && ! confirm('Move to ' + o.text + '? This is a final status and cannot be changed later.')) { e.target.value = this.was; return }
                                                            e.target.form.submit() } }">
                                        @csrf @method('PATCH')
                                        <select name="order_status_id" @change="go($event)" aria-label="Order status"
                                                style="background-color: {{ $c }}1a; color: {{ $c }}; border-color: {{ $c }}55"
                                                class="rounded-full text-[11px] font-bold border py-1 pl-3 pr-8 focus:ring-0 cursor-pointer">
                                            <option value="{{ $order->order_status_id }}" selected>{{ $order->status->name }}</option>
                                            @foreach ($active as $s)
                                                @continue($s->id === $order->order_status_id)
                                                <option value="{{ $s->id }}" data-final="{{ (int) $s->is_final }}">{{ $s->name }}</option>
                                            @endforeach
                                        </select>
                                    </form>
                                @endif
                            </td>

                            <td class="py-3.5 px-6 text-slate-600">
                                @if ($order->courier)
                                    <p>{{ $order->courier->name }}</p>
                                    @if ($order->tracking_code)
                                        @if ($order->tracking_url)
                                            <a href="{{ $order->tracking_url }}" target="_blank" rel="noopener" class="text-[11px] font-mono text-indigo-600 hover:text-indigo-800">{{ $order->tracking_code }}</a>
                                        @else
                                            <p class="text-[11px] font-mono text-slate-400">{{ $order->tracking_code }}</p>
                                        @endif
                                    @endif
                                @else
                                    <span class="text-slate-400">Not assigned</span>
                                @endif
                                @if ($order->courier_status)<p class="mt-0.5 text-[11px] font-semibold text-indigo-600">{{ $order->courier_status_label }}</p>@endif
                            </td>

                            <td class="py-3.5 px-6 text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <a href="{{ route('admin.orders.show', $order) }}" class="p-1.5 text-slate-500 hover:text-indigo-600 hover:bg-slate-100 rounded-lg transition-colors">View</a>
                                    <a href="{{ route('admin.orders.invoice', $order) }}" target="_blank" class="p-1.5 text-slate-500 hover:text-indigo-600 hover:bg-slate-100 rounded-lg transition-colors">Invoice</a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-14 text-slate-400">
                                @if ($filtering)
                                    No orders match your filters. <a href="{{ route('admin.orders.index') }}" class="text-indigo-600 font-semibold">Clear filters</a>
                                @else
                                    No orders yet. <a href="{{ route('admin.orders.create') }}" class="text-indigo-600 font-semibold">Create one by hand</a>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-4 border-t border-slate-100">
                {{ $orders->links() }}
            </div>
        </div>

        {{-- Add-payment popup (one for the whole page) --}}
        <div x-show="pay" x-cloak class="fixed inset-0 z-50 grid place-items-center p-4" role="dialog" aria-modal="true">
            <div class="absolute inset-0 bg-slate-900/40" @click="pay = null"></div>
            <template x-if="pay">
                <form :action="pay.url" method="POST" x-data="{ amount: pay.due }" class="relative w-full max-w-sm bg-white rounded-2xl border border-slate-200 shadow-xl p-5 space-y-3">
                    @csrf
                    <div class="flex items-start justify-between">
                        <div>
                            <h3 class="text-sm font-bold text-slate-800">Add payment</h3>
                            <p class="text-[11px] text-slate-500 font-mono" x-text="pay.number"></p>
                        </div>
                        <button type="button" @click="pay = null" class="text-slate-400 hover:text-slate-700" aria-label="Close">&times;</button>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Amount received</label>
                        <input type="number" name="amount" step="0.01" min="0.01" :max="pay.due" x-model="amount" required class="{{ $inputCls }}">
                        <div class="flex gap-2 mt-2 text-[11px]">
                            <button type="button" @click="amount = pay.due" class="px-2.5 py-1 rounded-lg border border-slate-200 text-slate-600 hover:border-indigo-300 hover:text-indigo-600" x-text="'Full due ' + pay.fmt"></button>
                            <button type="button" @click="amount = Math.round(pay.due * 50) / 100" class="px-2.5 py-1 rounded-lg border border-slate-200 text-slate-600 hover:border-indigo-300 hover:text-indigo-600">Half</button>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Received by</label>
                        <select name="payment_gateway_id" class="{{ $inputCls }}">
                            <option value="">Not specified</option>
                            @foreach ($gateways as $g)<option value="{{ $g->id }}" :selected="String(pay.gateway) === '{{ $g->id }}'">{{ $g->name }}</option>@endforeach
                        </select>
                    </div>
                    <input type="text" name="transaction_id" maxlength="100" placeholder="Transaction ID (optional)" class="{{ $inputCls }} font-mono">
                    <button class="w-full px-4 py-2.5 bg-indigo-600 text-white font-semibold text-xs rounded-xl shadow-lg shadow-indigo-600/30 hover:bg-indigo-700">Save payment</button>
                </form>
            </template>
        </div>
    </div>
@endsection
