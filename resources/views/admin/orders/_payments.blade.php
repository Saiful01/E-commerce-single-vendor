{{-- Needs: $order. Replaces the old "Payment" section of admin/orders/show.blade.php --}}
@php
    $money    = \App\Models\SiteSetting::current();
    $gateways = \App\Models\PaymentGateway::active()->ordered()->get(['id', 'name']);
    $payments = $order->payments()->with(['gateway:id,name'])->get();
    $due      = $order->due_amount;
    $closed   = $order->status->restock_items;
    $inputCls = 'w-full text-xs rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500';
    $labelCls = 'block text-xs font-semibold text-slate-700 mb-1';
    $styles   = ['unpaid' => 'bg-rose-50 text-rose-700 border-rose-200', 'partial' => 'bg-amber-50 text-amber-700 border-amber-200',
                 'paid' => 'bg-emerald-50 text-emerald-700 border-emerald-200', 'refunded' => 'bg-slate-100 text-slate-600 border-slate-200'];
@endphp

<section class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs space-y-4" x-data="{ amount: {{ $due }} }">
    <div class="flex items-start justify-between gap-3">
        <div>
            <h3 class="text-sm font-bold text-slate-800">Payment</h3>
            <p class="text-[11px] text-slate-500 mt-0.5">Method chosen: <span class="font-semibold text-slate-700">{{ $order->paymentGateway?->name ?? 'Not set' }}</span></p>
        </div>
        <span class="px-2.5 py-1 rounded-md text-[10px] font-bold border {{ $styles[$order->payment_status] ?? $styles['unpaid'] }}">{{ $order->payment_status_label }}</span>
    </div>

    {{-- Numbers --}}
    <dl class="grid grid-cols-3 gap-2 text-center">
        <div class="rounded-xl bg-slate-50 p-2.5"><dt class="text-[10px] text-slate-400 uppercase tracking-wide">To pay</dt><dd class="text-xs font-bold text-slate-800 mt-0.5">{{ $money->formatMoney($order->payable_amount) }}</dd></div>
        <div class="rounded-xl bg-slate-50 p-2.5"><dt class="text-[10px] text-slate-400 uppercase tracking-wide">Paid</dt><dd class="text-xs font-bold text-emerald-600 mt-0.5">{{ $money->formatMoney($order->paid_amount) }}</dd></div>
        <div class="rounded-xl bg-slate-50 p-2.5"><dt class="text-[10px] text-slate-400 uppercase tracking-wide">Still due</dt><dd class="text-xs font-bold {{ $due > 0 ? 'text-rose-600' : 'text-slate-800' }} mt-0.5">{{ $money->formatMoney($due) }}</dd></div>
    </dl>
    @if($order->returned_total > 0)
        <p class="text-[11px] text-slate-500">Goods worth {{ $money->formatMoney($order->returned_total) }} were returned and are already taken off the amount to pay.</p>
    @endif

    {{-- Add a payment --}}
    @if($closed)
        <p class="rounded-xl bg-slate-50 border border-slate-200 p-3 text-[11px] text-slate-600">This order is {{ $order->status->name }}, so new payments are switched off.</p>
    @elseif($due <= 0)
        <p class="rounded-xl bg-emerald-50 border border-emerald-200 p-3 text-[11px] text-emerald-800">Nothing is due on this order.</p>
    @else
        <form action="{{ route('admin.orders.payments.store', $order) }}" method="POST" class="space-y-3 border-t border-slate-100 pt-4">
            @csrf
            <p class="text-xs font-bold text-slate-800">Add a payment</p>
            <div>
                <label class="{{ $labelCls }}">Amount received</label>
                <input type="number" name="amount" step="0.01" min="0.01" max="{{ $due }}" x-model="amount" required class="{{ $inputCls }}">
                <div class="flex gap-2 mt-2 text-[11px]">
                    <button type="button" @click="amount = {{ $due }}" class="px-2.5 py-1 rounded-lg border border-slate-200 text-slate-600 hover:border-indigo-300 hover:text-indigo-600">Full due {{ $money->formatMoney($due) }}</button>
                    <button type="button" @click="amount = {{ round($due / 2, 2) }}" class="px-2.5 py-1 rounded-lg border border-slate-200 text-slate-600 hover:border-indigo-300 hover:text-indigo-600">Half</button>
                </div>
                @if($payments->where('status', 'confirmed')->where('type', 'payment')->isNotEmpty())
                    <p class="text-[10px] text-slate-400 mt-1.5">Already paid in part, so the next payment suggests what is left.</p>
                @endif
                @error('amount') <span class="text-rose-500 text-[11px] mt-1 block">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="{{ $labelCls }}">Received by</label>
                <select name="payment_gateway_id" class="{{ $inputCls }}">
                    <option value="">Not specified</option>
                    @foreach($gateways as $g)<option value="{{ $g->id }}" @selected($order->payment_gateway_id == $g->id)>{{ $g->name }}</option>@endforeach
                </select>
            </div>
            <div>
                <label class="{{ $labelCls }}">Transaction ID</label>
                <input type="text" name="transaction_id" value="{{ old('transaction_id', $payments->isEmpty() ? $order->transaction_id : '') }}" maxlength="100" placeholder="bKash / bank reference" class="{{ $inputCls }} font-mono">
            </div>
            <div>
                <label class="{{ $labelCls }}">Note <span class="font-normal text-slate-400">(optional)</span></label>
                <input type="text" name="note" maxlength="500" class="{{ $inputCls }}">
            </div>
            <button class="w-full px-4 py-2.5 bg-indigo-600 text-white font-semibold text-xs rounded-xl shadow-lg shadow-indigo-600/30 hover:bg-indigo-700 transition-all">Save payment</button>
        </form>
    @endif

    {{-- Payment history --}}
    @if($payments->isNotEmpty())
        <div class="border-t border-slate-100 pt-4 space-y-2.5">
            <p class="text-xs font-bold text-slate-800">History</p>
            @foreach($payments as $p)
                <div class="rounded-xl border p-3 {{ $p->status === 'pending' ? 'border-amber-300 bg-amber-50/50' : 'border-slate-200' }}">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <p class="text-xs font-bold {{ $p->is_refund ? 'text-rose-600' : 'text-slate-800' }}">{{ $p->is_refund ? '-' : '+' }} {{ $money->formatMoney($p->amount) }}
                                <span class="font-semibold text-slate-400">{{ \App\Models\OrderPayment::TYPES[$p->type] }}</span></p>
                            <p class="text-[11px] text-slate-500">{{ $p->gateway?->name ?? 'Method not set' }} @if($p->transaction_id)· <span class="font-mono">{{ $p->transaction_id }}</span>@endif</p>
                            <p class="text-[10px] text-slate-400">{{ $p->created_at->format('d M Y, h:i A') }} · {{ $p->source === 'customer' ? 'told by the customer' : 'entered by ' . ($p->user?->name ?? 'admin') }}</p>
                            @if($p->note)<p class="text-[11px] text-slate-500 mt-0.5">{{ $p->note }}</p>@endif
                        </div>
                        <span class="shrink-0 px-2 py-0.5 rounded text-[10px] font-bold
                            {{ ['pending' => 'bg-amber-100 text-amber-800', 'confirmed' => 'bg-emerald-50 text-emerald-700', 'rejected' => 'bg-slate-100 text-slate-500'][$p->status] }}">
                            {{ \App\Models\OrderPayment::STATUSES[$p->status] }}</span>
                    </div>

                    @if($p->status === 'pending')
                        <div class="mt-3 flex flex-wrap items-end gap-2">
                            <form action="{{ route('admin.order-payments.confirm', $p) }}" method="POST" class="flex items-end gap-2">
                                @csrf @method('PATCH')
                                <input type="number" name="amount" step="0.01" min="0.01" value="{{ $p->amount }}" class="w-28 text-xs rounded-lg border-slate-200" aria-label="Amount received">
                                <button class="px-3 py-2 bg-emerald-600 text-white font-semibold text-[11px] rounded-lg hover:bg-emerald-700">Confirm</button>
                            </form>
                            <form action="{{ route('admin.order-payments.reject', $p) }}" method="POST" onsubmit="return confirm('Reject this payment?')">
                                @csrf @method('PATCH')
                                <button class="px-3 py-2 border border-slate-200 text-slate-600 font-semibold text-[11px] rounded-lg hover:bg-slate-50">Reject</button>
                            </form>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</section>
