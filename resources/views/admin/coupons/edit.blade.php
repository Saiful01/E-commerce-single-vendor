@extends('layouts.admin')

@section('title', 'Edit Coupon')

@section('content')
    <div class="max-w-6xl mx-auto space-y-5">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-bold text-slate-800">Edit coupon</h2>
                <p class="text-xs text-slate-500 mt-1"><span class="font-mono font-semibold text-slate-700">{{ $coupon->code }}</span> @if ($coupon->name) <span class="text-slate-400">· {{ $coupon->name }}</span> @endif</p>
            </div>
            <a href="{{ route('admin.coupons.index') }}" class="text-xs font-semibold text-slate-500 hover:text-slate-800">&larr; Back to coupons</a>
        </div>

        @if ($coupon->used_count > 0)
            <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-[11px] text-amber-800">
                This coupon has already been used {{ $coupon->used_count }} {{ Str::plural('time', $coupon->used_count) }}. Changing the code or the discount affects only new orders.
            </div>
        @endif

        @include('admin.coupons._form', [
            'action' => route('admin.coupons.update', $coupon),
            'method' => 'PUT',
            'coupon' => $coupon,
        ])
    </div>
@endsection
