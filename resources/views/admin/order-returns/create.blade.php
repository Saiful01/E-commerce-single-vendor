@extends('layouts.admin')

@section('title', 'New return')

@section('content')
    @php
        $input = 'w-full text-xs rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500';
        $label = 'block text-xs font-semibold text-slate-700 mb-1';
        $money = \App\Models\SiteSetting::current();
    @endphp

    <div class="max-w-3xl mx-auto space-y-5">
        <div class="flex items-center justify-between">
            <div><h2 class="text-xl font-bold text-slate-800">Start a return</h2>
                <p class="text-xs text-slate-500 mt-1">Order <span class="font-mono font-semibold">{{ $order->order_number }}</span> · {{ $order->customer_name }}</p></div>
            <a href="{{ route('admin.orders.show', $order) }}" class="text-xs font-semibold text-slate-500 hover:text-slate-800">&larr; Back to order</a>
        </div>

        @if($errors->any())
            <div class="rounded-2xl border border-rose-200 bg-rose-50 p-4"><ul class="list-disc pl-5 text-[11px] text-rose-600">@foreach($errors->all() as $m)<li>{{ $m }}</li>@endforeach</ul></div>
        @endif

        <form action="{{ route('admin.orders.returns.store', $order) }}" method="POST" class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs space-y-5">
            @csrf
            <div class="space-y-2.5">
                <p class="text-sm font-bold text-slate-800">Which items come back?</p>
                @foreach($order->items as $item)
                    @php $left = $remaining[$item->id] ?? 0; @endphp
                    <div class="flex items-center gap-3 rounded-xl border border-slate-200 p-3 {{ $left < 1 ? 'opacity-50' : '' }}">
                        <img src="{{ $item->image_url ?: asset('images/placeholder.png') }}" class="w-12 h-12 rounded-lg object-cover border border-slate-200" alt="">
                        <div class="min-w-0 flex-1">
                            <p class="text-xs font-bold text-slate-800">{{ $item->name }}</p>
                            <p class="text-[11px] text-slate-500">{{ $item->variant_label }} {{ $money->formatMoney($item->unit_price) }} each · bought {{ $item->quantity }}@if($item->returned_quantity), already returned {{ $item->returned_quantity }}@endif</p>
                        </div>
                        <div class="text-right">
                            <input type="number" name="qty[{{ $item->id }}]" min="0" max="{{ $left }}" value="{{ old('qty.' . $item->id, 0) }}" @disabled($left < 1) class="w-20 text-xs rounded-lg border-slate-200 text-right" aria-label="Quantity to return">
                            <p class="text-[10px] text-slate-400 mt-0.5">max {{ $left }}</p>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="{{ $label }}">Reason *</label>
                    <select name="reason" class="{{ $input }}">
                        @foreach(config('shop.return_reasons') as $k => $text)<option value="{{ $k }}" @selected(old('reason') === $k)>{{ $text }}</option>@endforeach
                    </select>
                </div>
                <label class="flex items-center gap-2 text-xs font-semibold text-slate-700 sm:mt-6"><input type="checkbox" name="auto_approve" value="1" checked class="rounded border-slate-300 text-indigo-600"> Approve it straight away</label>
            </div>
            <div>
                <label class="{{ $label }}">Details <span class="font-normal text-slate-400">(optional)</span></label>
                <textarea name="details" rows="3" maxlength="1000" class="{{ $input }}">{{ old('details') }}</textarea>
            </div>
            <button class="px-4 py-2.5 bg-indigo-600 text-white font-semibold text-xs rounded-xl shadow-lg shadow-indigo-600/30 hover:bg-indigo-700">Create return</button>
        </form>
    </div>
@endsection
