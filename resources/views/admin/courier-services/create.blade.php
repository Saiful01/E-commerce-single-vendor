@extends('layouts.admin')

@section('title', 'Add Courier')

@section('content')
    <div class="max-w-6xl mx-auto space-y-5">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-bold text-slate-800">Add courier</h2>
                <p class="text-xs text-slate-500 mt-1">Add a delivery partner and connect its API if it has one.</p>
            </div>
            <a href="{{ route('admin.courier-services.index') }}" class="text-xs font-semibold text-slate-500 hover:text-slate-800">&larr; Back to couriers</a>
        </div>

        @include('admin.courier-services._form', [
            'action'  => route('admin.courier-services.store'),
            'method'  => 'POST',
            'courier' => null,
        ])
    </div>
@endsection
