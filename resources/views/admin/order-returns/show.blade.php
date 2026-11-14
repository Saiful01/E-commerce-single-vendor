@extends('layouts.admin')

@section('title', 'Return ' . $return->return_number)

@section('content')
    @php
        $money   = \App\Models\SiteSetting::current();
        $order   = $return->order;
        $gateways = \App\Models\PaymentGateway::active()->ordered()->get(['id', 'name']);
        $badge   = ['requested' => 'bg-amber-50 text-amber-700 border-amber-200', 'approved' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
                    'rejected' => 'bg-rose-50 text-rose-700 border-rose-200', 'completed' => 'bg-emerald-50 text-emerald-700 border-emerald-200'];
        $input   = 'w-full text-xs rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500';
        $label   = 'block text-xs font-semibold text-slate-700 mb-1';
        $card    = 'bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs';
        $btn     = 'px-4 py-2.5 bg-indigo-600 text-white font-semibold text-xs rounded-xl shadow-lg shadow-indigo-600/30 hover:bg-indigo-700 transition-all';
    @endphp

    <div class="max-w-5xl mx-auto space-y-5">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <div class="flex items-center gap-3 flex-wrap">
                    <h2 class="text-xl font-bold text-slate-800 font-mono">{{ $return->return_number }}</h2>
                    <span class="px-2.5 py-1 rounded-md text-[11px] font-bold border {{ $badge[$return->status] }}">{{ $return->status_label }}</span>
                </div>
                <p class="text-xs text-slate-500 mt-1">Order <a href="{{ route('admin.orders.show', $order) }}" class="font-mono font-semibold text-indigo-600">{{ $order->order_number }}</a>
                    · {{ $order->customer_name }} · {{ $order->customer_phone }} · {{ $return->created_at->format('d M Y, h:i A') }}</p>
            </div>
            <a href="{{ route('admin.order-returns.index') }}" class="text-xs font-semibold text-slate-500 hover:text-slate-800">&larr; Back to returns</a>
        </div>

        @if($errors->any())
            <div class="rounded-2xl border border-rose-200 bg-rose-50 p-4"><ul class="list-disc pl-5 text-[11px] text-rose-600">@foreach($errors->all() as $m)<li>{{ $m }}</li>@endforeach</ul></div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 items-start">
            <div class="lg:col-span-2 space-y-4">
                <section class="{{ $card }} !p-0 overflow-hidden">
                    <div class="px-5 py-4 border-b border-slate-100"><h3 class="text-sm font-bold text-slate-800">Items coming back</h3></div>
                    <table class="w-full text-left text-xs">
                        <thead><tr class="bg-slate-50/50 text-[11px] font-bold text-slate-400 uppercase tracking-wider"><th class="py-3 px-5">Product</th><th class="py-3 px-5 text-right">Qty</th><th class="py-3 px-5 text-right">Value</th></tr></thead>
                        <tbody class="divide-y divide-slate-100 font-medium">
                        @foreach($return->items as $ri)
                            <tr>
                                <td class="py-3 px-5"><p class="font-bold text-slate-800">{{ $ri->orderItem->name }}</p>@if($ri->orderItem->variant_label)<p class="text-[11px] text-slate-500">{{ $ri->orderItem->variant_label }}</p>@endif</td>
                                <td class="py-3 px-5 text-right">{{ $ri->quantity }} <span class="text-slate-400">of {{ $ri->orderItem->quantity }}</span></td>
                                <td class="py-3 px-5 text-right font-bold text-slate-800">{{ $money->formatMoney($ri->value) }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                    <p class="px-5 py-3 border-t border-slate-100 bg-slate-50/40 text-xs text-slate-500">Value already has the coupon discount share taken off.
                        <span class="float-right font-bold text-slate-800">{{ $money->formatMoney($return->items_value) }}</span></p>
                </section>

                <section class="{{ $card }} space-y-2 text-xs">
                    <h3 class="text-sm font-bold text-slate-800">Why</h3>
                    <p class="font-semibold text-slate-700">{{ $return->reason_label }}</p>
                    @if($return->details)<p class="text-slate-600 whitespace-pre-line">{{ $return->details }}</p>@endif
                    @if($return->admin_note)<p class="rounded-xl bg-slate-50 border border-slate-200 p-3 text-slate-600"><span class="font-semibold">Note:</span> {{ $return->admin_note }}</p>@endif
                    @if($return->status === 'completed')
                        <p class="text-emerald-700 font-semibold">Completed {{ $return->completed_at->format('d M Y') }} · refunded {{ $money->formatMoney($return->refund_amount) }} · {{ $return->restocked ? 'items put back in stock' : 'items not restocked' }}</p>
                    @endif
                </section>
            </div>

            <aside class="space-y-4">
                @if($return->status === 'requested')
                    <section class="{{ $card }} space-y-3">
                        <h3 class="text-sm font-bold text-slate-800">Decide</h3>
                        <form action="{{ route('admin.order-returns.approve', $return) }}" method="POST" class="space-y-2">
                            @csrf @method('PATCH')
                            <input type="text" name="note" maxlength="1000" placeholder="Note for the customer (optional)" class="{{ $input }}">
                            <button class="{{ $btn }} w-full">Approve return</button>
                        </form>
                        <form action="{{ route('admin.order-returns.reject', $return) }}" method="POST" class="space-y-2 border-t border-slate-100 pt-3">
                            @csrf @method('PATCH')
                            <input type="text" name="note" maxlength="1000" required placeholder="Why it is rejected" class="{{ $input }}">
                            <button class="w-full px-4 py-2.5 border border-rose-200 text-rose-600 font-semibold text-xs rounded-xl hover:bg-rose-50">Reject return</button>
                        </form>
                    </section>
                @endif

                @if($return->status === 'approved')
                    <section class="{{ $card }} space-y-3">
                        <h3 class="text-sm font-bold text-slate-800">Goods received. Complete it.</h3>
                        <form action="{{ route('admin.order-returns.complete', $return) }}" method="POST" class="space-y-3" onsubmit="return confirm('Complete this return? Stock and refund cannot be undone.')">
                            @csrf @method('PATCH')
                            <label class="flex items-center gap-2 text-xs font-semibold text-slate-700"><input type="checkbox" name="restock" value="1" checked class="rounded border-slate-300 text-indigo-600"> Put items back in stock</label>
                            <div>
                                <label class="{{ $label }}">Refund amount</label>
                                <input type="number" name="refund_amount" step="0.01" min="0" max="{{ $maxRefund }}" value="{{ old('refund_amount', $suggested) }}" class="{{ $input }}">
                                <p class="text-[10px] text-slate-400 mt-1">Customer paid {{ $money->formatMoney($order->paid_amount) }}. Most you can refund: {{ $money->formatMoney($maxRefund) }}.</p>
                            </div>
                            <div>
                                <label class="{{ $label }}">Refunded by</label>
                                <select name="payment_gateway_id" class="{{ $input }}"><option value="">Not specified</option>
                                    @foreach($gateways as $g)<option value="{{ $g->id }}" @selected($order->payment_gateway_id == $g->id)>{{ $g->name }}</option>@endforeach</select>
                            </div>
                            <input type="text" name="transaction_id" maxlength="100" placeholder="Refund transaction ID" class="{{ $input }} font-mono">
                            <input type="text" name="note" maxlength="1000" placeholder="Note (optional)" class="{{ $input }}">
                            <button class="{{ $btn }} w-full">Complete return</button>
                        </form>
                        <form action="{{ route('admin.order-returns.reject', $return) }}" method="POST" class="space-y-2 border-t border-slate-100 pt-3">
                            @csrf @method('PATCH')
                            <input type="text" name="note" maxlength="1000" required placeholder="Reject with a reason" class="{{ $input }}">
                            <button class="w-full px-4 py-2 border border-rose-200 text-rose-600 font-semibold text-xs rounded-xl hover:bg-rose-50">Reject return</button>
                        </form>
                    </section>
                @endif

                <section class="{{ $card }} text-xs space-y-1.5">
                    <h3 class="text-sm font-bold text-slate-800 mb-2">Order money</h3>
                    <div class="flex justify-between"><span class="text-slate-500">Order total</span><span class="font-semibold">{{ $money->formatMoney($order->total) }}</span></div>
                    <div class="flex justify-between"><span class="text-slate-500">Paid so far</span><span class="font-semibold">{{ $money->formatMoney($order->paid_amount) }}</span></div>
                    <div class="flex justify-between"><span class="text-slate-500">Returned before</span><span class="font-semibold">{{ $money->formatMoney($order->returned_total) }}</span></div>
                </section>
            </aside>
        </div>
    </div>
@endsection
