@extends('layouts.admin')

@section('title', 'Edit Payment Method')

@section('content')
    <div class="max-w-6xl mx-auto space-y-5">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-bold text-slate-800">Edit payment method</h2>
                <p class="text-xs text-slate-500 mt-1"><span class="font-semibold text-slate-700">{{ $gateway->name }}</span> <span class="text-slate-400">· {{ $gateway->driver_label }}</span></p>
            </div>
            <a href="{{ route('admin.payment-gateways.index') }}" class="text-xs font-semibold text-slate-500 hover:text-slate-800">&larr; Back to payment methods</a>
        </div>

        @unless ($gateway->isConfigured())
            <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-[11px] text-amber-800">
                Some details are missing, so customers cannot see this method at checkout yet. Fill in every field under "Account details".
            </div>
        @endunless

        @if (($count = $gateway->ordersCount()) > 0)
            <div class="rounded-2xl border border-sky-200 bg-sky-50 px-4 py-3 text-[11px] text-sky-800">
                {{ $count }} {{ Str::plural('order', $count) }} already paid with this method. Changes affect only new orders.
            </div>
        @endif

        @include('admin.payment-gateways._form', [
            'action'  => route('admin.payment-gateways.update', $gateway),
            'method'  => 'PUT',
            'gateway' => $gateway,
        ])
    </div>
@endsection
