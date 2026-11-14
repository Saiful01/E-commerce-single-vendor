@extends('layouts.admin')

@section('title', 'Add Payment Method')

@section('content')
    <div class="max-w-6xl mx-auto space-y-5">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-bold text-slate-800">Add payment method</h2>
                <p class="text-xs text-slate-500 mt-1">Pick how customers can pay and fill in the details.</p>
            </div>
            <a href="{{ route('admin.payment-gateways.index') }}" class="text-xs font-semibold text-slate-500 hover:text-slate-800">&larr; Back to payment methods</a>
        </div>

        @include('admin.payment-gateways._form', [
            'action'  => route('admin.payment-gateways.store'),
            'method'  => 'POST',
            'gateway' => null,
        ])
    </div>
@endsection
