@extends('layouts.admin')

@section('title', 'Add Coupon')

@section('content')
    <div class="max-w-6xl mx-auto space-y-5">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-bold text-slate-800">Add coupon</h2>
                <p class="text-xs text-slate-500 mt-1">Set the code, the discount and who can use it.</p>
            </div>
            <a href="{{ route('admin.coupons.index') }}" class="text-xs font-semibold text-slate-500 hover:text-slate-800">&larr; Back to coupons</a>
        </div>

        @include('admin.coupons._form', [
            'action' => route('admin.coupons.store'),
            'method' => 'POST',
            'coupon' => null,
        ])
    </div>
@endsection
