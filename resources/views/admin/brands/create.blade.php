@extends('layouts.admin')

@section('title', 'Add Brand')

@section('content')
    <div class="max-w-6xl mx-auto space-y-5">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-bold text-slate-800">Add brand</h2>
                <p class="text-xs text-slate-500 mt-1">Name, logo, website and search listing.</p>
            </div>
            <a href="{{ route('admin.brands.index') }}" class="text-xs font-semibold text-slate-500 hover:text-slate-800">&larr; Back to brands</a>
        </div>

        @include('admin.brands._form', [
            'action' => route('admin.brands.store'),
            'method' => 'POST',
            'brand'  => null,
        ])
    </div>
@endsection
