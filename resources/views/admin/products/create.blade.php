@extends('layouts.admin')

@section('title', 'Add Product')

@section('content')
    <div class="max-w-6xl mx-auto space-y-5">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-bold text-slate-800">Add product</h2>
                <p class="text-xs text-slate-500 mt-1">Details, images, variants and search listing in one place.</p>
            </div>
            <a href="{{ route('admin.products.index') }}" class="text-xs font-semibold text-slate-500 hover:text-slate-800">&larr; Back to products</a>
        </div>

        @include('admin.products._form', [
            'action'  => route('admin.products.store'),
            'method'  => 'POST',
            'product' => null,
        ])
    </div>
@endsection
