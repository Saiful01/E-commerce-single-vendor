@extends('layouts.admin')

@section('title', 'Edit Courier')

@section('content')
    <div class="max-w-6xl mx-auto space-y-5">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-bold text-slate-800">Edit courier</h2>
                <p class="text-xs text-slate-500 mt-1"><span class="font-semibold text-slate-700">{{ $courier->name }}</span> <span class="text-slate-400">· {{ $courier->driver_label }}</span></p>
            </div>
            <a href="{{ route('admin.courier-services.index') }}" class="text-xs font-semibold text-slate-500 hover:text-slate-800">&larr; Back to couriers</a>
        </div>

        @unless ($courier->isConfigured())
            <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-[11px] text-amber-800">
                Some API credentials are missing, so automatic booking will not work yet. Fill in every field under "API credentials".
            </div>
        @endunless

        @if (($count = $courier->ordersCount()) > 0)
            <div class="rounded-2xl border border-sky-200 bg-sky-50 px-4 py-3 text-[11px] text-sky-800">
                {{ $count }} {{ Str::plural('order', $count) }} already use this courier. Changes affect only new shipments.
            </div>
        @endif

        @include('admin.courier-services._form', [
            'action'  => route('admin.courier-services.update', $courier),
            'method'  => 'PUT',
            'courier' => $courier,
        ])
    </div>
@endsection
