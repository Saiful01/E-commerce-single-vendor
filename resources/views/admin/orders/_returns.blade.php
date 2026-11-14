{{-- Needs: $order --}}
@php
    $returns = $order->returns()->withCount('items')->get();
    $check   = app(\App\Services\ReturnService::class)->eligibility($order, admin: true);
    $money   = \App\Models\SiteSetting::current();
    $badge   = ['requested' => 'bg-amber-50 text-amber-700 border-amber-200', 'approved' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
                'rejected' => 'bg-rose-50 text-rose-700 border-rose-200', 'completed' => 'bg-emerald-50 text-emerald-700 border-emerald-200'];
@endphp

<section class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs space-y-3">
    <div class="flex items-center justify-between gap-3">
        <h3 class="text-sm font-bold text-slate-800">Returns</h3>
        @if($order->return_state)<span class="px-2.5 py-1 rounded-md text-[10px] font-bold bg-slate-100 text-slate-700">{{ ucfirst($order->return_state) }} returned</span>@endif
    </div>

    @forelse($returns as $r)
        <a href="{{ route('admin.order-returns.show', $r) }}" class="block rounded-xl border border-slate-200 p-3 hover:border-indigo-300">
            <div class="flex items-center justify-between gap-2">
                <span class="text-xs font-mono font-bold text-slate-800">{{ $r->return_number }}</span>
                <span class="px-2 py-0.5 rounded-md text-[10px] font-bold border {{ $badge[$r->status] }}">{{ $r->status_label }}</span>
            </div>
            <p class="text-[11px] text-slate-500 mt-1">{{ $r->items_count }} {{ Str::plural('item', $r->items_count) }} · {{ $money->formatMoney($r->items_value) }} · {{ $r->reason_label }}</p>
        </a>
    @empty
        <p class="text-[11px] text-slate-400">No returns on this order.</p>
    @endforelse

    @if($check['ok'])
        <a href="{{ route('admin.orders.returns.create', $order) }}" class="block text-center px-4 py-2.5 border border-slate-200 text-slate-700 font-semibold text-xs rounded-xl hover:bg-slate-50">Start a return</a>
    @else
        <p class="text-[10px] text-slate-400">{{ $check['reason'] }}</p>
    @endif
</section>
