@extends('layouts.admin')

@section('title', 'Returns')

@section('content')
    @php
        $money = \App\Models\SiteSetting::current();
        $badge = ['requested' => 'bg-amber-50 text-amber-700 border-amber-200', 'approved' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
                  'rejected' => 'bg-rose-50 text-rose-700 border-rose-200', 'completed' => 'bg-emerald-50 text-emerald-700 border-emerald-200'];
        $tabBase = 'px-3.5 py-2 text-xs font-semibold rounded-xl border whitespace-nowrap transition-colors flex items-center gap-2';
        $tabOn   = 'bg-indigo-600 text-white border-indigo-600 shadow-sm';
        $tabOff  = 'bg-white text-slate-600 border-slate-200 hover:border-indigo-300 hover:text-indigo-600';
    @endphp

    <div class="space-y-5">
        <div>
            <h2 class="text-xl font-bold text-slate-800">Returns</h2>
            <p class="text-xs text-slate-500 mt-1">Return requests from customers and returns you started by hand.</p>
        </div>

        <nav class="flex gap-2 overflow-x-auto pb-1" aria-label="Return status">
            <a href="{{ route('admin.order-returns.index', array_filter(['q' => $q])) }}" class="{{ $tabBase }} {{ $status ? $tabOff : $tabOn }}">All <span class="opacity-70">{{ $counts->sum() }}</span></a>
            @foreach(\App\Models\OrderReturn::STATUSES as $key => $label)
                <a href="{{ route('admin.order-returns.index', array_filter(['q' => $q, 'status' => $key])) }}" class="{{ $tabBase }} {{ $status === $key ? $tabOn : $tabOff }}">{{ $label }} <span class="opacity-70">{{ $counts[$key] ?? 0 }}</span></a>
            @endforeach
        </nav>

        <form method="GET" action="{{ route('admin.order-returns.index') }}" class="bg-white p-3 rounded-2xl border border-slate-200/80 shadow-xs">
            @if($status)<input type="hidden" name="status" value="{{ $status }}">@endif
            <input type="search" name="q" value="{{ $q }}" placeholder="Return number, order number, name or phone" class="w-full text-xs rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500">
        </form>

        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                    <tr class="bg-slate-50/50 border-b border-slate-100 text-[11px] font-bold text-slate-400 uppercase tracking-wider">
                        <th class="py-3.5 px-6">Return</th><th class="py-3.5 px-6">Order</th><th class="py-3.5 px-6">Customer</th>
                        <th class="py-3.5 px-6">Value</th><th class="py-3.5 px-6">Status</th><th class="py-3.5 px-6 text-right">Actions</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-xs font-medium">
                    @forelse($returns as $r)
                        <tr class="hover:bg-slate-50/50">
                            <td class="py-3.5 px-6"><a href="{{ route('admin.order-returns.show', $r) }}" class="font-mono text-[13px] font-bold text-slate-800 hover:text-indigo-600">{{ $r->return_number }}</a>
                                <p class="text-[11px] text-slate-400">{{ $r->created_at->format('d M Y, h:i A') }} · {{ $r->source === 'admin' ? 'by admin' : 'by customer' }}</p></td>
                            <td class="py-3.5 px-6"><a href="{{ route('admin.orders.show', $r->order) }}" class="font-mono text-slate-600 hover:text-indigo-600">{{ $r->order->order_number }}</a></td>
                            <td class="py-3.5 px-6"><p class="font-semibold text-slate-800">{{ $r->order->customer_name }}</p><p class="text-[11px] text-slate-400">{{ $r->order->customer_phone }}</p></td>
                            <td class="py-3.5 px-6"><p class="font-bold text-slate-800">{{ $money->formatMoney($r->items_value) }}</p><p class="text-[11px] text-slate-400">{{ $r->items_count }} {{ Str::plural('item', $r->items_count) }}</p></td>
                            <td class="py-3.5 px-6"><span class="px-2.5 py-1 rounded-md text-[10px] font-bold border {{ $badge[$r->status] }}">{{ $r->status_label }}</span></td>
                            <td class="py-3.5 px-6 text-right"><a href="{{ route('admin.order-returns.show', $r) }}" class="p-1.5 text-slate-500 hover:text-indigo-600 hover:bg-slate-100 rounded-lg">Open</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center py-14 text-slate-400">No returns found.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-4 border-t border-slate-100">{{ $returns->links() }}</div>
        </div>
    </div>
@endsection
