{{-- Needs: $order. Optional: shows every time the customer was sent to SSLCommerz. Put it under the payment panel in admin/orders/show.blade.php --}}
@php
    $attempts = \App\Models\OnlinePayment::where('order_id', $order->id)->latest('id')->get();
    $money    = \App\Models\SiteSetting::current();
    $tone     = ['initiated' => 'bg-slate-100 text-slate-600', 'paid' => 'bg-emerald-50 text-emerald-700', 'failed' => 'bg-rose-50 text-rose-700',
                 'cancelled' => 'bg-slate-100 text-slate-500', 'review' => 'bg-amber-100 text-amber-800'];
@endphp

@if($attempts->isNotEmpty())
    <section class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs space-y-3">
        <h3 class="text-sm font-bold text-slate-800">Online payment attempts</h3>
        @foreach($attempts as $a)
            <div class="rounded-xl border border-slate-200 p-3 text-xs">
                <div class="flex items-center justify-between gap-2">
                    <span class="font-mono font-semibold text-slate-700 truncate">{{ $a->tran_id }}</span>
                    <span class="shrink-0 px-2 py-0.5 rounded text-[10px] font-bold {{ $tone[$a->status] ?? $tone['initiated'] }}">{{ \App\Models\OnlinePayment::STATUSES[$a->status] ?? $a->status }}</span>
                </div>
                <p class="text-slate-500 mt-1">{{ $money->formatMoney($a->amount) }} · {{ $a->created_at->format('d M Y, h:i A') }}@if($a->card_type) · {{ $a->card_type }}@endif</p>
                @if($a->bank_tran_id)<p class="text-[11px] text-slate-400 font-mono">Bank ref {{ $a->bank_tran_id }}</p>@endif
                @if($a->status === 'review')<p class="text-[11px] text-amber-700 mt-1">Check this one: see the internal note and the payment list above.</p>@endif
            </div>
        @endforeach
    </section>
@endif
